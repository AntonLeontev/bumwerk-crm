<script setup>
const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
    color: {
        type: String,
        required: false,
        default: "#000000",
    },
});
</script>

<template>
    <v-card
        variant="outlined"
        class="hover:shadow-sm transition-shadow"
        :style="{
            borderColor: color,
        }"
    >
        <v-card-title
            class="text-sm font-medium leading-tight"
            :title="lead.name"
        >
            {{ lead.name }}
        </v-card-title>
        <v-card-subtitle class="text-xs">
            <div class="d-flex flex-wrap ga-2">
                <div class="d-flex align-center ga-1">
                    <v-icon size="small" icon="mdi-account"></v-icon>
                    <span>{{
                        lead.contact?.full_name || lead.contact?.name
                    }}</span>
                </div>
                <div class="d-flex align-center ga-1">
                    <v-icon size="small" icon="mdi-account-tie"></v-icon>
                    <span>{{ lead.user?.name }}</span>
                </div>
            </div>
        </v-card-subtitle>
    </v-card>
</template>
