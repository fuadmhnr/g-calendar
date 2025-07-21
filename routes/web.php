<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GuestCalendarController;

Route::get('/', function () {
    return view('home');
})->name('home');

// Guest routes - no authentication required
Route::get('/events', [GuestCalendarController::class, 'index'])->name('guest.index');
Route::get('/events/{event}', [GuestCalendarController::class, 'show'])->name('guest.show');
Route::post('/events/{event}/join', [GuestCalendarController::class, 'join'])->name('guest.join');

// Google OAuth routes
Route::get('/google/redirect', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('google.callback');

// Calendar routes - protected by Google Calendar Auth middleware
Route::middleware(['google.auth'])->group(function () {
    Route::get('/calendar', [GoogleCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/create', [GoogleCalendarController::class, 'create'])->name('calendar.create');
    Route::post('/calendar', [GoogleCalendarController::class, 'store'])->name('calendar.store');
    Route::get('/calendar/{event}/edit', [GoogleCalendarController::class, 'edit'])->name('calendar.edit');
    Route::put('/calendar/{event}', [GoogleCalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{event}', [GoogleCalendarController::class, 'destroy'])->name('calendar.destroy');
});
