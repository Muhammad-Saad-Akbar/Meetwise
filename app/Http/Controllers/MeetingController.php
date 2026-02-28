<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Http\Requests\StoreMeetingRequest;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
}
