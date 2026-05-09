<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Http\Requests\StoreMeetingRequest;
use Illuminate\Http\Request;
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
        $meetings = Meeting::where('host_id', auth()->id())->latest()->get();
        return Inertia::render('meetings/Index', compact('meetings'));
    }

    public function create()
    {
        return Inertia::render('meetings/Create', []);
    }


    public function store(StoreMeetingRequest $request)
    {
        Meeting::create([
            'title' => $request->title,
            'host_id' => auth()->id(),
            'meeting_code' => Str::uuid(),
            'type' => $request->type,
            'scheduled_at' => $request->type === 'scheduled'
                ? $request->scheduled_at
                : null,
            'status' => 'upcoming',
        ]);

        Inertia::flash('message', 'User created successfully!');
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

        return redirect()->route('meetings.room', $meeting->meeting_code);
    }

    public function room($meeting_code)
    {
        $meeting = Meeting::with('host')->where('meeting_code', $meeting_code)->firstOrFail();
        return Inertia::render('meetings/Room', compact('meeting'));
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
}
