<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from "@/components/ui/button/Button.vue";
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue'

const now = ref(new Date())
let timer = null
const page = usePage()
const copiedCode = ref(null)

const props = defineProps({
    meetings: Array
})

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date()
    }, 60000)

    // Auto-open waiting room in new tab when an instant meeting is just created
    if (page.props.flash?.instantMeetingCode) {
        window.open(route('meetings.waitingRoom', page.props.flash.instantMeetingCode), '_blank')
    }
});

onUnmounted(() => {
    clearInterval(timer)
});

const isJoinDisabled = (meeting) => {
    if (meeting.status === 'ended') return true
    if (!meeting.scheduled_at) return false
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
            return 'You can start this meeting when the scheduled time arrives'
        }
    }
    return 'Click to Start meeting';
};

const getMeetingLink = (meeting) => {
    return `${window.location.origin}/join/${meeting.meeting_code}`
}

const buildShareableText = (meeting) => {
    const link = getMeetingLink(meeting)
    let text = `You're invited to join a MeetWise meeting!\n\n`
    text += `📌 Title: ${meeting.title}\n`
    text += `📋 Type: ${capitalize(meeting.type)}\n`
    if (meeting.scheduled_at) {
        text += `🗓 Scheduled: ${formatDate(meeting.scheduled_at)}\n`
    }
    text += `\nClick to join:\n${link}`
    return text
}

const copyMeetingLink = async (meeting) => {
    try {
        await navigator.clipboard.writeText(buildShareableText(meeting))
        copiedCode.value = meeting.meeting_code
        setTimeout(() => {
            copiedCode.value = null
        }, 2000)
    } catch (err) {
        console.error('Copy failed:', err)
    }
}

const deleteMeeting = (meeting) => {
    if (!confirm(`Are you sure you want to delete "${meeting.title}"? This action cannot be undone.`)) {
        return
    }
    router.delete(route('meetings.destroy', meeting.meeting_code))
}
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
                    <h2 class="text-lg font-semibold">{{ meeting.title }}</h2>
                    <p>Type: {{ capitalize(meeting.type) }}</p>
                    <p>Status: {{ capitalize(meeting.status) }}</p>

                    <p v-if="meeting.scheduled_at">
                        Scheduled: {{ formatDate(meeting.scheduled_at) }}
                    </p>

                    <!-- Action buttons row -->
                    <div class="mt-3 flex items-center gap-3 flex-wrap">

                        <!-- Copy Link button -->
                        <button
                            @click="copyMeetingLink(meeting)"
                            :class="copiedCode === meeting.meeting_code
                                ? 'bg-green-600 hover:bg-green-700 text-white'
                                : 'bg-gray-200 hover:bg-gray-300 text-gray-700'"
                            class="px-3 py-2 rounded text-sm font-medium transition flex items-center gap-1.5"
                            :title="copiedCode === meeting.meeting_code ? 'Copied!' : 'Copy meeting link'"
                        >
                            <svg v-if="copiedCode !== meeting.meeting_code" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ copiedCode === meeting.meeting_code ? 'Copied!' : 'Copy Link' }}
                        </button>

                        <!-- Edit button — scheduled + upcoming meetings only -->
                        <Link
                            v-if="meeting.type === 'scheduled' && meeting.status === 'upcoming'"
                            :href="route('meetings.edit', meeting.meeting_code)"
                            class="px-3 py-2 rounded text-sm font-medium bg-yellow-100 hover:bg-yellow-200 text-yellow-800 transition flex items-center gap-1.5"
                            title="Edit this meeting"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </Link>

                        <!-- Delete button — scheduled meetings only, not while live -->
                        <button
                            v-if="meeting.type === 'scheduled' && meeting.status !== 'live'"
                            @click="deleteMeeting(meeting)"
                            class="px-3 py-2 rounded text-sm font-medium bg-red-100 hover:bg-red-200 text-red-700 transition flex items-center gap-1.5"
                            title="Delete this meeting"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>

                        <!-- Start Meeting button -->
                        <div :title="getJoinTooltip(meeting)">
                            <template v-if="!isJoinDisabled(meeting)">
                                <a :href="route('meetings.waitingRoom', meeting.meeting_code)" target="_blank" rel="noopener">
                                    <Button>Start Meeting</Button>
                                </a>
                            </template>
                            <template v-else>
                                <Button disabled>Start Meeting</Button>
                            </template>
                        </div>

                    </div>
                </div>

                <p v-if="meetings.length === 0">No meetings yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
