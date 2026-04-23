<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AgoraRTC from "agora-rtc-sdk-ng"

const page = usePage()

const props = defineProps({
    meeting: Object
})

const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
const localTracks = ref([]);
const isScreenSharing = ref(false);
const showChat = ref(false);
const showParticipants = ref(false);
const isHost = page.props.auth.user.id === props.meeting.host_id;

const toggleChat = () => {
    showChat.value = !showChat.value
    showParticipants.value = false
}

const toggleParticipants = () => {
    showParticipants.value = !showParticipants.value
    showChat.value = false
}

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

         //  Play Local Video
        tracks[1].play("local-player")

         //  Publish tracks
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
});

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
        <div class="flex flex-1">

            <!-- Video Area -->
            <div class="flex flex-col items-center justify-center gap-4">
                <!-- Local Video -->
                <div id="local-player" class="w-260 h-120 bg-black rounded"></div>
                <!-- Remote Users -->
                <div id="remote-container" class="flex gap-4"></div>

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
                <div v-if="showChat" class="flex flex-col flex-1">

                    <div class="p-4 border-b border-gray-700">
                        <h2 class="font-semibold">Chat</h2>
                    </div>
                    <div class="flex-1 p-4 overflow-y-auto space-y-2">
                        <div class="bg-gray-700 p-2 rounded">
                            Hello 👋
                        </div>
                        <div class="bg-gray-700 p-2 rounded">
                            Welcome to meeting
                        </div>
                    </div>
                    <div class="p-4 border-t border-gray-700">
                        <input
                            type="text"
                            placeholder="Type a message..."
                            class="w-full p-2 rounded bg-gray-700 border-none"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="p-6 border-t border-gray-700 flex justify-center gap-4">
            <button class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600">
                🎤 Mic
            </button>

            <button class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600">
                📷 Camera
            </button>

            <button @click="toggleScreenShare" class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600">
                {{ isScreenSharing ? 'Stop Share' : 'Share Screen' }}
            </button>

            <button
                @click="toggleParticipants"
                class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600"
            >
                👥 Participants
            </button>

            <button
                @click="toggleChat"
                class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600"
            >
                💬 Chat
            </button>

            <button v-if="isHost" class="bg-red-600 px-4 py-2 rounded hover:bg-red-500">
                End Meeting
            </button>
            <button v-else class="bg-red-600 px-4 py-2 rounded hover:bg-red-500">
                Leave
            </button>
        </div>
    </div>
</template>


<style scoped>
/* ✅ :deep() pierces Vue's scoped boundary → targets Agora's injected <video> */
#local-player :deep(video) {
    transform: scaleX(-1);
}

#local-player.screen-sharing :deep(video) {
    transform: scaleX(1) !important;
}
</style>
