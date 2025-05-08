<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventCardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventCardManagementController;
use App\Http\Controllers\ScanLogManagementController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AttendeeRegistrationController;
use App\Http\Controllers\QrScannerController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\UserImportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\EventController;
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

Route::middleware(['web'])->group(function () {
    // Public routes that don't require authentication
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/register', [AttendeeRegistrationController::class, 'index'])->name('register');
    Route::post('/register', [AttendeeRegistrationController::class, 'store'])->name('register.store');
    Route::get('/event-card/{attendee}', [EventCardController::class, 'show'])->name('event-card');
    Route::get('/event-card/{attendee}/download', [EventCardController::class, 'download'])->name('event-card.download');

    // Authentication routes
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    // Protected routes that require authentication
    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
        
        // Dashboard Routes
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
        
        // Event Card Management
        Route::get('/event-cards', [EventCardManagementController::class, 'index'])->name('event-cards.index');
        Route::get('/event-cards/create', [EventCardManagementController::class, 'create'])->name('event-cards.create');
        Route::post('/event-cards', [EventCardManagementController::class, 'store'])->name('event-cards.store');
        Route::get('/event-cards/{attendee}', [EventCardManagementController::class, 'show'])->name('event-cards.show');
        Route::get('/event-cards/{attendee}/edit', [EventCardManagementController::class, 'edit'])->name('event-cards.edit');
        Route::put('/event-cards/{attendee}', [EventCardManagementController::class, 'update'])->name('event-cards.update');
        Route::delete('/event-cards/{attendee}', [EventCardManagementController::class, 'destroy'])->name('event-cards.destroy');
        Route::get('/event-cards/{attendee}/download', [EventCardManagementController::class, 'download'])->name('event-cards.download');
        Route::get('/event-cards/verify/{token}', [EventCardManagementController::class, 'verify'])
            ->name('event-cards.verify');
        
        // Scan Log Management
        Route::get('/scan-logs', [ScanLogManagementController::class, 'index'])->name('scan-logs');
        Route::get('/scan-logs/export', [ScanLogManagementController::class, 'export'])->name('scan-logs.export');
        
        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        
        // QR Scanner
        Route::get('/scanner', [QrScannerController::class, 'index'])->name('scanner');
        Route::post('/scanner/validate', [QrScannerController::class, 'validateToken'])->name('scanner.validate');
        
        // Profile Management
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // Admin Routes
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', function () {
                return view('admin.dashboard');
            })->name('admin.dashboard');
            
            // User Management
            Route::get('/users', [UserManagementController::class, 'index'])->name('users');
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
            
            // Activity Logs
            Route::get('/activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs');
            Route::get('/activity-logs/export', [ActivityLogsController::class, 'export'])->name('activity-logs.export');
            
            // User Import
            Route::get('/users/import', [UserImportController::class, 'index'])->name('users.import');
            Route::post('/users/import', [UserImportController::class, 'import'])->name('users.import.store');
            Route::get('/users/import/template', [UserImportController::class, 'downloadTemplate'])->name('users.import.template');
        });
    });
});

// Resource Routes
Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('activity-logs', ActivityLogController::class);
    Route::resource('events', EventController::class);
});
