<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import H1 from "@/components/H1.vue";
import CrudPage from "@/components/CrudPage.vue";
import Lead from "@/components/canban/Lead.vue";
import Column from "@/components/canban/Column.vue";
import { ref, reactive, watch, onMounted, computed } from "vue";
import { useForm } from "laravel-precognition-vue";
import axios from "axios";
import draggable from "vuedraggable";
import { vMaska } from "maska/vue";

import { useUserStore } from "@/stores/user";
const userStore = useUserStore();

import { useToastsStore } from "@/stores/toasts";
const toastsStore = useToastsStore();

const statusesLoaded = ref(false);
const allStatuses = [];
const statuses = reactive([]);
function loadStatuses() {
    axios
        .get(route("lead-statuses.index"))
        .then((response) => {
            const data = response.data.filter((s) => !s.is_final);
            allStatuses.splice(0, allStatuses.length, ...response.data);
            statuses.splice(0, statuses.length, ...data);
            statusesLoaded.value = true;
			loadLeads();
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
}

const leadsLoaded = ref(false);
const leads = reactive([]);
function loadLeads() {
    axios
        .get(route("leads.index", { statuses: statuses.map((s) => s.id) }))
        .then((response) => {
            leads.splice(0, leads.length, ...response.data);
            leadsLoaded.value = true;
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
}

// -----------------
// View/Edit lead modal
// -----------------
const showEditDialog = ref(false);
const currentLeadId = ref(null);
const currentLead = ref(null);
const editForm = useForm("put", () => route("leads.update", { lead: currentLeadId.value }), {
    title: "",
    description: "",
    amount: null,
    contact_id: null,
    status_id: null,
    user_id: null,
});

function openEditModal(lead) {
    currentLeadId.value = lead.id;
    showEditDialog.value = true;
    loadLeadDetails(lead.id);
}

function closeEditModal() {
    showEditDialog.value = false;
    currentLeadId.value = null;
    currentLead.value = null;
    editForm.reset();
    editForm.errors = {};
}

function loadLeadDetails(id) {
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
                    title: [c.surname, c.name, c.patronymic].filter(Boolean).join(' '),
                    subtitle: [c.phone?.number, c.email?.address].filter(Boolean).join(' • '),
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
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

function submitEditLead() {
    editForm
        .submit()
        .then((response) => {
            // Обновить элемент в списке
            const idx = leads.findIndex((l) => l.id === response.data.id);
            if (idx !== -1) {
                leads[idx] = response.data;
            }
            closeEditModal();
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

// -----------------
// Create lead modal
// -----------------
const showCreateDialog = ref(false);
const contactsLoaded = ref(true);
const contacts = reactive([]);
const contactSearch = ref("");
let contactsSearchTimeout = null;

// Users async search state
const usersLoaded = ref(true);
const users = reactive([]);
const userSearch = ref("");
let usersSearchTimeout = null;

function openCreateModal() {
    showCreateDialog.value = true;
    // префилл ответственного текущим пользователем
    if (userStore.user?.id) {
        createForm.user_id = userStore.user.id;
        const item = {
            id: userStore.user.id,
            title: userStore.user.name,
            subtitle: userStore.user.email,
        };
        if (!users.find((x) => x.id === item.id)) {
            users.unshift(item);
        }
    }
}

function closeCreateModal() {
    showCreateDialog.value = false;
    createForm.reset();
    createForm.errors = {};
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

const createForm = useForm("post", route("leads.store"), {
    title: "",
    description: "",
    amount: null,
    contact_id: null,
    status_id: null,
    user_id: null,
});

watch(statuses, (val) => {
    if (val?.length && !createForm.status_id) {
        createForm.status_id = val[0].id;
    }
}, { immediate: true });

function submitCreateLead() {
    createForm
        .submit()
        .then((response) => {
            leads.unshift(response.data);
            closeCreateModal();
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

function handleCreateStatusAfter(status) {
	if (statuses.length >= 250) {
		toastsStore.addError('Достигнуто максимальное количество статусов');
		return;
	}

    axios.post(route("lead-statuses.store"), {
        name: "Новый статус",
        position: status.position + 1,
    })
    .then(() => {
        loadStatuses();
    })
    .catch((error) => {
        toastsStore.handleResponseError(error);
    });
}

onMounted(() => {
    loadStatuses();
});

const leadsByStatusId = computed(() => {
    const grouped = {};
    statuses.forEach((s) => (grouped[s.id] = []));
    leads.forEach((lead) => {
        if (!grouped[lead.status_id]) grouped[lead.status_id] = [];
        grouped[lead.status_id].push(lead);
    });
    // сортируем внутри статусов по position, затем по created_at убыв.
    Object.keys(grouped).forEach((key) => {
        grouped[key].sort((a, b) => {
            if ((a.position ?? 0) !== (b.position ?? 0)) {
                return (a.position ?? 0) - (b.position ?? 0);
            }
            const aCreated = new Date(a.created_at).getTime();
            const bCreated = new Date(b.created_at).getTime();
            return bCreated - aCreated;
        });
    });
    return grouped;
});

function onStatusesMove(event) {
	return event.draggedContext.futureIndex > 0;
}

function onStatusesMoveEnd(event) {
	axios.post(route("lead-statuses.save-new-order"), {
		statuses: statuses.map((s) => ({
			id: s.id,
			position: s.position,
		})),
	})
	.catch((error) => {
		toastsStore.handleResponseError(error);
	});
}

function handleStatusUpdated() {
    loadStatuses();
}
function handleStatusDeleted() {
    loadStatuses();
}

function onLeadChange(event, targetStatus) {
    if (!event) return;

    // Игнорируем чисто source-событие удаления, обработаем на целевой колонке
    if (event.removed && !event.added && !event.moved) {
        return;
    }

    // Сформировать текущие массивы id по статусам
    const mapStatusToIds = {};
    statuses.forEach((s) => {
        mapStatusToIds[s.id] = (leadsByStatusId.value[s.id] || []).map((l) => l.id);
    });

    if (event.added && !event.moved) {
        // Перенос между колонками (обрабатываем на целевой колонке)
        const movedLead = event.added.element;
        const sourceStatusId = movedLead.status_id;
        const targetStatusId = targetStatus.id;

        // Удаляем из источника (без знания индекса просто фильтруем)
        mapStatusToIds[sourceStatusId] = (mapStatusToIds[sourceStatusId] || []).filter(
            (id) => id !== movedLead.id
        );

        // Вставляем в цель по новому индексу
        const dstCopy = mapStatusToIds[targetStatusId].slice();
        dstCopy.splice(event.added.newIndex, 0, movedLead.id);
        mapStatusToIds[targetStatusId] = dstCopy;

        // Локально обновим статус у лида
        const idx = leads.findIndex((l) => l.id === movedLead.id);
        if (idx !== -1) {
            leads[idx] = { ...leads[idx], status_id: targetStatusId };
        }
    }

    // Пересчитаем позиции и локально обновим их
    const affectedStatusIds = new Set();
    if (event.moved) affectedStatusIds.add(targetStatus.id);
    if (event.added && !event.moved) {
        affectedStatusIds.add(targetStatus.id);
        const movedLead = event.added.element;
        if (movedLead?.status_id) {
            affectedStatusIds.add(movedLead.status_id);
        }
    }

    affectedStatusIds.forEach((statusId) => {
        const ids = mapStatusToIds[statusId] || [];
        ids.forEach((leadId, index) => {
            const li = leads.findIndex((l) => l.id === leadId);
            if (li !== -1) {
                leads[li] = { ...leads[li], position: index + 1, status_id: statusId };
            }
        });
    });

    // Отправим только измененные колонки
    const payload = {
        columns: Array.from(affectedStatusIds).map((statusId) => ({
            status_id: statusId,
            ids: mapStatusToIds[statusId] || [],
        })),
    };

    axios
        .post(route('leads.save-new-order'), payload)
        .then(() => {})
        .catch((error) => toastsStore.handleResponseError(error));
}
</script>

<template>
    <AppLayout>
        <CrudPage>
            <template v-slot:header>
                <div class="justify-between d-flex">
                    <H1>Лиды</H1>

                    <div class="d-flex ga-2">
                        <v-btn
                            prepend-icon="mdi-account-plus"
                            @click="openCreateModal"
                            color="primary"
                            >Создать лид</v-btn
                        >
                    </div>
                </div>

                <div class="justify-start mt-3 d-flex">
                    <!-- <v-text-field
                        v-model="search"
                        density="compact"
                        placeholder="Поиск"
                        variant="outlined"
                        hide-details
                        max-width="300px"
                        append-inner-icon="mdi-magnify"
                        clearable
                    /> -->
                </div>
            </template>
            <template v-slot:content>
                <div class="overflow-x-auto">
                    <draggable
                        :list="statuses"
                        item-key="id"
                        class="flex gap-4 py-2 min-h-[calc(100vh-220px)] h-[calc(100vh-220px)]"
                        ghost-class="opacity-50"
                        :animation="200"
                        handle=".column-drag-handle"
                        :move="(event) => onStatusesMove(event)"
						@end="onStatusesMoveEnd"
                    >
                        <template #item="{ element: status, index }">
                            <Column
                                :status="status"
                                :items-count="
                                    (leadsByStatusId[status.id] &&
                                        leadsByStatusId[status.id].length) ||
                                    0
                                "
                                @create-status-after="handleCreateStatusAfter"
                                @status-updated="handleStatusUpdated"
                                @status-deleted="handleStatusDeleted"
                            >
							<draggable
								class="flex flex-col gap-2 h-full pr-2"
								:list="leadsByStatusId[status.id]"
								item-key="id"
								group="leads"
								ghost-class="opacity-50"
								:animation="200"
								:sort="true"
								@change="(e) => onLeadChange(e, status)"
							>
								<template #item="{ element }">
									<Lead
										:lead="element"
										:color="status.color"
                                        @open="openEditModal"
									/>
								</template>
							</draggable>
						</Column>
                        </template>
                    </draggable>
                </div>

                <!-- ------ -->
                <!-- Modals -->
                <!-- ------ -->
                <v-dialog v-model="showCreateDialog" max-width="640">
                    <v-card>
                        <v-card-title class="text-base">Создание лида</v-card-title>
                        <v-card-text>
                            <v-form @submit.prevent="submitCreateLead">
                                <div class="flex flex-col gap-3">
                                    <v-text-field
                                        v-model="createForm.title"
                                        label="Название"
                                        variant="outlined"
                                        density="comfortable"
                                        :error="!!createForm.errors.title"
                                        :error-messages="createForm.errors.title"
                                        maxlength="255"
                                        required
                                        clearable
                                    />

                                    <v-autocomplete
                                        v-model="createForm.contact_id"
                                        :items="contacts"
                                        item-title="title"
                                        item-value="id"
                                        label="Контакт"
                                        variant="outlined"
                                        density="comfortable"
                                        :loading="!contactsLoaded"
                                        clearable
                                        :error="!!createForm.errors.contact_id"
                                        :error-messages="createForm.errors.contact_id"
                                        @update:search="handleContactSearch"
										:custom-filter="() => true"
                                        no-data-text="Ничего не найдено"
										hint="Введите минимум 3 символа для поиска"
										:persistent-hint="true"
										placeholder="Имя, телефон, email"
                                    >
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item v-bind="props"
												:subtitle="item.raw.subtitle"
												:title="item.raw.title"
											/>
                                        </template>
                                    </v-autocomplete>

                                    <v-autocomplete
                                        v-model="createForm.status_id"
                                        :items="statuses"
                                        item-title="name"
                                        item-value="id"
                                        label="Статус"
                                        variant="outlined"
                                        density="comfortable"
                                        :error="!!createForm.errors.status_id"
                                        :error-messages="createForm.errors.status_id"
                                        required
                                    />

                                    <v-autocomplete
                                        v-model="createForm.user_id"
                                        :items="users"
                                        item-title="title"
                                        item-value="id"
                                        label="Ответственный"
                                        variant="outlined"
                                        density="comfortable"
                                        :loading="!usersLoaded"
                                        clearable
                                        :error="!!createForm.errors.user_id"
                                        :error-messages="createForm.errors.user_id"
                                        @update:search="handleUserSearch"
                                        :custom-filter="() => true"
                                        no-data-text="Ничего не найдено"
                                        hint="Введите минимум 3 символа для поиска"
                                        :persistent-hint="true"
                                        placeholder="Имя или email"
                                    >
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item v-bind="props"
                                                :subtitle="item.raw.subtitle"
                                                :title="item.raw.title"
                                            />
                                        </template>
                                    </v-autocomplete>

                                    <v-text-field
                                        v-model.number="createForm.amount"
                                        label="Сумма"
                                        type="number"
                                        variant="outlined"
                                        density="comfortable"
                                        :error="!!createForm.errors.amount"
                                        :error-messages="createForm.errors.amount"
                                        clearable
                                        min="0"
                                    />

                                    <v-textarea
                                        v-model="createForm.description"
                                        label="Описание"
                                        variant="outlined"
                                        density="comfortable"
                                        :error="!!createForm.errors.description"
                                        :error-messages="createForm.errors.description"
                                        auto-grow
                                        clearable
                                    />
                                </div>
                            </v-form>
                        </v-card-text>
                        <v-card-actions class="justify-end">
                            <v-btn variant="text" @click="closeCreateModal" :disabled="createForm.processing">Отмена</v-btn>
                            <v-btn color="primary" :loading="createForm.processing" @click="submitCreateLead">Создать</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <v-dialog v-model="showEditDialog" max-width="720">
                    <v-card>
                        <v-card-title class="text-base">{{ currentLead?.title }}</v-card-title>
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
                                                    <v-list-item v-bind="props"
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
                                                    <v-list-item v-bind="props"
                                                        :title="item.raw.name"
                                                    >
														<template v-slot:prepend>
															<v-icon icon="mdi-check" size="small" v-if="item.raw.is_win" color="success" />
															<v-icon icon="mdi-close" size="small" v-if="item.raw.is_loose" color="error" />
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
                                                    <v-list-item v-bind="props"
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

                                            <v-textarea
                                                v-model="editForm.description"
                                                label="Описание"
                                                variant="outlined"
                                                density="comfortable"
                                                :error="!!editForm.errors.description"
                                                :error-messages="editForm.errors.description"
                                                auto-grow
                                                clearable
                                            />
                                        </div>
                                    </v-form>
                                </div>
                                <div>
                                    <div class="text-subtitle-2 mb-2">Контакт</div>
                                    <v-card variant="tonal">
                                        <v-card-text>
                                            <div class="flex flex-col gap-1">
                                                <div>
                                                    <strong>Фамилия:</strong>
                                                    {{ currentLead.contact?.surname || '—' }}
                                                </div>
                                                <div>
                                                    <strong>Имя:</strong>
                                                    {{ currentLead.contact?.name || '—' }}
                                                </div>
                                                <div>
                                                    <strong>Отчество:</strong>
                                                    {{ currentLead.contact?.patronymic || '—' }}
                                                </div>
                                                <div>
                                                    <strong>Телефоны:</strong>
                                                    {{ currentLead.contact?.phones?.map((p) => p.number).join(', ') || '—' }}
                                                </div>
                                                <div>
                                                    <strong>Emails:</strong>
                                                    {{ currentLead.contact?.emails?.map((e) => e.address).join(', ') || '—' }}
                                                </div>
                                                <div>
                                                    <strong>Telegram:</strong>
                                                    {{ currentLead.contact?.telegram || '—' }}
                                                </div>
                                            </div>
                                        </v-card-text>
                                    </v-card>
                                </div>
                            </div>
                        </v-card-text>
                        <v-card-actions class="justify-end">
                            <v-btn variant="text" @click="closeEditModal" :disabled="editForm.processing">Отмена</v-btn>
                            <v-btn color="primary" :loading="editForm.processing" @click="submitEditLead">Сохранить</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </template>
        </CrudPage>
    </AppLayout>
</template>

<style lang="scss" scoped></style>
