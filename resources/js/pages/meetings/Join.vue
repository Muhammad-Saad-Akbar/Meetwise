<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import {Head, useForm} from '@inertiajs/vue3';
import { route } from "ziggy-js";

const form = useForm({
    meeting_code: '',
});

const joinMeeting = () => {
    form.post(route('meetings.join'));
}
</script>



<template>
    <Head title="Join Meeting" />

    <AppLayout>
        <div class="p-6 max-w-md mx-auto">
            <h1 class="text-2xl font-bold mb-6">Join Meeting</h1>

            <form @submit.prevent="joinMeeting" class="space-y-4">
                <input
                    v-model="form.meeting_code"
                    type="text"
                    placeholder="Enter Meeting Code"
                    class="w-full border p-2 rounded"
                />
                <div v-if="form.errors.meeting_code" class="text-red-500">{{ form.errors.meeting_code }}</div>

                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full" :disabled="form.processing">
                    Join
                </button>
            </form>
        </div>
    </AppLayout>
</template>


<style scoped>

</style>
