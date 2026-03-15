<script setup>
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
    meeting: Object
})

const showChat = ref(false)
const showParticipants = ref(false)
const isHost = page.props.auth.user.id === props.meeting.host_id

const toggleChat = () => {
    showChat.value = !showChat.value
    showParticipants.value = false
}

const toggleParticipants = () => {
    showParticipants.value = !showParticipants.value
    showChat.value = false
}
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
            <div class="flex-1 flex items-center justify-center">
                <div class="bg-black w-4/5 h-4/5 rounded-lg flex items-center justify-center">
                    <p class="text-gray-500">
                        Video Grid Area
                    </p>
                </div>
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

            <button class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600">
                🖥 Share
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

</style>
