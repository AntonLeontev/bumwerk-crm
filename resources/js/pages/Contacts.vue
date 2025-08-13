<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import H1 from "@/components/H1.vue";
import CrudPage from "@/components/CrudPage.vue";
import DataTablePagination from "@/components/DataTablePagination.vue";
import { ref, reactive, watch, onMounted } from "vue";
import { useForm } from "laravel-precognition-vue";
import axios from "axios";
import { vMaska } from "maska/vue";

import { useUserStore } from "@/stores/user";
const userStore = useUserStore();

import { useToastsStore } from "@/stores/toasts";
const toastsStore = useToastsStore();

const headers = [
    {
        title: "Имя",
        align: "start",
        key: "title",
        sortable: false,
        width: "20%",
    },
    {
        title: "Телефон",
        align: "start",
        key: "phone",
        sortable: false,
        width: "20%",
    },
    {
        title: "Email",
        align: "start",
        key: "email",
        sortable: false,
        width: "20%",
    },
    {
        title: "Телеграм",
        align: "start",
        key: "telegram",
        sortable: false,
        width: "15%",
    },
    { title: "Действия", key: "actions", sortable: false, align: "end" },
];
const serverItems = reactive([]);
const loading = ref(false);

const itemsPerPage = ref(localStorage.getItem("contacts:itemsPerPage") || 10);
const totalItems = ref(0);
const page = ref(1);
const sortBy = ref(null);
const search = ref(null);

watch(itemsPerPage, () => {
    localStorage.setItem("contacts:itemsPerPage", itemsPerPage.value);
    loadItems({
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        search: search.value,
    });
});
watch(page, () => {
    loadItems({
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        search: search.value,
    });
});
watch(search, () => {
    loadItems({
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        search: search.value,
    });
});

function loadItems({ page, itemsPerPage, sortBy, search }) {
    loading.value = true;

    axios
        .get(route("contacts.index"), {
            params: {
                page,
                items_per_page: itemsPerPage,
                sort: sortBy,
                search: search,
            },
        })
        .then((response) => {
            serverItems.splice(0, serverItems.length, ...response.data.data);
            totalItems.value = response.data.total;
        })
        .finally(() => {
            loading.value = false;
        });
}

const creating = ref(false);
const deleting = ref(false);
const deletingItem = ref(null);

function openCreateModal(item) {
    creating.value = true;
}

function openDeleteModal(item) {
    deleting.value = true;
    deletingItem.value = item;
}

function deleteUser() {
    axios
        .delete(
            route("contacts.destroy", {
                id: deletingItem.value.id,
            })
        )
        .then((response) => {
            loadItems({
                page: page.value,
                itemsPerPage: itemsPerPage.value,
                sortBy: sortBy.value,
                search: search.value,
            });
        })
		.catch((error) => {
			toastsStore.handleResponseError(error);
		})
        .finally(() => {
            deleting.value = false;
        });
}

function closeCreateForm() {
    creating.value = false;
    createForm.reset();
    createForm.errors = {};
}

const createForm = useForm("post", route("contacts.store"), {
    name: "",
    surname: "",
    patronymic: "",
    telegram: "",
    phone: "",
    email: "",
});

const submitCreateForm = () =>
    createForm
        .submit()
        .then((response) => {
            loadItems({
                page: page.value,
                itemsPerPage: itemsPerPage.value,
                sortBy: sortBy.value,
                search: search.value,
            });
            closeCreateForm();
        })
        .catch((error) => {
            toastsStore.handleResponseError(error);
        });
</script>

<template>
    <AppLayout>
        <CrudPage>
            <template v-slot:header>
                <div class="justify-between d-flex">
                    <H1>Контакты</H1>

                    <div class="d-flex ga-2">
                        <v-btn
                            prepend-icon="mdi-account-plus"
                            @click="openCreateModal"
                            color="primary"
                            >Создать контакт</v-btn
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
                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="serverItems"
                    :items-length="totalItems"
                    :loading="loading"
                    item-value="name"
                    @update:options="loadItems"
                    density="comfortable"
                >
                    <template v-slot:item.name="{ item }">
                        {{ item.name }}

                        <v-icon
                            color="danger"
                            class="ms-2"
                            size="small"
                            icon="mdi-email-alert-outline"
                            v-if="!item.email_verified"
                            v-tooltip="'Пользователь еще не активирован'"
                        />
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <!-- <v-icon
                            class="me-2"
                            size="small"
                            @click="sendInvite(item)"
                            v-if="!item.email_verified"
                            color="primary"
                            title="Отправить еще одно приглашение"
                        >
                            mdi-email-arrow-right-outline
                        </v-icon> -->
                        <v-btn
                            icon="mdi-trash-can-outline"
                            variant="plain"
                            size="small"
                            color="error"
                            title="Удалить пользователя"
                            @click="openDeleteModal(item, $event)"
                            v-if="userStore.user?.id !== item.id"
                        >
                        </v-btn>
                    </template>

                    <template v-slot:no-data>
                        <p class="text-center text-body-1">
                            Контактов пока нет
                        </p>
                        <v-btn
                            class="mt-3"
                            color="primary"
                            @click="openCreateModal"
                        >
                            Создать контакт
                        </v-btn>
                    </template>

                    <template v-slot:bottom>
                        <DataTablePagination
                            :itemsPerPage="itemsPerPage"
                            :totalItems="totalItems"
                            :page="page"
                            @update:itemsPerPage="itemsPerPage = $event"
                            @update:page="page = $event"
                        />
                    </template>
                </v-data-table-server>

                <!-- ------ -->
                <!-- Modals -->
                <!-- ------ -->

                <v-dialog
                    v-model="creating"
                    width="auto"
                    max-width="450"
                    min-width="350"
                >
                    <v-card prepend-icon="mdi-plus">
                        <template v-slot:title>
                            <div class="justify-between d-flex align-center">
                                Создание контакта
                                <v-btn
                                    icon="mdi-close"
                                    variant="plain"
                                    @click="closeCreateForm"
                                ></v-btn>
                            </div>
                        </template>

                        <template v-slot:text>
                            <form class="flex-col d-flex ga-3">
                                <v-text-field
                                    label="Фамилия"
                                    variant="outlined"
                                    v-model="createForm.surname"
                                    :hint="createForm.errors.surname"
                                    persistent-hint
                                    :class="
                                        createForm.invalid('surname')
                                            ? 'text-danger'
                                            : ''
                                    "
                                ></v-text-field>
                                <v-text-field
                                    label="Имя"
                                    variant="outlined"
                                    v-model="createForm.name"
                                    :hint="createForm.errors.name"
                                    persistent-hint
                                    :class="
                                        createForm.invalid('name')
                                            ? 'text-danger'
                                            : ''
                                    "
                                ></v-text-field>
                                <v-text-field
                                    label="Отчество"
                                    variant="outlined"
                                    v-model="createForm.patronymic"
                                    :hint="createForm.errors.patronymic"
                                    persistent-hint
                                    :class="
                                        createForm.invalid('patronymic')
                                            ? 'text-danger'
                                            : ''
                                    "
                                ></v-text-field>
                                <v-text-field
                                    label="Телефон"
                                    variant="outlined"
                                    v-model="createForm.phone"
                                    v-maska="'+7(###)###-##-##'"
                                    placeholder="+7(999)999-99-99"
                                    :hint="createForm.errors.phone"
                                    persistent-hint
                                    :class="
                                        createForm.invalid('phone')
                                            ? 'text-danger'
                                            : ''
                                    "
                                ></v-text-field>
                                <v-text-field
                                    label="Email"
                                    variant="outlined"
                                    v-model="createForm.email"
                                    :hint="createForm.errors.email"
                                    persistent-hint
                                    :class="
                                        createForm.invalid('email')
                                            ? 'text-danger'
                                            : ''
                                    "
                                ></v-text-field>
                                <v-text-field
                                    label="Телеграм"
                                    variant="outlined"
                                    v-model="createForm.telegram"
                                    :hint="createForm.errors.telegram"
                                    persistent-hint
                                    :class="
                                        createForm.invalid('telegram')
                                            ? 'text-danger'
                                            : ''
                                    "
                                ></v-text-field>
                            </form>
                        </template>

                        <template v-slot:actions>
                            <v-btn
                                @click="submitCreateForm"
                                color="primary"
                                :disabled="createForm.processing"
                                >Создать</v-btn
                            >
                            <v-btn @click="closeCreateForm">Отмена</v-btn>
                        </template>
                    </v-card>
                </v-dialog>

                <v-dialog v-model="deleting" width="auto" max-width="400">
                    <v-card
                        prepend-icon="mdi-delete"
                        :text="
                            'Удалить пользователя ' + deletingItem.title + '?'
                        "
                    >
                        <template v-slot:title>
                            <div class="justify-between d-flex align-center">
                                Удаление
                                <v-btn
                                    icon="mdi-close"
                                    variant="plain"
                                    @click="deleting = false"
                                ></v-btn>
                            </div>
                        </template>

                        <template v-slot:actions>
                            <v-btn @click="deleteUser" color="error"
                                >Удалить</v-btn
                            >
                            <v-btn @click="deleting = false">Отмена</v-btn>
                        </template>
                    </v-card>
                </v-dialog>
            </template>
        </CrudPage>
    </AppLayout>
</template>

<style lang="scss" scoped></style>
