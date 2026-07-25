<script setup>
import {Head, router} from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AgoraRTC from "agora-rtc-sdk-ng"
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { useNavigationGuard } from '@/composables/useNavigationGuard'

const page = usePage()
const props = defineProps({
    meeting: Object
})

//   RTC Setup
const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
const localTracks = ref([]);
const isScreenSharing = ref(false);
const isMicMuted = ref(false)
const isCameraOff = ref(false)
const statusPollInterval = ref(null)

//  Chat State
const messages = ref([])
const newMessage = ref('')
const chatBody = ref(null)
const isSending = ref(false)

//  UI State
const showChat = ref(false);
const showParticipants = ref(false);
const isHost = page.props.auth.user.id === props.meeting.host_id;
let savedCameraMediaTrack = null

let echo = null

// Disable browser Back, Forward, and Reload buttons
useNavigationGuard()

const toggleChat = () => {
    showChat.value = !showChat.value
    showParticipants.value = false
    if (showChat.value) nextTick(() => scrollToBottom())
}

const toggleParticipants = () => {
    showParticipants.value = !showParticipants.value
    showChat.value = false
}

// --- Mic Toggle ---
const toggleMic = async () => {
    if (!localTracks.value[0]) return
    await localTracks.value[0].setMuted(!isMicMuted.value)
    isMicMuted.value = !isMicMuted.value
}

// --- Camera Toggle ---
const toggleCamera = async () => {
    if (!localTracks.value[1]) return

    if (isCameraOff.value) {
        // Turning ON: check if the native track is dead (happens after screen share)
        const nativeTrack = localTracks.value[1].getMediaStreamTrack()
        if (!nativeTrack || nativeTrack.readyState === 'ended') {
            // Native track is dead — get a fresh camera track
            const stream = await navigator.mediaDevices.getUserMedia({ video: true })
            const freshTrack = stream.getVideoTracks()[0]
            await localTracks.value[1].replaceTrack(freshTrack, false)
        }
        await localTracks.value[1].setEnabled(true)
        localTracks.value[1].play("local-player")
    } else {
        // Turning OFF
        await localTracks.value[1].setEnabled(false)
    }

    isCameraOff.value = !isCameraOff.value
}

// --- RTC Cleanup (shared by leave and end) ---
const cleanupRTC = async () => {
    if (statusPollInterval.value) {
        clearInterval(statusPollInterval.value)
    }
    try {
        for (const track of localTracks.value) {
            track.close()
        }
        await client.leave()
    } catch (e) {
        console.error('RTC cleanup error:', e)
    }
}

// --- Leave Meeting (participants) ---
const leaveRoom = async () => {
    await cleanupRTC()
    router.visit(route('meetings.index'))
}

// --- End Meeting (host only) ---
const endMeeting = async () => {
    try {
        await axios.post(route('meetings.end', props.meeting.meeting_code))
    } catch (e) {
        console.error('Failed to end meeting on backend:', e)
    }
    await cleanupRTC()
    router.visit(route('meetings.ended', props.meeting.meeting_code))
}

// --- Poll status (participants only — detect when host ends meeting) ---
const startStatusPolling = () => {
    statusPollInterval.value = setInterval(async () => {
        try {
            const res = await axios.get(route('meetings.checkStatus', props.meeting.meeting_code))
            if (res.data.status === 'ended') {
                await cleanupRTC()
                router.visit(route('meetings.ended', props.meeting.meeting_code))
            }
        } catch (e) {
            console.error('Status poll error:', e)
        }
    }, 10000)
}

// Stop Screen Share
const stopScreenShare = async () => {
    try {
        const screenTrack = localTracks.value[1].getMediaStreamTrack()
        screenTrack.stop()

        if (savedCameraMediaTrack) {
            await localTracks.value[1].replaceTrack(savedCameraMediaTrack, false)
            savedCameraMediaTrack = null
        }

        if (isCameraOff.value) {
            // Camera was OFF → disable and rebind player
            await localTracks.value[1].setEnabled(false)
            localTracks.value[1].play("local-player")
        } else {
            // Camera was ON → restore and play normally
            localTracks.value[1].play("local-player")
        }

        document.getElementById("local-player").classList.remove("screen-sharing")
        isScreenSharing.value = false
    } catch (err) {
        console.error("Stop screen share error:", err)
    }
}

const toggleScreenShare = async () => {
    if (!isScreenSharing.value) {
        try {
            // Save the current camera track BEFORE replacing it
            savedCameraMediaTrack = localTracks.value[1].getMediaStreamTrack()

            const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true })
            const screenMediaTrack = screenStream.getVideoTracks()[0]

            await localTracks.value[1].replaceTrack(screenMediaTrack, false) // false = keep camera track alive in background
            localTracks.value[1].play("local-player")

            document.getElementById("local-player").classList.add("screen-sharing")
            isScreenSharing.value = true

            screenMediaTrack.onended = async () => {
                await stopScreenShare()
            }
        } catch (err) {
            console.warn("Screen share cancelled or failed:", err)
            isScreenSharing.value = false
        }
    } else {
        await stopScreenShare()
    }
}

const scrollToBottom = () => {
    if (chatBody.value) {
        chatBody.value.scrollTop = chatBody.value.scrollHeight
    }
}

const loadMessages = async () => {
    try {
        const res = await axios.get(`/meetings/${props.meeting.meeting_code}/messages`)
        messages.value = res.data
        await nextTick(() => scrollToBottom())
    } catch (err) {
        console.error("Load messages error:", err)
    }
}

const sendMessage = async () => {
    const text = newMessage.value.trim()
    if (!text || isSending.value) return

    isSending.value = true
    newMessage.value = ''

    try {
        const res = await axios.post(
            `/meetings/${props.meeting.meeting_code}/messages`,
            { body: text }
        )
        messages.value.push(res.data)
        await nextTick(() => scrollToBottom())
    } catch (err) {
        console.error("Send message error:", err)
        newMessage.value = text
    } finally {
        isSending.value = false
    }
}

const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
    }
}

const initEcho = () => {
    window.Pusher = Pusher

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
    })

    // Listen for messages from other users
    echo.channel(`meeting.${props.meeting.meeting_code}`)
        .listen('MessageSent', (e) => {
            messages.value.push({
                id: e.id,
                body: e.body,
                sender: e.sender,
                time: e.time,
                isSelf: false,
            })
            nextTick(() => scrollToBottom())
        })
}

onMounted(async () => {

    // Read mic/camera preferences set by WaitingRoom
    const joinPrefsRaw = sessionStorage.getItem('meetingJoinPrefs')
    if (joinPrefsRaw) {
        try {
            const joinPrefs = JSON.parse(joinPrefsRaw)
            if (joinPrefs.cameraOff) isCameraOff.value = true
            if (joinPrefs.micMuted) isMicMuted.value = true
        } catch (e) { /* ignore parse errors */ }
        sessionStorage.removeItem('meetingJoinPrefs')
    }

    try {
        const res = await axios.post('/agora/token', {
            channel: props.meeting.meeting_code
        })

        const { token, appId, channel, uid } = res.data

        //  Join Agora channel
        await client.join(appId, channel, token, uid)
        console.log("Joined Agora")

        // Host → mark meeting as live
        if (isHost) {
            try {
                await axios.post(route('meetings.start', props.meeting.meeting_code))
            } catch (e) {
                console.error('Failed to mark meeting as live:', e)
            }
        }

        // Participant → start polling
        if (!isHost) {
            startStatusPolling()
        }

        //  Create Mic + Camera
        const tracks = await AgoraRTC.createMicrophoneAndCameraTracks()
        localTracks.value = tracks

        // Apply waiting room preferences
        if (isMicMuted.value) {
            await tracks[0].setMuted(true)
        }

        if (isCameraOff.value) {
            await tracks[1].setEnabled(false)
        } else {
            tracks[1].play("local-player")
        }

        await client.publish(tracks)
        console.log("Published local tracks")

         //  Handle remote users
        client.on("user-published", async (user, mediaType) => {

            await client.subscribe(user, mediaType)

            if (mediaType === "video") {
                const player = document.createElement("div")
                player.id = String(user.uid)
                player.style.width = "300px"
                player.style.height = "200px"

                document.getElementById("remote-container").appendChild(player)

                user.videoTrack.play(player.id)
            }

            if (mediaType === "audio") {
                user.audioTrack.play()
            }
        })
    } catch (error) {
        console.error("Agora Error:", error)
    }

    initEcho()
    await loadMessages()
});

onUnmounted(async () => {
    // Disconnect Laravel Echo (real-time chat)
    echo?.disconnect()

    if (statusPollInterval.value) {
        clearInterval(statusPollInterval.value)
    }
    try {
        for (const track of localTracks.value) {
            track.close()
        }
        await client.leave()
    } catch (e) {
        // Silently fail on unmount
    }
})
</script>

<template>
    <Head title="Meeting Room" />

    <div class="h-screen flex flex-col bg-gray-900 text-white">

        <!-- Header -->
        <div class="p-4 border-b border-gray-700 flex justify-between">
            <h1 class="text-lg font-semibold">
                {{ meeting.title }}
            </h1>
            <p class="text-sm text-gray-400">
                Host: {{ meeting.host.name }}
            </p>
        </div>

        <!-- Main Content -->
        <div class="flex flex-1 overflow-hidden">

            <!-- Video Area -->
            <div class="flex flex-col items-center justify-center gap-4 p-4">
                <div id="local-player" class="w-260 h-120 bg-black rounded"></div>
                <div id="remote-container" class="flex gap-4 flex-wrap justify-center"></div>
            </div>

            <!-- Sidebar -->
            <div v-if="showChat || showParticipants"
                 class="w-80 border-l border-gray-700 bg-gray-800 flex flex-col">

                <!-- Participants -->
                <div v-if="showParticipants" class="p-4 flex-1 overflow-y-auto">
                    <h2 class="font-semibold mb-4">Participants</h2>
                    <ul class="space-y-2">
                        <li class="bg-gray-700 p-2 rounded">User 1</li>
                        <li class="bg-gray-700 p-2 rounded">User 2</li>
                    </ul>
                </div>

                <!-- Chat -->
                <div v-if="showChat" class="flex flex-col flex-1 overflow-hidden">

                    <div class="p-4 border-b border-gray-700">
                        <h2 class="font-semibold">Chat</h2>
                    </div>

                    <div ref="chatBody" class="flex-1 p-4 overflow-y-auto space-y-3">

                        <p v-if="messages.length === 0" class="text-gray-500 text-sm text-center mt-4">
                            No messages yet. Say hi! 👋
                        </p>

                        <div v-for="msg in messages" :key="msg.id" :class="['flex flex-col', msg.isSelf ? 'items-end' : 'items-start']">
                            <div :class="[
                                'px-3 py-2 rounded-lg text-sm max-w-[90%] break-words whitespace-pre-wrap',
                                msg.isSelf
                                    ? 'bg-blue-600 text-white rounded-br-none'
                                    : 'bg-gray-700 text-white rounded-bl-none'
                            ]">
                                {{ msg.body }}
                            </div>
                            <span class="text-xs text-gray-400 mt-1.5 px-1">
                                {{ msg.sender }} · {{ msg.time }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4 border-t border-gray-700 flex gap-2">
                        <textarea
                            v-model="newMessage"
                            @keydown="handleKeyDown"
                            placeholder="Type a message..."
                            rows="1"
                            class="flex-1 p-2 rounded bg-gray-700 border-none outline-none text-sm resize-none"
                            style="max-height: 120px; overflow-y: auto;"
                        ></textarea>
                        <button
                            @click="sendMessage"
                            :disabled="isSending"
                            class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 px-3 py-2 rounded text-sm font-medium"
                        >
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="p-6 border-t border-gray-700 flex justify-center gap-4">
            <!-- Mic Button -->
            <button
                @click="toggleMic"
                :class="isMicMuted ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600'"
                class="p-3 rounded-full text-white transition"
                :title="isMicMuted ? 'Unmute' : 'Mute'"
            >
                <!-- Mic On -->
                <svg v-if="!isMicMuted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M12 3a4 4 0 014 4v4a4 4 0 01-8 0V7a4 4 0 014-4z" />
                </svg>
                <!-- Mic Off -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                </svg>
            </button>

            <!-- Camera Button -->
            <button
                @click="toggleCamera"
                :class="isCameraOff ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600'"
                class="p-3 rounded-full text-white transition"
                :title="isCameraOff ? 'Turn Camera On' : 'Turn Camera Off'"
            >
                <!-- Camera On -->
                <svg v-if="!isCameraOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                </svg>
                <!-- Camera Off -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                </svg>
            </button>

            <button @click="toggleScreenShare" class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600">
                {{ isScreenSharing ? 'Stop Share' : 'Share Screen' }}
            </button>

            <button @click="toggleParticipants" class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600">
                👥 Participants
            </button>

            <button
                @click="toggleChat"
                :class="['px-4 py-2 rounded', showChat ? 'bg-blue-600 hover:bg-blue-500' : 'bg-gray-700 hover:bg-gray-600']"
            >
                💬 Chat
            </button>

            <!-- Leave / End Meeting Button -->
            <button @click="isHost ? endMeeting() : leaveRoom()" class="px-4 py-2 rounded-lg text-white font-medium transition"
                :class="isHost ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600'"
                :title="isHost ? 'End meeting for everyone' : 'Leave meeting'"
            >
                {{ isHost ? 'End Meeting' : 'Leave' }}
            </button>
        </div>
    </div>
</template>


<style scoped>
#local-player :deep(video) {
    transform: scaleX(-1);
}

#local-player.screen-sharing :deep(video) {
    transform: scaleX(1) !important;
}
</style>
