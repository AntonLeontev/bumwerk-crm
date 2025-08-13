<script setup>
const emit = defineEmits(['open']);
const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
    color: {
        type: String,
        required: false,
        default: "#cccccc",
    },
});
</script>

<template>
    <v-card
        variant="outlined"
        class="hover:shadow-sm transition-shadow shrink-0"
        :style="{
            borderColor: color,
        }"
		:title="lead.title"
        @click="emit('open', lead)"
        hover
    >
        
		<v-card-text>
			<div class="mb-3 text-lg text-grey" v-if="lead.amount">
				{{ lead.amount }} ₽
			</div>
            <div class="d-flex ga-2 justify-space-between">
                <div class="d-flex align-center ga-1" title="Контакт">
                    <v-icon size="small" icon="mdi-account"></v-icon>
                    <span>{{
                        lead.contact?.full_name || `${lead.contact?.name} ${lead.contact?.surname}`
                    }}</span>
                </div>
                <div class="d-flex align-center ga-1" v-if="lead.user" title="Ответственный менеджер">
                    <v-icon size="small" icon="mdi-account-tie"></v-icon>
                    <span>{{ lead.user?.name }}</span>
                </div>
            </div>
		</v-card-text>
    </v-card>
</template>
