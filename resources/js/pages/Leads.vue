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
const statuses = reactive([]);
function loadStatuses() {
    axios
        .get(route("lead-statuses.index"))
        .then((response) => {
            const data = response.data.filter((s) => !s.is_final);
            statuses.splice(0, statuses.length, ...data);
            statusesLoaded.value = true;
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
}

const leadsLoaded = ref(false);
const leads = reactive([]);
function loadLeads() {
    axios
        .get(route("leads.index"))
        .then((response) => {
            leads.splice(0, leads.length, ...response.data);
            leadsLoaded.value = true;
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
}

// -----------------
// Create lead modal
// -----------------
const showCreateDialog = ref(false);
const contactsLoaded = ref(true);
const contacts = reactive([]);
const contactSearch = ref("");
let contactsSearchTimeout = null;

function openCreateModal() {
    showCreateDialog.value = true;
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

const createForm = useForm("post", route("leads.store"), {
    title: "",
    description: "",
    amount: null,
    contact_id: null,
    status_id: null,
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
	loadLeads();
});

const leadsByStatusId = computed(() => {
    const grouped = {};
    statuses.forEach((s) => (grouped[s.id] = []));
    leads.forEach((lead) => {
        if (!grouped[lead.status_id]) grouped[lead.status_id] = [];
        grouped[lead.status_id].push(lead);
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
                    <v-text-field
                        v-model="search"
                        density="compact"
                        placeholder="Поиск"
                        variant="outlined"
                        hide-details
                        max-width="300px"
                        append-inner-icon="mdi-magnify"
                        clearable
                    />
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
								<Lead
									v-for="lead in leadsByStatusId[status.id]"
									:key="lead.id"
									:lead="lead"
									:color="status.color"
								/>
								<div
									v-if="
										!leadsByStatusId[status.id] ||
										leadsByStatusId[status.id].length === 0
									"
									class="text-gray-500 text-xs text-center py-4"
								>
									Пусто
								</div>
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
            </template>
        </CrudPage>
    </AppLayout>
</template>

<style lang="scss" scoped></style>
