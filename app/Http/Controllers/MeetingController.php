<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Http\Requests\StoreMeetingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\Agora\RtcTokenBuilder;

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
}
