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

Route::middleware(['auth'])->group(function () {
   Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
   Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
   Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
});

require __DIR__.'/settings.php';
