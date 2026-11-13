<?php

use App\Http\Controllers\ShowTopic;
use App\Http\Controllers\ShowTopics;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public: anyone can browse topics and read one in their own language.
Route::get('/topics', ShowTopics::class)->name('topics.index');

Volt::route('/topic/{concept:public_id}/edit/{locale}', 'topic-editor')
    ->where('locale', '[A-Za-z]{2,3}(-[A-Za-z0-9]+)?')
    ->middleware('auth')
    ->name('topic.edit');

Route::get('/topic/{concept:public_id}/{slug?}', ShowTopic::class)
    ->where('slug', '[A-Za-z0-9._-]+')
    ->name('topic.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('profile', 'profile')->name('profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
