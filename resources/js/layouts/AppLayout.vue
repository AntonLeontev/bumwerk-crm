<script setup>
import Toasts from '@/components/Toasts.vue';
import Profile from '@/components/Profile.vue';
import { useUserStore } from '@/stores/user';
import { ref } from 'vue';

const userStore = useUserStore();
const drawer = ref(window.innerWidth >= 1280);

</script>

<template>
    <v-app>
        <v-app-bar class="px-2 md:px-4">
			<v-app-bar-nav-icon variant="text" @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
			<span class="text-md md:text-3xl">Bumwerk CRM</span>
			<v-spacer></v-spacer>
			<Profile />
        </v-app-bar>

		<v-navigation-drawer v-model="drawer" location="left">
			<v-list>
				<v-list-item>
					<RouterLink :to="{ name: 'home' }" class="d-flex ga-1" :class="$route.name === 'home' ? 'text-primary' : ''">
						<v-icon icon="mdi-home-circle-outline"></v-icon>
						Главная
					</RouterLink>
				</v-list-item>
				<v-list-item v-if="userStore.user.role === 'admin'">
					<RouterLink :to="{ name: 'users' }" class="d-flex ga-1" :class="$route.name === 'users' ? 'text-primary' : ''">
						<v-icon icon="mdi-account-group"></v-icon>
						Пользователи
					</RouterLink>
				</v-list-item>
			</v-list>
		</v-navigation-drawer>

        <v-main>
			<v-container class="justify-center d-flex">
				<slot></slot>
			</v-container>
        </v-main>
    </v-app>

	<Toasts />
</template>
