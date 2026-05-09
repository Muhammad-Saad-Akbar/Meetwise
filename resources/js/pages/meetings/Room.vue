<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AgoraRTC from "agora-rtc-sdk-ng"
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const page = usePage()
const props = defineProps({
    meeting: Object
})

//   RTC Setup
const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
const localTracks = ref([]);
const isScreenSharing = ref(false);

//  Chat State
const messages = ref([])
const newMessage = ref('')
const chatBody = ref(null)
const isSending = ref(false)

//  UI State
const showChat = ref(false);
const showParticipants = ref(false);
const isHost = page.props.auth.user.id === props.meeting.host_id;

let echo = null

const toggleChat = () => {
    showChat.value = !showChat.value
    showParticipants.value = false
    if (showChat.value) nextTick(() => scrollToBottom())
}

const toggleParticipants = () => {
    showParticipants.value = !showParticipants.value
    showChat.value = false
}


// Screen Share
const stopScreenShare = async () => {
    try {
        // Get a fresh native camera stream
        const cameraStream = await navigator.mediaDevices.getUserMedia({ video: true })
        const newCameraMediaTrack = cameraStream.getVideoTracks()[0]

        await localTracks.value[1].replaceTrack(newCameraMediaTrack, true)
        localTracks.value[1].play("local-player")

        document.getElementById("local-player").classList.remove("screen-sharing");

        isScreenSharing.value = false
    } catch (err) {
        console.error("Stop screen share error:", err)
    }
}

const toggleScreenShare = async () => {
    if (!isScreenSharing.value) {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: true
            })
            const screenMediaTrack = screenStream.getVideoTracks()[0]

            await localTracks.value[1].replaceTrack(screenMediaTrack, true)
            localTracks.value[1].play("local-player")

            document.getElementById("local-player").classList.add("screen-sharing");

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

    try {
        const res = await axios.post('/agora/token', {
            channel: props.meeting.meeting_code
        })

        const { token, appId, channel, uid } = res.data

        //  Join Agora channel
        await client.join(appId, channel, token, uid)
        console.log("Joined Agora")

        //  Create Mic + Camera
        const tracks = await AgoraRTC.createMicrophoneAndCameraTracks()
        localTracks.value = tracks
        tracks[1].play("local-player")
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

onUnmounted(() => {
    echo?.disconnect()
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
            <button class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600"> 🎤 Mic </button>
            <button class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600"> 📷 Camera </button>
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

            <button v-if="isHost" class="bg-red-600 px-4 py-2 rounded hover:bg-red-500">End Meeting</button>
            <button v-else class="bg-red-600 px-4 py-2 rounded hover:bg-red-500">Leave</button>
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
