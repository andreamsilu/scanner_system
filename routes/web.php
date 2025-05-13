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
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\AttendeeController;
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

    // Authentication routes for guests
    Route::middleware('guest')->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');
        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });

    // Protected routes that require authentication
    Route::middleware('auth')->group(function () {
        // Email verification routes
        Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])
            ->name('verification.notice');
        Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        // Password management routes
        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
        Route::put('password', [PasswordController::class, 'update'])
            ->name('password.update');

        // Logout route
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');

        // Dashboard Routes
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        // Admin Routes
        Route::middleware('role:admin')->group(function () {
            // Events Management
            Route::prefix('events')->name('events.')->group(function () {
                Route::get('/', [EventController::class, 'index'])->name('index');
                Route::get('/create', [EventController::class, 'create'])->name('create');
                Route::post('/', [EventController::class, 'store'])->name('store');
                Route::get('/{event}', [EventController::class, 'show'])->name('show');
                Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
                Route::put('/{event}', [EventController::class, 'update'])->name('update');
                Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
                Route::get('/{event}/attendees', [EventController::class, 'attendees'])->name('attendees');
                Route::get('/{event}/cards', [EventController::class, 'cards'])->name('cards');
            });

            // Event Cards Management
            Route::prefix('event-cards')->name('event-cards.')->group(function () {
                Route::get('/', [EventCardController::class, 'index'])->name('index');
                Route::get('/create', [EventCardController::class, 'create'])->name('create');
                Route::post('/', [EventCardController::class, 'store'])->name('store');
                Route::get('/{card}', [EventCardController::class, 'show'])->name('show');
                Route::get('/{card}/edit', [EventCardController::class, 'edit'])->name('edit');
                Route::put('/{card}', [EventCardController::class, 'update'])->name('update');
                Route::delete('/{card}', [EventCardController::class, 'destroy'])->name('destroy');
                Route::get('/{card}/download', [EventCardController::class, 'download'])->name('download');
                Route::get('/verify/{token}', [EventCardController::class, 'verify'])->name('verify');
            });

            // Attendees Management
            Route::prefix('attendees')->name('attendees.')->group(function () {
                Route::get('/', [AttendeeController::class, 'index'])->name('index');
                Route::get('/create', [AttendeeController::class, 'create'])->name('create');
                Route::post('/', [AttendeeController::class, 'store'])->name('store');
                Route::get('/{attendee}', [AttendeeController::class, 'show'])->name('show');
                Route::get('/{attendee}/edit', [AttendeeController::class, 'edit'])->name('edit');
                Route::put('/{attendee}', [AttendeeController::class, 'update'])->name('update');
                Route::delete('/{attendee}', [AttendeeController::class, 'destroy'])->name('destroy');
                Route::get('/bulk-upload', [AttendeeController::class, 'bulkUploadForm'])->name('bulk-upload');
                Route::post('/bulk-upload', [AttendeeController::class, 'bulkUpload'])->name('bulk-upload.store');
                Route::get('/download-template', [AttendeeController::class, 'downloadTemplate'])->name('download-template');
            });

            // Activity Logs Management
            Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
                Route::get('/', [ActivityLogsController::class, 'index'])->name('index');
                Route::get('/export', [ActivityLogsController::class, 'export'])->name('export');
            });

            // Users Management
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('/create', [UserController::class, 'create'])->name('create');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::get('/{user}', [UserController::class, 'show'])->name('show');
                Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
                Route::put('/{user}', [UserController::class, 'update'])->name('update');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            });
        });

        // Scanner Routes
        Route::middleware('role:scanner')->group(function () {
            Route::get('/scanner', [ScannerController::class, 'dashboard'])->name('scanner.dashboard');
            Route::get('/scanner/history', [ScannerController::class, 'history'])->name('scanner.history');
            Route::post('/scanner/validate', [ScannerController::class, 'validate'])->name('scanner.validate');
            Route::get('/scanner/export', [ScannerController::class, 'exportHistory'])->name('scanner.export');
        });

        // Profile Management
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});
Route::middleware(['auth', 'role:scanner'])->prefix('scanner')->name('scanner.')->group(function () {
    Route::get('/dashboard', [ScannerController::class, 'dashboard'])->name('dashboard');
    Route::get('/history', [ScannerController::class, 'history'])->name('history');
    Route::post('/validate', [ScannerController::class, 'validateToken'])->name('validate');
    Route::get('/event/{event}/select', [ScannerController::class, 'selectEvent'])->name('select-event');
    Route::get('/event/selected', [ScannerController::class, 'getSelectedEvent'])->name('selected-event');
});

Route::get('/scanners', [ScannerController::class, 'index'])->name('scanners.index');

