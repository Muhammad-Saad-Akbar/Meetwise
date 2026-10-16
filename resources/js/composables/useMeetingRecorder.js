import { computed, onUnmounted, ref } from 'vue'
import axios from 'axios'

/**
 * useMeetingRecorder
 *
 * Records the meeting in the HOST's browser:
 *  - Video: every <video> in #local-player / #remote-container is drawn into a
 *    canvas (simple grid layout) and captured with canvas.captureStream().
 *  - Audio: the host's mic + every remote audio track are mixed with Web Audio.
 *  - Upload: MediaRecorder emits a chunk every few seconds; chunks are uploaded
 *    one by one (in order, with retries) so the file builds up on the server
 *    while the meeting is still running.
 */

const WIDTH = 1280
const HEIGHT = 720
const FPS = 24
const TIMESLICE_MS = 3000 // one upload every 3s (~0.6 MB, safely under PHP's default 2 MB limit)
const UPLOAD_RETRIES = 3
const MIME_CANDIDATES = ['video/webm;codecs=vp8,opus', 'video/webm']

export function useMeetingRecorder(meetingCode) {
    const isRecording = ref(false)
    const isBusy = ref(false)
    const elapsedSeconds = ref(0)
    const error = ref('')

    // Audio tracks currently in the meeting. Kept even while NOT recording so
    // they can all be connected the moment recording starts.
    const audioTracks = new Map() // id -> MediaStreamTrack
    const audioNodes = new Map() // id -> MediaStreamAudioSourceNode

    let recordingId = null
    let mediaRecorder = null
    let canvasStream = null
    let ctx2d = null
    let audioContext = null
    let audioDestination = null
    let stopTicker = null
    let elapsedTimer = null
    let startedAt = 0
    let uploadQueue = Promise.resolve()
    let chunkIndex = 0
    let uploadFailed = false

    const formattedTime = computed(() => {
        const total = elapsedSeconds.value
        const h = Math.floor(total / 3600)
        const m = Math.floor((total % 3600) / 60)
        const s = total % 60
        const pad = (n) => String(n).padStart(2, '0')
        return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`
    })

    // ── Audio mixing ──────────────────────────────────────────────

    const disconnectAudio = (id) => {
        const node = audioNodes.get(id)
        if (node) {
            node.disconnect()
            audioNodes.delete(id)
        }
    }

    const connectAudio = (id) => {
        if (!audioContext) return
        disconnectAudio(id)

        const track = audioTracks.get(id)
        if (!track || track.readyState === 'ended') return

        const source = audioContext.createMediaStreamSource(new MediaStream([track]))
        source.connect(audioDestination)
        audioNodes.set(id, source)
    }

    // Call when a mic / remote audio track becomes available (replaces any old track with the same id)
    const registerAudioTrack = (id, mediaStreamTrack) => {
        audioTracks.set(String(id), mediaStreamTrack)
        connectAudio(String(id))
    }

    const unregisterAudioTrack = (id) => {
        audioTracks.delete(String(id))
        disconnectAudio(String(id))
    }

    // ── Video mixing ──────────────────────────────────────────────

    const drawFrame = () => {
        if (!ctx2d) return

        const videos = Array.from(
            document.querySelectorAll('#local-player video, #remote-container video'),
        ).filter((v) => v.videoWidth > 0 && v.readyState >= 2)

        ctx2d.fillStyle = '#111827'
        ctx2d.fillRect(0, 0, WIDTH, HEIGHT)
        if (videos.length === 0) return

        const cols = Math.ceil(Math.sqrt(videos.length))
        const rows = Math.ceil(videos.length / cols)
        const cellW = WIDTH / cols
        const cellH = HEIGHT / rows

        videos.forEach((video, i) => {
            const col = i % cols
            const row = Math.floor(i / cols)
            const scale = Math.min(cellW / video.videoWidth, cellH / video.videoHeight)
            const w = video.videoWidth * scale
            const h = video.videoHeight * scale
            ctx2d.drawImage(video, col * cellW + (cellW - w) / 2, row * cellH + (cellH - h) / 2, w, h)
        })
    }

    // A timer inside a Web Worker isn't throttled when the tab is in the background
    // (e.g. the host switched to the window they are screen-sharing).
    const startTicker = (callback) => {
        const source = `setInterval(() => postMessage(0), ${Math.round(1000 / FPS)})`
        const url = URL.createObjectURL(new Blob([source], { type: 'application/javascript' }))
        const worker = new Worker(url)
        worker.onmessage = callback
        return () => {
            worker.terminate()
            URL.revokeObjectURL(url)
        }
    }

    // ── Uploading ─────────────────────────────────────────────────

    const uploadChunk = async (index, blob) => {
        for (let attempt = 1; attempt <= UPLOAD_RETRIES; attempt++) {
            try {
                const form = new FormData()
                form.append('chunk', blob, `chunk-${index}.webm`)
                form.append('index', String(index))
                await axios.post(route('meetings.recordings.chunk', [meetingCode, recordingId]), form)
                return
            } catch (e) {
                if (attempt === UPLOAD_RETRIES) throw e
                await new Promise((resolve) => setTimeout(resolve, 1000 * attempt))
            }
        }
    }

    const closeOnServer = async () => {
        if (!recordingId) return
        await axios.post(route('meetings.recordings.stop', [meetingCode, recordingId]))
    }

    // ── Lifecycle ─────────────────────────────────────────────────

    const teardown = () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.onstop = null
            try {
                mediaRecorder.stop()
            } catch (e) {
                /* already stopped */
            }
        }
        mediaRecorder = null

        stopTicker?.()
        stopTicker = null

        clearInterval(elapsedTimer)
        elapsedTimer = null

        canvasStream?.getTracks().forEach((t) => t.stop())
        canvasStream = null
        ctx2d = null

        for (const id of [...audioNodes.keys()]) disconnectAudio(id)
        audioContext?.close().catch(() => {})
        audioContext = null
        audioDestination = null

        isRecording.value = false
    }

    const handleUploadFailure = async (e) => {
        console.error('Recording upload failed:', e)
        uploadFailed = true
        error.value = 'Connection problem: recording stopped. Everything uploaded so far was saved.'
        teardown()
        try {
            await closeOnServer()
        } catch (err) {
            /* nothing more we can do */
        }
    }

    const start = async () => {
        if (isRecording.value || isBusy.value) return
        error.value = ''

        if (typeof MediaRecorder === 'undefined') {
            error.value = 'Recording is not supported in this browser. Try Chrome, Edge or Firefox.'
            return
        }
        const mimeType = MIME_CANDIDATES.find((type) => MediaRecorder.isTypeSupported(type))
        if (!mimeType) {
            error.value = 'Recording is not supported in this browser. Try Chrome, Edge or Firefox.'
            return
        }

        isBusy.value = true
        recordingId = null

        try {
            // Created inside the click handler so the browser lets it run
            audioContext = new AudioContext()
            audioDestination = audioContext.createMediaStreamDestination()
            if (audioContext.state === 'suspended') await audioContext.resume()

            const res = await axios.post(route('meetings.recordings.start', meetingCode))
            recordingId = res.data.id

            // Video: canvas
            const canvas = document.createElement('canvas')
            canvas.width = WIDTH
            canvas.height = HEIGHT
            ctx2d = canvas.getContext('2d')
            drawFrame()
            stopTicker = startTicker(drawFrame)
            canvasStream = canvas.captureStream(FPS)

            // Audio: mix everyone already in the meeting
            for (const id of audioTracks.keys()) connectAudio(id)

            const stream = new MediaStream([
                ...canvasStream.getVideoTracks(),
                ...audioDestination.stream.getAudioTracks(),
            ])

            chunkIndex = 0
            uploadFailed = false
            uploadQueue = Promise.resolve()

            mediaRecorder = new MediaRecorder(stream, {
                mimeType,
                videoBitsPerSecond: 1_500_000,
                audioBitsPerSecond: 128_000,
            })

            mediaRecorder.ondataavailable = (event) => {
                if (!event.data || event.data.size === 0) return
                const index = chunkIndex++
                // Chain uploads so chunks always reach the server in order
                uploadQueue = uploadQueue.then(async () => {
                    if (uploadFailed) return
                    try {
                        await uploadChunk(index, event.data)
                    } catch (e) {
                        await handleUploadFailure(e)
                    }
                })
            }

            mediaRecorder.start(TIMESLICE_MS)

            startedAt = Date.now()
            elapsedSeconds.value = 0
            elapsedTimer = setInterval(() => {
                elapsedSeconds.value = Math.floor((Date.now() - startedAt) / 1000)
            }, 1000)

            isRecording.value = true
        } catch (e) {
            console.error('Could not start recording:', e)
            error.value = 'Could not start recording. Please try again.'
            teardown()
            try {
                await closeOnServer()
            } catch (err) {
                /* ignore */
            }
        } finally {
            isBusy.value = false
        }
    }

    // Stops recording, waits until every chunk is uploaded, then finalizes on the server
    const stop = async () => {
        if (!isRecording.value || isBusy.value) return
        isBusy.value = true

        try {
            await new Promise((resolve) => {
                mediaRecorder.onstop = resolve
                mediaRecorder.stop() // fires one last dataavailable, then "stop"
            })
            await uploadQueue

            const failed = uploadFailed
            teardown()
            if (!failed) await closeOnServer()
        } catch (e) {
            console.error('Could not finish recording:', e)
            error.value = 'Could not save the recording.'
            teardown()
        } finally {
            isBusy.value = false
        }
    }

    // Component unmounted: release devices/timers (the server finalizes when the meeting ends)
    onUnmounted(teardown)

    return {
        isRecording,
        isBusy,
        error,
        formattedTime,
        registerAudioTrack,
        unregisterAudioTrack,
        start,
        stop,
    }
}
