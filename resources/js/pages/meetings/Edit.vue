<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    meeting: Object
})

// Format the stored datetime into the format datetime-local input needs: YYYY-MM-DDTHH:MM
const formatForInput = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return `${year}-${month}-${day}T${hours}:${minutes}`
}

const form = useForm({
    title: props.meeting.title,
    scheduled_at: formatForInput(props.meeting.scheduled_at),
})

const submit = () => {
    form.put(route('meetings.update', props.meeting.meeting_code))
}
</script>

<template>
    <Head title="Edit Meeting" />

    <AppLayout>
        <div class="p-6 max-w-lg">
            <h1 class="text-2xl font-bold mb-6">Edit Meeting</h1>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium mb-1">Meeting Title</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter meeting title"
                    />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">
                        {{ form.errors.title }}
                    </p>
                </div>

                <!-- Scheduled At -->
                <div>
                    <label class="block text-sm font-medium mb-1">Scheduled Date & Time</label>
                    <input
                        v-model="form.scheduled_at"
                        type="datetime-local"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <p v-if="form.errors.scheduled_at" class="text-red-500 text-xs mt-1">
                        {{ form.errors.scheduled_at }}
                    </p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link
                        :href="route('meetings.index')"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-sm font-medium transition"
                    >
                        Cancel
                    </Link>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
