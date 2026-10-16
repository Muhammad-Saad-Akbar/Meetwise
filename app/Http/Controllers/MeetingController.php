<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Recording;
use App\Http\Requests\StoreMeetingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\Agora\RtcTokenBuilder;
use App\Models\Message;
use App\Events\MessageSent;

class MeetingController extends Controller
{
    //
    public function index()
    {
        $meetings = Meeting::where('host_id', auth()->id())
            ->withCount(['recordings' => fn ($query) => $query->where('status', 'completed')])
            ->latest()
            ->get();
        return Inertia::render('meetings/Index', compact('meetings'));
    }

    public function create()
    {
        return Inertia::render('meetings/Create', []);
    }

    public function store(StoreMeetingRequest $request)
    {
        $meeting = Meeting::create([
            'title'        => $request->title,
            'host_id'      => auth()->id(),
            'meeting_code' => Str::uuid(),
            'type'         => $request->type,
            'scheduled_at' => $request->type === 'scheduled'
                ? $request->scheduled_at
                : null,
            'status'       => 'upcoming',
        ]);

        // Instant meeting → flash the meeting_code so Index.vue can open it in a new tab
        if ($request->type === 'instant') {
            return redirect()->route('meetings.index')
                ->with('instantMeetingCode', $meeting->meeting_code);
        }

        // Scheduled meeting → redirect normally
        return redirect()->route('meetings.index');
    }

    public function edit($meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)
            ->where('host_id', auth()->id())
            ->firstOrFail();

        // Only scheduled upcoming meetings can be edited
        if ($meeting->type !== 'scheduled' || $meeting->status !== 'upcoming') {
            return redirect()->route('meetings.index');
        }

        return Inertia::render('meetings/Edit', compact('meeting'));
    }

    public function update(Request $request, $meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)
            ->where('host_id', auth()->id())
            ->firstOrFail();

        // Only scheduled upcoming meetings can be updated
        if ($meeting->type !== 'scheduled' || $meeting->status !== 'upcoming') {
            return redirect()->route('meetings.index');
        }

        $request->validate([
            'title'        => 'required|string|max:255',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $meeting->update([
            'title'        => $request->title,
            'scheduled_at' => $request->scheduled_at,
        ]);

        return redirect()->route('meetings.index');
    }

    public function destroy($meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)
            ->where('host_id', auth()->id())
            ->firstOrFail();

        // Only scheduled meetings can be deleted
        if ($meeting->type !== 'scheduled') {
            return redirect()->route('meetings.index');
        }

        // Remove recording files from disk (DB rows are removed by the cascade)
        foreach ($meeting->recordings as $recording) {
            Storage::disk('local')->delete($recording->file_path);
        }

        $meeting->delete();

        return redirect()->route('meetings.index');
    }

    public function joinForm()
    {
        return Inertia::render('meetings/Join', []);
    }

    public function join(Request $request)
    {
        $request->validate([
            'meeting_code' => 'required|string',
        ]);

        $meeting = Meeting::where('meeting_code', $request->meeting_code)->first();

        if (!$meeting) {
            return back()->withErrors([
                'meeting_code' => 'Meeting not found.'
            ]);
        }

        if ($meeting->status === 'ended') {
            return back()->withErrors([
                'meeting_code' => 'This meeting has already ended.'
            ]);
        }

        if ($meeting->status === 'upcoming' && $meeting->host_id !== auth()->id()) {
            return back()->withErrors([
                'meeting_code' => 'Meeting has not started yet.'
            ]);
        }

        return redirect()->route('meetings.waitingRoom', $meeting->meeting_code);
    }

    public function room($meeting_code)
    {
        $meeting = Meeting::with('host')->where('meeting_code', $meeting_code)->firstOrFail();
        return Inertia::render('meetings/Room', compact('meeting'));
    }

    public function waitingRoom($meeting_code)
    {
        $meeting = Meeting::with('host')->where('meeting_code', $meeting_code)->firstOrFail();

        // Don't allow entry into waiting room if meeting already ended
        if ($meeting->status === 'ended') {
            return redirect()->route('meetings.index');
        }

        return Inertia::render('meetings/WaitingRoom', compact('meeting'));
    }

    public function meetingEnded($meeting_code)
    {
        $meeting = Meeting::with('host')->where('meeting_code', $meeting_code)->firstOrFail();
        return Inertia::render('meetings/MeetingEnd', compact('meeting'));
    }

    public function joinViaLink($meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)->first();

        if (!$meeting) {
            abort(404);
        }

        if ($meeting->status === 'ended') {
            return redirect()->route('home')
                ->with('joinMessage', 'This meeting has already ended.');
        }

        // Not logged in → send to register with a message
        if (!auth()->check()) {
            return redirect()->route('register')
                ->with('joinMessage', 'You must sign up before joining a meeting. After signing up, click the meeting link again to join.');
        }

        // Logged in → send to waiting room
        return redirect()->route('meetings.waitingRoom', $meeting->meeting_code);
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
        ]);

        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.certificate');

        $channelName = $request->channel;
        $uid = auth()->id();
        $role = RtcTokenBuilder::RolePublisher;

        $expireTimeInSeconds = 3600;
        $currentTimestamp = now()->timestamp;
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        $token = RtcTokenBuilder::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $privilegeExpiredTs
        );

        return response()->json([
            'token' => $token,
            'appId' => $appId,
            'channel' => $channelName,
            'uid' => $uid,
        ]);
    }

    public function getMessages($meeting_code)
    {
        $messages = Message::with('user')
            ->where('meeting_code', $meeting_code)
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'id'     => $m->id,
                'body'   => $m->body,
                'sender' => $m->user->name,
                'time'   => $m->created_at->format('H:i'),
                'isSelf' => $m->user_id === auth()->id(),
            ])
            ->values();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $meeting_code)
    {
        $request->validate(['body' => 'required|string|max:1000']);

        $message = Message::create([
            'user_id'      => auth()->id(),
            'meeting_code' => $meeting_code,
            'body'         => $request->body,
        ]);

        broadcast(new MessageSent($message->load('user')))->toOthers();

        return response()->json([
            'id'     => $message->id,
            'body'   => $message->body,
            'sender' => 'You',
            'time'   => $message->created_at->format('H:i'),
            'isSelf' => true,
        ]);
    }

    public function startMeeting($meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)->firstOrFail();

        if (auth()->id() !== $meeting->host_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting->update(['status' => 'live']);

        return response()->json(['status' => 'live']);
    }

    public function endMeeting($meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)->firstOrFail();

        if (auth()->id() !== $meeting->host_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting->update(['status' => 'ended']);

        // Safety net: close any recording that is still open when the meeting ends
        $meeting->recordings()
            ->where('status', 'recording')
            ->get()
            ->each(fn (Recording $recording) => $this->finalizeRecording($recording));

        return response()->json(['status' => 'ended']);
    }

    public function checkStatus($meeting_code)
    {
        $meeting = Meeting::where('meeting_code', $meeting_code)->firstOrFail();

        return response()->json([
            'status'       => $meeting->status,
            // Lets participants see the "meeting is being recorded" banner (picked up by the existing poll)
            'is_recording' => $meeting->recordings()->where('status', 'recording')->exists(),
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
     |  MEETING RECORDING
     |  The host's browser records the meeting and uploads it in
     |  small chunks while the meeting is running. Files are kept on
     |  the private "local" disk and only streamed to the host.
     ───────────────────────────────────────────────────────────── */

    // Host starts a new recording → create the DB row the chunks will attach to
    public function startRecording($meeting_code)
    {
        $meeting = $this->hostMeeting($meeting_code);

        if ($meeting->status === 'ended') {
            return response()->json(['error' => 'This meeting has already ended.'], 422);
        }

        // Close any leftover open recording (e.g. from a crashed tab) before starting a new one
        $meeting->recordings()
            ->where('status', 'recording')
            ->get()
            ->each(fn (Recording $recording) => $this->finalizeRecording($recording));

        $recording = $meeting->recordings()->create([
            'file_path'  => "recordings/{$meeting->id}/" . Str::uuid() . '.webm',
            'mime_type'  => 'video/webm',
            'status'     => 'recording',
            'started_at' => now(),
        ]);

        return response()->json(['id' => $recording->id]);
    }

    // Receives one chunk of the recording and appends it to the file
    public function uploadRecordingChunk(Request $request, $meeting_code, Recording $recording)
    {
        $meeting = $this->hostMeeting($meeting_code);
        abort_unless($recording->meeting_id === $meeting->id, 404);

        $request->validate([
            'chunk' => 'required|file|max:20480', // KB
            'index' => 'required|integer|min:0',
        ]);

        $index = (int) $request->input('index');

        return DB::transaction(function () use ($recording, $request, $index) {
            $recording = Recording::whereKey($recording->id)->lockForUpdate()->first();

            if ($recording->status !== 'recording') {
                return response()->json(['error' => 'Recording is not active.'], 409);
            }

            // Retry of a chunk we already stored (response got lost) → acknowledge, don't append twice
            if ($index < $recording->chunk_count) {
                return response()->json(['received' => $recording->chunk_count]);
            }

            // A chunk was skipped → appending would corrupt the file
            if ($index > $recording->chunk_count) {
                return response()->json([
                    'error'    => 'Out-of-order chunk.',
                    'expected' => $recording->chunk_count,
                ], 409);
            }

            $disk = Storage::disk('local');
            $disk->makeDirectory(dirname($recording->file_path));

            $in  = fopen($request->file('chunk')->getRealPath(), 'rb');
            $out = fopen($disk->path($recording->file_path), 'ab');
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);

            $recording->increment('chunk_count');

            return response()->json(['received' => $recording->chunk_count]);
        });
    }

    // Host stops recording → finalize size + duration
    public function stopRecording($meeting_code, Recording $recording)
    {
        $meeting = $this->hostMeeting($meeting_code);
        abort_unless($recording->meeting_id === $meeting->id, 404);

        $this->finalizeRecording($recording);

        return response()->json(['status' => 'completed']);
    }

    // List a meeting's recordings (watch page)
    public function recordings($meeting_code)
    {
        $meeting = $this->hostMeeting($meeting_code);

        $recordings = $meeting->recordings()
            ->where('status', 'completed')
            ->latest()
            ->get()
            ->map(fn (Recording $r) => [
                'id'         => $r->id,
                'created_at' => $r->created_at,
                'duration'   => $r->duration,
                'size'       => $r->size,
            ])
            ->values();

        return Inertia::render('meetings/Recordings', compact('meeting', 'recordings'));
    }

    // Stream the video file (supports HTTP Range so the player can seek)
    public function streamRecording(Recording $recording)
    {
        $this->authorizeRecording($recording);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($recording->file_path), 404);

        return response()->file($disk->path($recording->file_path), [
            'Content-Type' => $recording->mime_type,
        ]);
    }

    public function downloadRecording(Recording $recording)
    {
        $this->authorizeRecording($recording);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($recording->file_path), 404);

        $filename = Str::slug($recording->meeting->title ?: 'meeting')
            . '-' . $recording->created_at->format('Y-m-d-His') . '.webm';

        return response()->download($disk->path($recording->file_path), $filename, [
            'Content-Type' => $recording->mime_type,
        ]);
    }

    public function destroyRecording(Recording $recording)
    {
        $this->authorizeRecording($recording);

        $meetingCode = $recording->meeting->meeting_code;

        Storage::disk('local')->delete($recording->file_path);
        $recording->delete();

        return redirect()->route('meetings.recordings', $meetingCode);
    }

    // ── helpers ──

    private function hostMeeting($meeting_code): Meeting
    {
        return Meeting::where('meeting_code', $meeting_code)
            ->where('host_id', auth()->id())
            ->firstOrFail();
    }

    private function authorizeRecording(Recording $recording): void
    {
        abort_unless(
            $recording->status === 'completed'
                && $recording->meeting->host_id === auth()->id(),
            404
        );
    }

    private function finalizeRecording(Recording $recording): void
    {
        if ($recording->status === 'completed') {
            return;
        }

        // Nothing was ever uploaded → don't keep an empty recording around
        if ($recording->chunk_count === 0) {
            $recording->delete();
            return;
        }

        $disk = Storage::disk('local');

        $recording->update([
            'status'   => 'completed',
            'size'     => $disk->exists($recording->file_path) ? $disk->size($recording->file_path) : 0,
            'ended_at' => now(),
            'duration' => max(0, now()->timestamp - $recording->started_at->timestamp),
        ]);
    }
}
