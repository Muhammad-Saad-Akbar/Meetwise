<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\MeetingController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Public meeting join link — no auth middleware
Route::get('/join/{meeting_code}', [MeetingController::class, 'joinViaLink'])->name('meetings.joinViaLink');

Route::middleware(['auth'])->group(function () {
   Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
   Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
   Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
   Route::get('/meetings/join', [MeetingController::class, 'joinForm'])->name('meetings.joinForm');
   Route::post('/meetings/join', [MeetingController::class, 'join'])->name('meetings.join');
   Route::get('/meetings/{meeting_code}/waiting-room', [MeetingController::class, 'waitingRoom'])->name('meetings.waitingRoom');
   Route::get('/meetings/{meeting_code}', [MeetingController::class, 'room'])->name('meetings.room');
   Route::post('/agora/token', [MeetingController::class, 'generateToken'])->name('agora.token');
   Route::post('/meetings/{meeting_code}/messages', [MeetingController::class, 'sendMessage']);
   Route::get('/meetings/{meeting_code}/messages', [MeetingController::class, 'getMessages']);
   Route::post('/meetings/{meeting_code}/start', [MeetingController::class, 'startMeeting'])->name('meetings.start');
   Route::post('/meetings/{meeting_code}/end', [MeetingController::class, 'endMeeting'])->name('meetings.end');
   Route::get('/meetings/{meeting_code}/status', [MeetingController::class, 'checkStatus'])->name('meetings.checkStatus');
});

require __DIR__.'/settings.php';
