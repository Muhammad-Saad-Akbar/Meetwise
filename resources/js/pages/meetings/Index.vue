<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from "@/components/ui/button/Button.vue";
import { usePage } from '@inertiajs/vue3'

const props = defineProps(
    {
        meetings: Array
    }
)
const capitalize = (text) => {
    return text.charAt(0).toUpperCase() + text.slice(1)
}

const formatDate = (date) => {
    return new Date(date).toLocaleString()
}
const page = usePage()
</script>

<template>
    <Head title="Meeting" />

    <AppLayout>
        <div v-if="page.flash.toast" class="toast">
            {{ page.flash.toast.message }}
        </div>
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-4">My Meetings</h1>

                <Link :href="route('meetings.create')">
                    <Button>+ Create Meeting</Button>
                </Link>

                <div class="mt-6 space-y-4">
                    <div
                        v-for="meeting in meetings"
                        :key="meeting.id"
                        class="border p-4 rounded shadow"
                    >
                        <h2 class="text-lg font-semibold">
                            {{ meeting.title }}
                        </h2>

                        <p>Type: {{ capitalize(meeting.type) }}</p>
                        <p>Status: {{ capitalize(meeting.status) }}</p>

                        <p v-if="meeting.scheduled_at">
                            Scheduled: {{ formatDate(meeting.scheduled_at) }}
                        </p>

                        <p class="text-sm text-gray-500">
                            Code: {{ meeting.meeting_code }}
                        </p>
                    </div>

                    <p v-if="meetings.length === 0">
                        No meetings yet.
                    </p>
                </div>
            </div>
    </AppLayout>
</template>


