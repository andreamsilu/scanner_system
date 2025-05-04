<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventCardController;
use App\Http\Livewire\Admin\Dashboard;
use App\Http\Livewire\AttendeeRegistration;
use App\Http\Livewire\EventCard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Public routes
Route::get('/register', AttendeeRegistration::class)->name('register');
Route::get('/event-card/{attendee}', EventCard::class)->name('event-card');
Route::get('/event-card/{attendee}/download', [EventCardController::class, 'download'])->name('event-card.download');

// Protected routes
Route::middleware('auth')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
