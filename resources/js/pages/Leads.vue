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
            // Полностью перезаписываем массив, сохраняя реактивность
            statuses.splice(0, statuses.length, ...data);
            statusesLoaded.value = true;
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
}

const leadsLoaded = ref(false);
const leads = ref([]);

async function handleCreateStatusAfter(status) {
	if (statuses.length >= 250) {
		toastsStore.addError('Достигнуто максимальное количество статусов');
		return;
	}

    try {
        const response = await axios.post(route("lead-statuses.store"), {
            name: "Новый статус",
            position: status.position + 1,
        });
        // Обновляем список статусов, чтобы появился новый
        await loadStatuses();
    } catch (error) {
        toastsStore.handleResponseError(error);
    }
}

onMounted(() => {
    loadStatuses();
});

// const leads = ref([
//     {
//         id: 101,
//         name: "Разработка лендинга лендинга лендинга лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 2,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 101,
//         name: "Разработка лендинга",
//         contact: { full_name: "Иван Иванов" },
//         user: { name: "Пётр" },
//         status_id: 1,
//     },
//     {
//         id: 102,
//         name: "SEO для ООО Ромашка",
//         contact: { full_name: "Мария Петрова" },
//         user: { name: "Светлана" },
//         status_id: 2,
//     },
//     {
//         id: 103,
//         name: "Редизайн интернет‑магазина",
//         contact: { full_name: "Алексей Сидоров" },
//         user: { name: "Николай" },
//         status_id: 3,
//     },
//     {
//         id: 104,
//         name: "CRM интеграция",
//         contact: { full_name: "ООО Бета" },
//         user: { name: "Ирина" },
//         status_id: 4,
//     },
//     {
//         id: 105,
//         name: "Подписка на сопровождение",
//         contact: { full_name: "ЧП Дельта" },
//         user: { name: "Павел" },
//         status_id: 5,
//     },
// ]);

const leadsByStatusId = computed(() => {
    const grouped = {};
    statuses.forEach((s) => (grouped[s.id] = []));
    leads.value.forEach((lead) => {
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
                                <template v-if="!statusesLoaded">
                                    <v-skeleton-loader
                                        type="card"
                                        class="mb-2"
                                        v-for="n in 3"
                                        :key="n"
                                    />
                                </template>
                                <template v-else>
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
                                </template>
                            </Column>
                        </template>
                    </draggable>
                </div>

                <!-- ------ -->
                <!-- Modals -->
                <!-- ------ -->
            </template>
        </CrudPage>
    </AppLayout>
</template>

<style lang="scss" scoped></style>
