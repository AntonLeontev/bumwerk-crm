import Login from "@/pages/auth/Login.vue";

export default [
    {
        path: "/",
        component: () => import("@/pages/Home.vue"),
        name: "home",
        meta: { auth: true, title: "Главная" },
    },
    {
        path: "/profile",
        component: () => import("@/pages/Profile.vue"),
        name: "profile",
        meta: { auth: true, title: "Профиль" },
    },
    {
        path: "/userlist",
        component: () => import("@/pages/Users.vue"),
        name: "users",
        meta: { auth: true, title: "Пользователи" },
    },
    {
        path: "/contacts-list",
        component: () => import("@/pages/Contacts.vue"),
        name: "contacts",
        meta: { auth: true, title: "Контакты" },
    },
    { path: "/login", component: Login, name: "login" },
    {
        path: "/forgot-password",
        component: () => import("@/pages/auth/ForgotPassword.vue"),
        name: "forgot-password",
    },
    {
        path: "/create-password/:email/:token",
        component: () => import("@/pages/auth/CreatePassword.vue"),
        name: "create-password",
    },
    {
        path: "/reset-password",
        component: () => import("@/pages/auth/ResetPassword.vue"),
        name: "reset-password",
    },
    {
        path: "/:pathMatch(.*)*",
        name: "404",
        component: () => import("@/pages/404.vue"),
    },
];
