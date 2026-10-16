<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
    meeting: Object,
    recordings: Array,
})

const formatDate = (date) => new Date(date).toLocaleString()

const formatDuration = (seconds) => {
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    const s = seconds % 60
    const pad = (n) => String(n).padStart(2, '0')
    return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`
}

const formatSize = (bytes) => {
    if (bytes >= 1024 * 1024 * 1024) return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB'
    if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
    return Math.max(1, Math.round(bytes / 1024)) + ' KB'
}

// Browser-made recordings have no duration in their header, so the player
// reports "Infinity" and the seek bar doesn't work. Jumping to the very end
// once makes the browser work out the real length; then we go back to 0.
const fixInfiniteDuration = (event) => {
    const video = event.target
    if (video.duration !== Infinity) return

    const reset = () => {
        video.removeEventListener('timeupdate', reset)
        video.currentTime = 0
    }
    video.addEventListener('timeupdate', reset)
    video.currentTime = 1e101
}

const deleteRecording = (recording, number) => {
    if (!confirm(`Delete Recording ${number}? This action cannot be undone.`)) {
        return
    }
    router.delete(route('recordings.destroy', recording.id))
}
</script>

<template>
    <Head :title="`Recordings — ${meeting.title}`" />

    <AppLayout>
        <div class="p-6">
            <Link :href="route('meetings.index')" class="text-sm underline">
                ← Back to My Meetings
            </Link>

            <h1 class="text-2xl font-bold mt-3">Recordings</h1>
            <p class="text-gray-500 mb-6">{{ meeting.title }}</p>

            <div class="space-y-6">
                <div
                    v-for="(recording, index) in recordings"
                    :key="recording.id"
                    class="border p-4 rounded shadow"
                >
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                        <div>
                            <h2 class="text-lg font-semibold">
                                Recording {{ recordings.length - index }}
                            </h2>
                            <p class="text-sm text-gray-500">
                                {{ formatDate(recording.created_at) }}
                                · {{ formatDuration(recording.duration) }}
                                · {{ formatSize(recording.size) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a
                                :href="route('recordings.download', recording.id)"
                                class="px-3 py-2 rounded text-sm font-medium bg-gray-200 hover:bg-gray-300 text-gray-700 transition"
                            >
                                Download
                            </a>
                            <button
                                @click="deleteRecording(recording, recordings.length - index)"
                                class="px-3 py-2 rounded text-sm font-medium bg-red-100 hover:bg-red-200 text-red-700 transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <video
                        :src="route('recordings.stream', recording.id)"
                        controls
                        preload="metadata"
                        class="w-full max-w-3xl rounded bg-black"
                        @loadedmetadata="fixInfiniteDuration"
                    ></video>
                </div>

                <p v-if="recordings.length === 0">No recordings for this meeting yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
