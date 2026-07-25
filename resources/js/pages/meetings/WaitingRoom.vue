<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'
import { useNavigationGuard } from '@/composables/useNavigationGuard'

const props = defineProps({
    meeting: Object
})

// Disable browser Back, Forward, and Reload buttons
useNavigationGuard()

const videoRef = ref(null)
let previewStream = null
const isCameraOff = ref(false)
const isMicMuted = ref(false)
const hasPermissionError = ref(false)

const startPreview = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        previewStream = stream
        if (videoRef.value) {
            videoRef.value.srcObject = stream
        }
    } catch (err) {
        console.error('Camera/mic permission error:', err)
        hasPermissionError.value = true
    }
}

const toggleCamera = () => {
    if (!previewStream) return
    const videoTrack = previewStream.getVideoTracks()[0]
    if (!videoTrack) return
    videoTrack.enabled = !videoTrack.enabled
    isCameraOff.value = !videoTrack.enabled
}

const toggleMic = () => {
    if (!previewStream) return
    const audioTrack = previewStream.getAudioTracks()[0]
    if (!audioTrack) return
    audioTrack.enabled = !audioTrack.enabled
    isMicMuted.value = !audioTrack.enabled
}

const joinMeeting = () => {
    sessionStorage.setItem('meetingJoinPrefs', JSON.stringify({
        cameraOff: isCameraOff.value,
        micMuted: isMicMuted.value
    }))

    if (previewStream) {
        previewStream.getTracks().forEach(track => track.stop())
    }

    router.visit(route('meetings.room', props.meeting.meeting_code))
}

const cancel = () => {
    if (previewStream) {
        previewStream.getTracks().forEach(track => track.stop())
    }
    // Try to close this tab (works because it was opened via target="_blank")
    // Falls back to navigating to meetings index if browser blocks window.close()
    window.close()
    router.visit(route('meetings.index'))
}

onMounted(() => {
    startPreview()
})

onUnmounted(() => {
    if (previewStream) {
        previewStream.getTracks().forEach(track => track.stop())
    }
})
</script>

<template>
    <Head title="Waiting Room" />

    <div class="min-h-screen bg-gray-900 text-white flex items-center justify-center p-6">
        <div class="w-full max-w-xl">

            <!-- Meeting Info -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold">{{ meeting.title }}</h1>
                <p class="text-gray-400 text-sm mt-1">Host: {{ meeting.host.name }}</p>
            </div>

            <!-- Camera Preview Box -->
            <div class="relative bg-gray-800 rounded-2xl overflow-hidden aspect-video mb-4 shadow-lg">

                <!-- Live video feed -->
                <video
                    v-show="!isCameraOff && !hasPermissionError"
                    ref="videoRef"
                    autoplay
                    muted
                    playsinline
                    class="w-full h-full object-cover"
                    style="transform: scaleX(-1);"
                />

                <!-- Camera is off -->
                <div
                    v-show="isCameraOff && !hasPermissionError"
                    class="absolute inset-0 flex flex-col items-center justify-center bg-gray-800 text-gray-400"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3l18 18" />
                    </svg>
                    <p class="text-sm">Camera is off</p>
                </div>

                <!-- Permission error -->
                <div
                    v-show="hasPermissionError"
                    class="absolute inset-0 flex flex-col items-center justify-center bg-gray-800 px-6 text-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-sm text-yellow-400 font-medium">Camera/mic access denied</p>
                    <p class="text-xs text-gray-500 mt-1">You can still join, but others won't see or hear you.</p>
                </div>
            </div>

            <!-- Mic & Camera Toggles -->
            <div class="flex justify-center gap-4 mb-8">

                <!-- Mic Button -->
                <button
                    @click="toggleMic"
                    :disabled="hasPermissionError"
                    :class="isMicMuted ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600'"
                    class="p-3 rounded-full text-white transition disabled:opacity-40 disabled:cursor-not-allowed"
                    :title="isMicMuted ? 'Unmute mic' : 'Mute mic'"
                >
                    <svg v-if="!isMicMuted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M12 3a4 4 0 014 4v4a4 4 0 01-8 0V7a4 4 0 014-4z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                    </svg>
                </button>

                <!-- Camera Button -->
                <button
                    @click="toggleCamera"
                    :disabled="hasPermissionError"
                    :class="isCameraOff ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600'"
                    class="p-3 rounded-full text-white transition disabled:opacity-40 disabled:cursor-not-allowed"
                    :title="isCameraOff ? 'Turn camera on' : 'Turn camera off'"
                >
                    <svg v-if="!isCameraOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                    </svg>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-center gap-4">
                <button
                    @click="cancel"
                    class="px-6 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-white transition text-sm font-medium"
                >
                    Cancel
                </button>
                <button
                    @click="joinMeeting"
                    class="px-8 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition font-semibold text-sm"
                >
                    Join Meeting
                </button>
            </div>

        </div>
    </div>
</template>
