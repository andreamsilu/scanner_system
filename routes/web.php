<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\SmsManagementController;
use App\Http\Controllers\CardTemplateController;
use App\Http\Controllers\EventCardController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\PublicRegistrationController;

// Keep static paths such as users/export and attendees/bulk-upload out of numeric bindings.
Route::pattern('id', '[0-9]+');
Route::pattern('attendee', '[0-9]+');

Route::get('/webhooks/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'receive'])->middleware('throttle:600,1')->name('whatsapp.webhook.receive');

Route::prefix('register/event/{token}')->whereUuid('token')->middleware('cache.headers:no_store;private')->name('public-registration.')->group(function () {
    Route::get('/', [PublicRegistrationController::class, 'show'])->name('show');
    Route::post('/', [PublicRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::get('/complete', [PublicRegistrationController::class, 'complete'])->name('complete');
});

// Authentication Routes
/* Removed (or commented out) the fallback route (or any route named 'welcome') so that if a user (or a seeder) tries to redirect to route('welcome') (or GET /) then a 404 (or a fallback) is returned (and no "Route [welcome] not defined" error is thrown). */
/*
Route::get('/', function () {
    return view('welcome');
})->name('welcome');
*/

/* (New fallback (or root) route (GET /) so that if a user (or a seeder) tries to GET / (or redirect to route('welcome')) then the user is redirected (using redirect()->route('login')) (so that no "Route [welcome] not defined" error is thrown).) */
Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile routes (accessible to both admin and scanner roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Scanner Routes (accessible to both admin and scanner roles)
    Route::middleware(['auth', 'role:admin,scanner'])->prefix('scanner')->name('scanner.')->group(function () {
        // Dashboard and Event Management
        Route::get('/dashboard', [ScannerController::class, 'dashboard'])->name('dashboard');
        Route::get('/events', [ScannerController::class, 'events'])->name('events.index');
        Route::post('/events/{event}/select', [ScannerController::class, 'selectEvent'])->name('events.select');
        Route::delete('/events/{event}/deselect', [ScannerController::class, 'deselectEvent'])->name('events.deselect');
        Route::get('/events/{event}/attendees', [ScannerController::class, 'showEventAttendees'])->name('events.attendees');
        
        // Attendees Management
        Route::get('/attendees', [ScannerController::class, 'attendeesIndex'])->name('attendees.index');
        
        // QR Code Scanning Routes
        Route::get('/scan', [ScannerController::class, 'scan'])->name('scan.index');
        Route::post('/scan/validate', [AdmissionController::class, 'store'])->middleware('throttle:120,1')->name('scan.validate');
        
        // Manual Token Validation Routes - Redirect to scan page
        Route::get('/validate', [ScannerController::class, 'scan'])->name('validate.index');
        
        // History and Reports
        Route::get('/history', [ScannerController::class, 'history'])->name('history');
        Route::get('/history/export', [ScannerController::class, 'exportHistory'])->name('history.export');

        // Scanner control routes
        Route::post('/scan/start', [ScannerController::class, 'startScanner'])->name('scan.start');
        Route::post('/scan/stop', [ScannerController::class, 'stopScanner'])->name('scan.stop');
        Route::get('/scan/status', [ScannerController::class, 'getScannerStatus'])->name('scan.status');
    });

    // Admin Routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
        
        // Events Management
        Route::middleware(['auth', 'role:admin'])->group(function () {
            Route::prefix('events')->name('events.')->group(function () {
                Route::get('/', [EventController::class, 'index'])->name('index');
                Route::get('/create', [EventController::class, 'create'])->name('create');
                Route::get('/{event}/guests', [TicketController::class, 'guests'])->name('guests.index');
                Route::post('/', [EventController::class, 'store'])->name('store');
                Route::get('/{event}', [EventController::class, 'show'])->name('show');
                Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
                Route::post('/{event}/public-registration', [PublicRegistrationController::class, 'manage'])->name('public-registration');
                Route::match(['put', 'patch'], '/{event}', [EventController::class, 'update'])->name('update');
                Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
                
                // Event tickets management
                Route::prefix('{event}/tickets')->name('tickets.')->group(function () {
                    Route::get('/', [TicketController::class, 'index'])->name('index');
                    Route::get('/data', [TicketController::class, 'getTicketsData'])->name('data');
                    Route::get('/create/{attendee}', [TicketController::class, 'create'])->name('create');
                    Route::post('/store/{attendee}', [TicketController::class, 'store'])->name('store');
                    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
                    Route::post('/generate', [TicketController::class, 'generate'])->name('generate');
                    Route::get('/{ticket}/print', [TicketController::class, 'print'])->name('print');
                    Route::get('/{ticket}/export', [TicketController::class, 'exportTicket'])->name('export-ticket');
                    Route::patch('/{ticket}/deactivate', [TicketController::class, 'deactivate'])->name('deactivate');
                    Route::get('/{ticket}/download-jpg', [TicketController::class, 'downloadJpg'])->name('download-jpg');
                });
                
                // Scanner Assignment Routes
                Route::post('/{event}/scanners/assign', [EventController::class, 'assignScanner'])->name('scanners.assign');
                Route::delete('/{event}/scanners/{scanner}', [EventController::class, 'removeScanner'])->name('scanners.remove');
                Route::put('/{event}/scanners/{scanner}', [EventController::class, 'updateScanner'])->name('scanners.update');

                // Event attendees management
                Route::prefix('{event}/attendees')->name('attendees.')->group(function () {
                    Route::get('/', [AttendeeController::class, 'index'])->name('index');
                    Route::get('/create', [AttendeeController::class, 'create'])->name('create');
                    Route::post('/', [AttendeeController::class, 'store'])->name('store');
                    Route::get('/{attendee}', [AttendeeController::class, 'show'])->name('show');
                    Route::get('/{attendee}/edit', [AttendeeController::class, 'edit'])->name('edit');
                    Route::put('/{attendee}', [AttendeeController::class, 'update'])->name('update');
                    Route::delete('/{attendee}', [AttendeeController::class, 'destroy'])->name('destroy');
                    Route::post('/{attendee}/check-in', [AdmissionController::class, 'prepare'])->name('check-in');
                    Route::post('/{attendee}/regenerate-qr', [AttendeeController::class, 'regenerateQrCode'])->name('regenerate-qr');
                    Route::patch('/{attendee}/cancel', [AttendeeController::class, 'cancel'])->name('cancel');

                    // Bulk Upload Routes
                    Route::prefix('bulk-upload')->name('bulk-upload.')->group(function () {
                        Route::get('/', [BulkUploadController::class, 'create'])->name('create');
                        Route::post('/', [BulkUploadController::class, 'store'])->name('store');
                        Route::get('/{bulkUpload}/status', [BulkUploadController::class, 'status'])->name('status');
                        Route::get('/{bulkUpload}/status/get', [BulkUploadController::class, 'getStatus'])->name('status.get');
                    })->where('bulkUpload', '[0-9]+');
                });
            });
            
            // Scanners Management
            Route::resource('scanners', ScannerController::class);

            // Reports
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
            Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        });

        // User Management Routes
        Route::middleware(['auth'])->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
            Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        });

        // Settings Routes
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache');
        Route::post('/settings/clear-logs', [SettingController::class, 'clearLogs'])->name('settings.clear-logs');

        // Card Template Management Routes
        Route::resource('card-templates', CardTemplateController::class);
        Route::get('/card-templates/{cardTemplate}/preview', [CardTemplateController::class, 'preview'])->name('card-templates.preview');
        Route::patch('/card-templates/{cardTemplate}/toggle-status', [CardTemplateController::class, 'toggleStatus'])->name('card-templates.toggle-status');
        Route::get('/card-templates/type/{type}', [CardTemplateController::class, 'getByType'])->name('card-templates.by-type');

        // Event Cards Management Routes
        Route::resource('event-cards', EventCardController::class);
        Route::get('/event-cards/{attendee}/download', [EventCardController::class, 'download'])->name('event-cards.download');
        Route::get('/event-cards/verify/{token}', [EventCardController::class, 'verify'])->name('event-cards.verify');

        // Ticket exports
        Route::get('/events/{event}/tickets/export/{format?}', [TicketController::class, 'exportAll'])->name('events.tickets.export');


        // Bulk Upload Routes
        Route::get('/events/{event}/attendees/bulk-upload', [BulkUploadController::class, 'create'])->name('events.attendees.bulk-upload');
        Route::post('/events/{event}/attendees/bulk-upload', [BulkUploadController::class, 'store'])->name('events.attendees.bulk-upload.store');
        Route::get('/events/{event}/attendees/bulk-upload/{bulkUpload}/status', [BulkUploadController::class, 'status'])->name('events.attendees.bulk-upload.status');
        Route::get('/events/{event}/attendees/bulk-upload/{bulkUpload}/status.json', [BulkUploadController::class, 'getStatus'])->name('events.attendees.bulk-upload.status.json');
    });

    // Email Verification Routes
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/')->with('status', 'email-verified');
    })->middleware(['auth', 'signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

    // Template download route (accessible to all authenticated users)
    Route::get('/events/attendees/template/{event?}', [BulkUploadController::class, 'downloadTemplate'])->name('events.attendees.template');

    // Attendee validation route
    Route::get('/attendees/validate/{token}', [AdmissionController::class, 'prepareToken'])
        ->name('attendees.validate');
});

// Public check-in route (no auth required)
Route::get('/check-in/{token}', [TicketController::class, 'verify'])->middleware('throttle:30,1')->name('scanner.check-in');

// Ticket Verification Route (Public)
Route::get('/tickets/{token}/verify', [App\Http\Controllers\TicketController::class, 'verify'])
    ->name('tickets.verify');

// SMS Management Routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('sms/management')->name('sms.management.')->group(function () {
    Route::get('/', [App\Http\Controllers\SmsManagementController::class, 'index'])->name('index');
    Route::get('/logs', [App\Http\Controllers\SmsManagementController::class, 'logs'])->name('logs');
    Route::get('/refresh-balance', [App\Http\Controllers\SmsManagementController::class, 'refreshBalance'])->name('refresh-balance');
    Route::post('/test', [App\Http\Controllers\SmsManagementController::class, 'sendTest'])->name('test');
    Route::get('/events/{event}', [App\Http\Controllers\SmsManagementController::class, 'showEvent'])->name('event');
    Route::post('/events/{event}/bulk-send', [App\Http\Controllers\SmsManagementController::class, 'bulkSend'])->name('event.bulk-send');
    Route::post('/events/{event}/send-attendee-invitation', [App\Http\Controllers\SmsManagementController::class, 'sendAttendeeInvitation'])->name('send-attendee-invitation');
});
