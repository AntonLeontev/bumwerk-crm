<script setup>
import { ref, watch } from "vue";
import { useForm } from "laravel-precognition-vue";
import { vMaska } from "maska/vue";

import { useToastsStore } from "@/stores/toasts";
const toastsStore = useToastsStore();

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    contact: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue", "updated"]);

const localModelValue = ref(props.modelValue);

watch(
    () => props.modelValue,
    (newValue) => {
        localModelValue.value = newValue;
        if (newValue && props.contact) {
            // Заполняем форму данными контакта
            editForm.name = props.contact.name || "";
            editForm.surname = props.contact.surname || "";
            editForm.patronymic = props.contact.patronymic || "";
            editForm.telegram = props.contact.telegram || "";
            editForm.phone = props.contact.phone || "";
            editForm.email = props.contact.email || "";
        }
    }
);

watch(localModelValue, (newValue) => {
    emit("update:modelValue", newValue);
});

const editForm = useForm(
    "put",
    () => route("contacts.update", { contact: props.contact.id }),
    {
        name: "",
        surname: "",
        patronymic: "",
        telegram: "",
        phone: "",
        email: "",
    }
);

function closeModal() {
    localModelValue.value = false;
    editForm.reset();
    editForm.errors = {};
}

const submitEditForm = () => {
    if (!props.contact) return;

    editForm
        .submit()
        .then((response) => {
            emit("updated");
            closeModal();
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
};
</script>

<template>
    <v-dialog
        v-model="localModelValue"
        width="auto"
        max-width="450"
        min-width="350"
    >
        <v-card prepend-icon="mdi-pencil">
            <template v-slot:title>
                <div class="justify-between d-flex align-center">
                    Редактирование контакта
                    <v-btn
                        icon="mdi-close"
                        variant="plain"
                        @click="closeModal"
                    ></v-btn>
                </div>
            </template>

            <template v-slot:text>
                <form
                    class="flex-col d-flex ga-3"
                    @submit.prevent="submitEditForm"
                >
                    <v-text-field
                        label="Фамилия"
                        variant="outlined"
                        v-model="editForm.surname"
                        :hint="editForm.errors.surname"
                        persistent-hint
                        :class="
                            editForm.invalid('surname') ? 'text-danger' : ''
                        "
                    ></v-text-field>
                    <v-text-field
                        label="Имя"
                        variant="outlined"
                        v-model="editForm.name"
                        :hint="editForm.errors.name"
                        persistent-hint
                        :class="editForm.invalid('name') ? 'text-danger' : ''"
                    ></v-text-field>
                    <v-text-field
                        label="Отчество"
                        variant="outlined"
                        v-model="editForm.patronymic"
                        :hint="editForm.errors.patronymic"
                        persistent-hint
                        :class="
                            editForm.invalid('patronymic') ? 'text-danger' : ''
                        "
                    ></v-text-field>
                    <v-text-field
                        label="Телефон"
                        variant="outlined"
                        v-model="editForm.phone"
                        v-maska="'+7(###)###-##-##'"
                        placeholder="+7(999)999-99-99"
                        :hint="editForm.errors.phone"
                        persistent-hint
                        :class="editForm.invalid('phone') ? 'text-danger' : ''"
                    ></v-text-field>
                    <v-text-field
                        label="Email"
                        variant="outlined"
                        v-model="editForm.email"
                        :hint="editForm.errors.email"
                        persistent-hint
                        :class="editForm.invalid('email') ? 'text-danger' : ''"
                    ></v-text-field>
                    <v-text-field
                        label="Телеграм"
                        variant="outlined"
                        v-model="editForm.telegram"
                        :hint="editForm.errors.telegram"
                        persistent-hint
                        :class="
                            editForm.invalid('telegram') ? 'text-danger' : ''
                        "
                    ></v-text-field>
                </form>
            </template>

            <template v-slot:actions>
                <v-btn
                    @click="submitEditForm"
                    color="primary"
                    :disabled="editForm.processing"
                >
                    Сохранить
                </v-btn>
                <v-btn @click="closeModal">Отмена</v-btn>
            </template>
        </v-card>
    </v-dialog>
</template>

<style lang="scss" scoped></style>
