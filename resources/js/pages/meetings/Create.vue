<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js'

const form = useForm({
    title: '',
    type: 'instant',
    scheduled_at: null,
});
const submitMeeting = () => {
    form.post(route('meetings.store'))
}
</script>

<template>
    <Head title="Create Meeting" />

    <AppLayout>
        <div class="p-6 max-w-xl">
            <h1 class="text-2xl font-bold mb-6">Create Meeting</h1>

            <form @submit.prevent="submitMeeting" class="space-y-14">

                <!-- Title -->
                <div>
                    <label class="block mb-1 font-medium">Meeting Title</label>
                    <input v-model="form.title" type="text" class="w-full border p-2 rounded" />
                    <div v-if="form.errors.title" class="text-red-500">{{ form.errors.title }}</div>

                </div>

                <!-- Type -->
                <div>
                    <label class="block mb-1 font-medium">Meeting Type</label>
                    <select v-model="form.type" class="w-full border p-2 rounded">
                        <option value="instant">Instant</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>

                <!-- Scheduled Date -->
                <div v-if="form.type === 'scheduled'">
                    <label class="block mb-1 font-medium">Schedule Date & Time</label>
                    <input v-model="form.scheduled_at" type="datetime-local" class="w-full border p-2 rounded" />
                    <div v-if="form.errors.scheduled_at">{{ form.errors.scheduled_at }}</div>
                </div>

                <!-- Submit -->
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="form.processing">
                    Create Meeting
                </button>
            </form>
        </div>
    </AppLayout>
</template>


<style scoped>

</style>
