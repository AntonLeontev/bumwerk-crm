<script setup>
import { ref, reactive, watch, computed } from "vue";
import { useForm } from "laravel-precognition-vue";
import axios from "axios";
import { useDate } from "vuetify";

import { useToastsStore } from "@/stores/toasts";
const toastsStore = useToastsStore();

const date = useDate();

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    leadId: {
        type: [Number, String],
        default: null,
    },
    allStatuses: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:modelValue", "lead-updated"]);

const currentLead = ref(null);
const comments = reactive([]);
const commentsContainer = ref(null);

// Users async search state
const usersLoaded = ref(true);
const users = reactive([]);
const userSearch = ref("");
let usersSearchTimeout = null;

// Contacts async search state
const contactsLoaded = ref(true);
const contacts = reactive([]);
const contactSearch = ref("");
let contactsSearchTimeout = null;

// Comment form state
const commentText = ref("");
const isSubmittingComment = ref(false);

const editForm = useForm(
    "put",
    () => route("leads.update", { lead: props.leadId }),
    {
        title: "",
        description: "",
        amount: null,
        contact_id: null,
        status_id: null,
        user_id: null,
    }
);

const showDialog = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

function closeModal() {
    showDialog.value = false;
	setTimeout(() => {
		currentLead.value = null;
		comments.splice(0, comments.length);
		commentText.value = "";
		isSubmittingComment.value = false;
		editForm.reset();
		editForm.errors = {};
	}, 200)
}

function loadLeadDetails(id) {
    if (!id) return;

    axios
        .get(route("leads.show", { lead: id }))
        .then((response) => {
            currentLead.value = response.data;
            // Заполнить форму редактирования
            editForm.title = currentLead.value.title || "";
            editForm.description = currentLead.value.description || "";
            editForm.amount = currentLead.value.amount ?? null;
            editForm.contact_id = currentLead.value.contact_id ?? null;
            editForm.status_id = currentLead.value.status_id ?? null;
            editForm.user_id = currentLead.value.user_id ?? null;

            // Убедиться, что выбранный контакт отображается в списке
            if (currentLead.value.contact) {
                const c = currentLead.value.contact;
                const item = {
                    id: c.id,
                    title: [c.surname, c.name, c.patronymic]
                        .filter(Boolean)
                        .join(" "),
                    subtitle: [c.phone?.number, c.email?.address]
                        .filter(Boolean)
                        .join(" • "),
                };
                if (!contacts.find((x) => x.id === item.id)) {
                    contacts.unshift(item);
                }
            }

            // Убедиться, что выбранный ответственный отображается в списке
            if (currentLead.value.user) {
                const u = currentLead.value.user;
                const uItem = {
                    id: u.id,
                    title: u.name,
                    subtitle: u.email,
                };
                if (!users.find((x) => x.id === uItem.id)) {
                    users.unshift(uItem);
                }
            }

            // Загрузить комментарии
            if (currentLead.value.comments) {
                comments.splice(
                    0,
                    comments.length,
                    ...currentLead.value.comments
                );
                // Прокрутить к последним комментариям
                setTimeout(() => {
                    if (commentsContainer.value) {
                        commentsContainer.value.scrollTop =
                            commentsContainer.value.scrollHeight;
                    }
                }, 100);
            }
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

function submitEditLead() {
    editForm
        .submit()
        .then((response) => {
            emit("lead-updated", response.data);
            closeModal();
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

function loadContacts() {
    axios
        .get(route("contacts.index"), {
            params: {
                items_per_page: 20,
                search: contactSearch.value || undefined,
            },
        })
        .then((response) => {
            // Преобразуем элементы для автокомплита: заголовок + доп. описание
            const items = response.data.data.map((c) => ({
                ...c,
                title: c.title,
                subtitle: [c.phone, c.email].filter(Boolean).join(" • "),
            }));
            contacts.splice(0, contacts.length, ...items);
            contactsLoaded.value = true;
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

function handleContactSearch(val) {
    contactSearch.value = val;
    // Дебаунс + минимальная длина 3 символа
    if (contactsSearchTimeout) clearTimeout(contactsSearchTimeout);
    // Если меньше 3 символов — очищаем список и ничего не грузим
    if (!val || val.length < 3) {
        contacts.length = 0;
        contactsLoaded.value = true;
        return;
    }
    contactsLoaded.value = false;
    contactsSearchTimeout = setTimeout(() => {
        loadContacts();
    }, 350);
}

function loadUsers() {
    axios
        .get(route("users.index"), {
            params: {
                items_per_page: 20,
                search: userSearch.value || undefined,
            },
        })
        .then((response) => {
            const items = response.data.data.map((u) => ({
                ...u,
                title: u.name,
                subtitle: u.email,
            }));
            users.splice(0, users.length, ...items);
            usersLoaded.value = true;
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

function handleUserSearch(val) {
    userSearch.value = val;
    // Дебаунс + минимальная длина 3 символа
    if (usersSearchTimeout) clearTimeout(usersSearchTimeout);
    // Если меньше 3 символов — очищаем список и ничего не грузим
    if (!val || val.length < 3) {
        users.length = 0;
        usersLoaded.value = true;
        return;
    }
    usersLoaded.value = false;
    usersSearchTimeout = setTimeout(() => {
        loadUsers();
    }, 350);
}

function submitComment() {
    if (!commentText.value.trim() || !props.leadId) return;
    
    isSubmittingComment.value = true;
    
    axios
        .post(route("leads.comments.store", { lead: props.leadId }), {
            text: commentText.value.trim(),
        })
        .then((response) => {
            // Добавляем новый комментарий в список
            comments.push(response.data);
            commentText.value = "";
            
            // Прокрутить к последнему комментарию
            setTimeout(() => {
                if (commentsContainer.value) {
                    commentsContainer.value.scrollTop =
                        commentsContainer.value.scrollHeight;
                }
            }, 100);
        })
        .catch((error) => toastsStore.handleResponseError(error))
        .finally(() => {
            isSubmittingComment.value = false;
        });
}

// Следить за изменениями leadId и загружать детали лида
watch(
    () => props.leadId,
    (newId) => {
        if (newId && props.modelValue) {
            loadLeadDetails(newId);
        }
    },
    { immediate: true }
);

// Следить за открытием модального окна
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue && props.leadId) {
            loadLeadDetails(props.leadId);
        } else if (!newValue) {
            closeModal();
        }
    }
);
</script>

<template>
    <v-dialog v-model="showDialog" max-width="720">
        <v-card>
            <v-card-title class="text-base">{{
                currentLead?.title
            }}</v-card-title>
            <v-card-text>
                <div v-if="!currentLead">Загрузка...</div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <v-form @submit.prevent="submitEditLead">
                            <div class="flex flex-col gap-3">
                                <v-text-field
                                    v-model="editForm.title"
                                    label="Название"
                                    variant="outlined"
                                    density="comfortable"
                                    :error="!!editForm.errors.title"
                                    :error-messages="editForm.errors.title"
                                    maxlength="255"
                                    required
                                    clearable
                                />

                                <v-autocomplete
                                    v-model="editForm.contact_id"
                                    :items="contacts"
                                    item-title="title"
                                    item-value="id"
                                    label="Контакт"
                                    variant="outlined"
                                    density="comfortable"
                                    :loading="!contactsLoaded"
                                    clearable
                                    :error="!!editForm.errors.contact_id"
                                    :error-messages="editForm.errors.contact_id"
                                    @update:search="handleContactSearch"
                                    :custom-filter="() => true"
                                    no-data-text="Ничего не найдено"
                                    hint="Введите минимум 3 символа для поиска"
                                    :persistent-hint="true"
                                    placeholder="Имя, телефон, email"
                                >
                                    <template v-slot:item="{ props, item }">
                                        <v-list-item
                                            v-bind="props"
                                            :subtitle="item.raw.subtitle"
                                            :title="item.raw.title"
                                        />
                                    </template>
                                </v-autocomplete>

                                <v-autocomplete
                                    v-model="editForm.status_id"
                                    :items="allStatuses"
                                    item-title="name"
                                    item-value="id"
                                    label="Статус"
                                    variant="outlined"
                                    density="comfortable"
                                    :error="!!editForm.errors.status_id"
                                    :error-messages="editForm.errors.status_id"
                                    required
                                >
                                    <template v-slot:item="{ props, item }">
                                        <v-list-item
                                            v-bind="props"
                                            :title="item.raw.name"
                                        >
                                            <template v-slot:prepend>
                                                <v-icon
                                                    icon="mdi-check"
                                                    size="small"
                                                    v-if="item.raw.is_win"
                                                    color="success"
                                                />
                                                <v-icon
                                                    icon="mdi-close"
                                                    size="small"
                                                    v-if="item.raw.is_loose"
                                                    color="error"
                                                />
                                            </template>
                                        </v-list-item>
                                    </template>
                                </v-autocomplete>

                                <v-autocomplete
                                    v-model="editForm.user_id"
                                    :items="users"
                                    item-title="title"
                                    item-value="id"
                                    label="Ответственный"
                                    variant="outlined"
                                    density="comfortable"
                                    :loading="!usersLoaded"
                                    clearable
                                    :error="!!editForm.errors.user_id"
                                    :error-messages="editForm.errors.user_id"
                                    @update:search="handleUserSearch"
                                    :custom-filter="() => true"
                                    no-data-text="Ничего не найдено"
                                    hint="Введите минимум 3 символа для поиска"
                                    :persistent-hint="true"
                                    placeholder="Имя или email"
                                >
                                    <template v-slot:item="{ props, item }">
                                        <v-list-item
                                            v-bind="props"
                                            :subtitle="item.raw.subtitle"
                                            :title="item.raw.title"
                                        />
                                    </template>
                                </v-autocomplete>

                                <v-text-field
                                    v-model.number="editForm.amount"
                                    label="Сумма"
                                    type="number"
                                    variant="outlined"
                                    density="comfortable"
                                    :error="!!editForm.errors.amount"
                                    :error-messages="editForm.errors.amount"
                                    clearable
                                    min="0"
                                />
                            </div>
                        </v-form>
                    </div>
                    <div>
                        <v-card variant="outlined" title="Контакт">
                            <v-card-text>
                                <div class="flex flex-col gap-1">
                                    <div>
                                        <strong>Фамилия:</strong>
                                        {{
                                            currentLead.contact?.surname || "—"
                                        }}
                                    </div>
                                    <div>
                                        <strong>Имя:</strong>
                                        {{ currentLead.contact?.name || "—" }}
                                    </div>
                                    <div>
                                        <strong>Отчество:</strong>
                                        {{
                                            currentLead.contact?.patronymic ||
                                            "—"
                                        }}
                                    </div>
                                    <div>
                                        <strong>Телефоны:</strong>
                                        {{
                                            currentLead.contact?.phones
                                                ?.map((p) => p.number)
                                                .join(", ") || "—"
                                        }}
                                    </div>
                                    <div>
                                        <strong>Emails:</strong>
                                        {{
                                            currentLead.contact?.emails
                                                ?.map((e) => e.address)
                                                .join(", ") || "—"
                                        }}
                                    </div>
                                    <div>
                                        <strong>Telegram:</strong>
                                        {{
                                            currentLead.contact?.telegram || "—"
                                        }}
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>
                    </div>
                    <div class="col-span-2">
                        <v-card
                            variant="outlined"
                            title="История"
                        >
                            <v-card-text class="">
                                <div
                                    class="h-60 overflow-y-auto pr-3 mb-4"
                                    ref="commentsContainer"
                                >
                                    <div
                                        v-if="comments.length === 0"
                                        class="text-center text-grey-500 mt-8"
                                    >
                                        Комментариев пока нет
                                    </div>
                                    <div v-else class="flex flex-col gap-3">
                                        <div
                                            v-for="comment in comments"
                                            :key="comment.id"
                                            class="border-l-primary py-2"
											:class="{
												'border-l-2 pl-3': comment.user_id !== null,
											}"
                                        >
                                            <div class="text-body-2 mb-1">
                                                {{ comment.text }}
                                            </div>
                                            <div
                                                class="text-caption text-grey-darken-1 flex items-center gap-2 justify-between"
                                            >
                                                <span>{{
                                                    comment.user?.name ||
                                                    "Система"
                                                }}</span>
                                                <span>{{
                                                    date.format(
                                                        comment.updated_at ||
                                                            comment.created_at,
                                                        "fullDateTime"
                                                    )
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Форма добавления комментария -->
                                <div class="border-t pt-4">
                                    <v-form @submit.prevent="submitComment">
                                        <div class="relative">
                                            <v-textarea
                                                v-model="commentText"
                                                label="Добавить комментарий"
                                                variant="outlined"
                                                density="comfortable"
                                                rows="2"
                                                max-rows="6"
                                                auto-grow
                                                maxlength="2000"
												:hide-details="true"
                                                counter
                                                placeholder="Введите ваш комментарий..."
                                                :disabled="isSubmittingComment"
                                                required
                                            >
												<template v-slot:append-inner>
													<v-btn
														type="submit"
														color="primary"
														:loading="isSubmittingComment"
														:disabled="!commentText.trim()"
														size="small"
														icon="mdi-arrow-right-thick"
														@click="submitComment"
													/>
												</template>
											</v-textarea>
                                        </div>
                                    </v-form>
                                </div>
                            </v-card-text>
                        </v-card>
                    </div>
                </div>
            </v-card-text>
            <v-card-actions class="justify-end">
                <v-btn
                    variant="text"
                    @click="closeModal"
                    :disabled="editForm.processing"
                    >Отмена</v-btn
                >
                <v-btn
                    color="primary"
                    :loading="editForm.processing"
                    @click="submitEditLead"
                    >Сохранить</v-btn
                >
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style lang="scss" scoped></style>
