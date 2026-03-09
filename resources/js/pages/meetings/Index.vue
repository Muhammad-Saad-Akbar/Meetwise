<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from "@/components/ui/button/Button.vue";
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue'

const now = ref(new Date())
let timer = null
const page = usePage()

const props = defineProps(
    {
        meetings: Array
    }
)

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date()
    }, 60000)
});

onUnmounted(() => {
    clearInterval(timer)
});

const isJoinDisabled = (meeting) => {

    if (meeting.status === 'ended') {
        return true
    }

    if (!meeting.scheduled_at) {
        return false
    }

    const meetingTime = new Date(meeting.scheduled_at)

    return now.value < meetingTime
};

const capitalize = (text) => {
    return text.charAt(0).toUpperCase() + text.slice(1)
};

const formatDate = (date) => {
    return new Date(date).toLocaleString()
};

const getJoinTooltip = (meeting) => {
    if (meeting.status === 'ended') {
        return 'This meeting has already ended'
    }

    if (meeting.scheduled_at) {
        const meetingTime = new Date(meeting.scheduled_at)

        if (now.value < meetingTime) {
            return 'Meeting has not started yet'
        }
    }

    return 'Click to join meeting'
};

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

                <Link :href="route('meetings.joinForm')" class="ml-12">
                    <Button>Join Meeting</Button>
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
                        <div :title="getJoinTooltip(meeting)">
                            <Link :href="route('meetings.room', meeting.meeting_code)">
                                <Button :disabled="isJoinDisabled(meeting)">
                                    Start Meeting
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <p v-if="meetings.length === 0">
                        No meetings yet.
                    </p>
                </div>
            </div>
    </AppLayout>
</template>


