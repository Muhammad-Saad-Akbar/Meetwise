<script setup>
import { Head, router } from '@inertiajs/vue3'
import { useNavigationGuard } from '@/composables/useNavigationGuard'

const props = defineProps({
    meeting: Object
})

// Disable browser Back, Forward, and Reload buttons
useNavigationGuard()

const goToMeetings = () => {
    router.visit(route('meetings.index'))
}
</script>

<template>
    <Head title="Meeting Ended" />

    <div class="min-h-screen bg-gray-900 text-white flex items-center justify-center p-6">
        <div class="text-center max-w-md w-full">

            <!-- Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z" />
                    </svg>
                </div>
            </div>

            <!-- Heading -->
            <h1 class="text-3xl font-bold mb-2">Meeting Ended</h1>

            <!-- Meeting title -->
            <p class="text-gray-300 text-lg font-medium mb-2">{{ meeting.title }}</p>

            <!-- Subtext -->
            <p class="text-gray-500 text-sm mb-10">
                Thank you for using MeetWise. We hope the meeting was productive!
            </p>

            <!-- Back to Meetings button -->
            <button
                @click="goToMeetings"
                class="px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-semibold transition text-sm"
            >
                Back to Meetings
            </button>

        </div>
    </div>
</template>
