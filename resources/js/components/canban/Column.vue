<script setup>
import { ref, reactive, watch, computed } from 'vue';
import axios from 'axios';
import { useToastsStore } from '@/stores/toasts';
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
const isAdmin = computed(() => userStore.user.role === 'admin');

const props = defineProps({
    status: {
        type: Object,
        required: true,
    },
    itemsCount: {
        type: Number,
        required: true,
    },
    hasNext: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['create-status-after', 'status-updated', 'status-deleted']);

const toastsStore = useToastsStore();

const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const formRef = ref(null);
const editForm = reactive({
    name: '',
    color: '#22c55e',
});

// Возвращает контрастный цвет текста (чёрный/белый) для заданного фона
const statusTextColor = computed(() => {
    const background = props.status?.color || '#ffffff';
    return getContrastTextColor(background);
});

function getContrastTextColor(color) {
    const { r, g, b } = parseToRgb(color);
    // YIQ — быстрая эвристика контраста
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;
    return yiq >= 186 ? '#000000' : '#ffffff';
}

function parseToRgb(input) {
    if (!input) return { r: 255, g: 255, b: 255 };

    // HEX: #RRGGBB или #RGB
    const hexMatch = String(input).trim().match(/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/);
    if (hexMatch) {
        let hex = hexMatch[1];
        if (hex.length === 3) {
            hex = hex.split('').map((c) => c + c).join('');
        }
        const intVal = parseInt(hex, 16);
        return {
            r: (intVal >> 16) & 255,
            g: (intVal >> 8) & 255,
            b: intVal & 255,
        };
    }

    // rgb() или rgba()
    const rgbMatch = String(input)
        .trim()
        .match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|0?\.\d+|1))?\s*\)$/);
    if (rgbMatch) {
        return {
            r: Math.max(0, Math.min(255, Number(rgbMatch[1]))),
            g: Math.max(0, Math.min(255, Number(rgbMatch[2]))),
            b: Math.max(0, Math.min(255, Number(rgbMatch[3]))),
        };
    }

    // Фоллбек — белый
    return { r: 255, g: 255, b: 255 };
}

watch(
    () => props.status,
    (s) => {
        if (!s) return;
        editForm.name = s.name;
        editForm.color = s.color || '#22c55e';
    },
    { immediate: true }
);

function openEditDialog() {
    editForm.name = props.status.name;
    editForm.color = props.status.color || '#22c55e';
    showEditDialog.value = true;
}

function openDeleteDialog() {
	showDeleteDialog.value = true;
}

async function saveEdit() {
    try {
        isSaving.value = true;
        await axios.put(route('lead-statuses.update', props.status.id), {
            name: editForm.name,
            color: editForm.color,
        });
        showEditDialog.value = false;
        emit('status-updated');
    } catch (error) {
        toastsStore.handleResponseError(error);
    } finally {
        isSaving.value = false;
    }
}

function deleteStatus() {
	isDeleting.value = true;

	axios.delete(route('lead-statuses.destroy', props.status.id))
	.then(response => {
		isDeleting.value = false;
		emit('status-deleted');
	})
	.catch(error => toastsStore.handleResponseError(error))
	.finally(() => showDeleteDialog.value = false)
}
</script>

<template>
    <div class="flex-none w-[320px] md:w-[360px] max-w-[400px] relative group">
        <v-card variant="flat" class="border rounded-md h-full flex flex-col">
            <v-card-title
                class="text-sm md:text-base d-flex align-center justify-between ga-1 group"
                :style="{
                    backgroundColor: status.color,
                    color: statusTextColor,
                }"
                :title="status.name"
            >
                <div class="d-flex align-center ga-2 min-w-0">
                    <v-icon
                        class="column-drag-handle cursor-move"
                        size="18"
                        icon="mdi-drag"
                        v-if="status.position > 0 && isAdmin"
                    />
                    <span class="truncate">
                        {{ status.name }}
                    </span>
                </div>
                <div class="d-flex align-center ga-1">
                    <v-btn
                        icon="mdi-pencil"
                        variant="text"
                        size="small"
                        density="comfortable"
                        class="opacity-0 group-hover:opacity-100! transition-opacity duration-150"
                        :title="'Редактировать статус ' + status.name"
                        @click.stop="openEditDialog"
                        v-if="!status.is_final && isAdmin"
                    />
                    <v-btn
                        icon="mdi-trash-can"
                        variant="text"
                        size="small"
                        density="comfortable"
						color="error"
						@click.stop="openDeleteDialog"
                        class="opacity-0 group-hover:opacity-100! transition-opacity duration-150"
                        :title="'Удалить статус ' + status.name"
                        v-if="!status.is_final && !status.is_system && isAdmin"
                    />

					<v-chip size="small" color="primary" variant="flat">
                        {{ itemsCount }}
                    </v-chip>
                </div>
            </v-card-title>
            <v-divider />
            <v-card-text
                class="p-2 flex-1 overflow-x-hidden min-h-0 h-[calc(100%-48px)]"
            >
                <div class="flex flex-col gap-2 h-full overflow-x-hidden">
                    <slot />
                </div>
            </v-card-text>
        </v-card>

        <!-- Кнопка добавления статуса: появляется при наведении на колонку -->
        <div
            class="absolute top-1/2 -translate-x-[.125rem] left-[100%] z-20 opacity-0 group-hover:opacity-100! transition-opacity duration-150 "
            v-if="hasNext"
        >
            <v-btn
				v-if="isAdmin"
                icon="mdi-plus"
                color="primary"
                variant="flat"
                rounded="circle"
                density="comfortable"
                size="x-small"
                :title="'Добавить статус после ' + status.name"
                @click="emit('create-status-after', status)"
            />
        </div>

        <!-- Диалог редактирования статуса -->
        <v-dialog v-model="showEditDialog" max-width="420">
            <v-card>
                <v-card-title class="text-base">Редактирование статуса</v-card-title>
                <v-card-text>
                    <v-form ref="formRef" @submit.prevent="saveEdit">
                        <div class="d-flex flex-column ga-3">
                            <v-text-field
                                v-model="editForm.name"
                                label="Название"
                                variant="outlined"
                                density="comfortable"
                                :rules="[(v) => !!v || 'Введите название']"
                                maxlength="255"
                                required
                                clearable
                            />
                            <div>
                                <div class="text-caption mb-1">Цвет</div>
								<div class="flex justify-center">
									<v-color-picker
										v-model="editForm.color"
										hide-canvas="false"
										hide-sliders
										hide-inputs
										mode="hex"
										show-swatches
										canvas-height="120"
									/>
								</div>
                            </div>
                        </div>
                    </v-form>
                </v-card-text>
                <v-card-actions class="justify-end">
                    <v-btn variant="text" @click="showEditDialog = false" :disabled="isSaving">Отмена</v-btn>
                    <v-btn color="primary" :loading="isSaving" @click="saveEdit">Сохранить</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Диалог удаления статуса -->
        <v-dialog v-model="showDeleteDialog" max-width="420">
            <v-card>
                <v-card-title class="text-base">Удаление статуса</v-card-title>
                <v-card-text>
                    Вы уверены, что хотите удалить статус {{ status.name }}?
                </v-card-text>
                <v-card-actions class="justify-end">
                    <v-btn variant="text" @click="showDeleteDialog = false" :disabled="isDeleting">Отмена</v-btn>
                    <v-btn color="primary" :loading="isDeleting" @click="deleteStatus">Удалить</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
