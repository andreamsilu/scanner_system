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
use App\Http\Controllers\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\SmsManagementController;

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
    Route::post('login', [LoginController::class, 'login']);
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
        Route::post('/scan/validate', [ScannerController::class, 'validateToken'])->name('scan.validate');
        
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
                Route::post('/', [EventController::class, 'store'])->name('store');
                Route::get('/{event}', [EventController::class, 'show'])->name('show');
                Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
                Route::match(['put', 'patch'], '/{event}', [EventController::class, 'update'])->name('update');
                Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
                
                // Event tickets management
                Route::prefix('{event}/tickets')->name('events.tickets.')->group(function () {
                    Route::get('/', [TicketController::class, 'index'])->name('index');
                    Route::get('/data', [TicketController::class, 'getTicketsData'])->name('data');
                    Route::get('/create/{attendee}', [TicketController::class, 'create'])->name('create');
                    Route::post('/store/{attendee}', [TicketController::class, 'store'])->name('store');
                    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
                    Route::post('/generate', [TicketController::class, 'generate'])->name('generate');
                    Route::get('/{ticket}/print', [TicketController::class, 'print'])->name('print');
                    Route::get('/{ticket}/export', [TicketController::class, 'exportTicket'])->name('export');
                    Route::patch('/{ticket}/deactivate', [TicketController::class, 'deactivate'])->name('deactivate');
                });
                
                // Scanner Assignment Routes
                Route::post('/{event}/scanners/assign', [EventController::class, 'assignScanner'])->name('scanners.assign');
                Route::delete('/{event}/scanners/{scanner}', [EventController::class, 'removeScanner'])->name('scanners.remove');
                Route::put('/{event}/scanners/{scanner}', [EventController::class, 'updateScanner'])->name('scanners.update');

                // Event attendees management
                Route::get('/{event}/attendees', [AttendeeController::class, 'index'])->name('attendees.index');
                Route::get('/{event}/attendees/create', [AttendeeController::class, 'create'])->name('attendees.create');
                Route::post('/{event}/attendees', [AttendeeController::class, 'store'])->name('attendees.store');
                Route::get('/{event}/attendees/{attendee}', [AttendeeController::class, 'show'])->name('attendees.show');
                Route::get('/{event}/attendees/{attendee}/edit', [AttendeeController::class, 'edit'])->name('attendees.edit');
                Route::put('/{event}/attendees/{attendee}', [AttendeeController::class, 'update'])->name('attendees.update');
                Route::delete('/{event}/attendees/{attendee}', [AttendeeController::class, 'destroy'])->name('attendees.destroy');
                Route::post('/{event}/attendees/{attendee}/check-in', [AttendeeController::class, 'checkIn'])->name('attendees.check-in');
                Route::post('/{event}/attendees/{attendee}/regenerate-qr', [AttendeeController::class, 'regenerateQrCode'])->name('attendees.regenerate-qr');
                Route::patch('/{event}/attendees/{attendee}/cancel', [AttendeeController::class, 'cancel'])->name('attendees.cancel');

                // Bulk Upload Routes (moved inside events prefix group)
                Route::get('/{event}/attendees/bulk-upload', [BulkUploadController::class, 'create'])->name('attendees.bulk-upload.create');
                Route::post('/{event}/attendees/bulk-upload', [BulkUploadController::class, 'store'])->name('attendees.bulk-upload.store');
                Route::get('/{event}/attendees/bulk-upload/{bulkUpload}/status', [BulkUploadController::class, 'status'])
                    ->name('attendees.bulk-upload.status');
                Route::get('/{event}/attendees/bulk-upload/{bulkUpload}/status/get', [BulkUploadController::class, 'getStatus'])
                    ->name('attendees.bulk-upload.status.get');
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

        // Settings Routes
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache');
        Route::post('/settings/clear-logs', [SettingController::class, 'clearLogs'])->name('settings.clear-logs');

        // Ticket exports
        Route::get('/tickets/export/{format?}', [TicketController::class, 'exportAll'])->name('tickets.export');
    });

    // Attendee validation route
    Route::get('/attendees/validate/{token}', [App\Http\Controllers\AttendeeController::class, 'validateToken'])
        ->name('attendees.validate');
});

// Public check-in route (no auth required)
Route::get('/check-in/{token}', [ScanController::class, 'checkIn'])->name('scanner.check-in');

// Ticket Verification Route (Public)
Route::get('/tickets/{token}/verify', [App\Http\Controllers\TicketController::class, 'verify'])
    ->name('tickets.verify');

// SMS Management Routes
Route::middleware(['auth', 'verified'])->prefix('sms/management')->name('sms.management.')->group(function () {
    Route::get('/', [App\Http\Controllers\SmsManagementController::class, 'index'])->name('index');
    Route::get('/logs', [App\Http\Controllers\SmsManagementController::class, 'logs'])->name('logs');
    Route::get('/refresh-balance', [App\Http\Controllers\SmsManagementController::class, 'refreshBalance'])->name('refresh-balance');
    Route::post('/test', [App\Http\Controllers\SmsManagementController::class, 'sendTest'])->name('test');
    Route::get('/events/{event}', [App\Http\Controllers\SmsManagementController::class, 'showEvent'])->name('event');
    Route::post('/events/{event}/bulk-send', [App\Http\Controllers\SmsManagementController::class, 'bulkSend'])->name('event.bulk-send');
    Route::post('/events/{event}/send-attendee-invitation', [App\Http\Controllers\SmsManagementController::class, 'sendAttendeeInvitation'])->name('send-attendee-invitation');
});

// Temporary SMS Test Route
Route::get('/test-sms-send', function() {
    try {
        $smsService = app(App\Services\NextSmsService::class);
        $phone = '+255754289824'; // Format the number with country code
        $message = 'Test message from Scannable Event System at ' . now()->format('Y-m-d H:i:s');
        
        $result = $smsService->send($phone, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'Test SMS sent',
            'result' => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send SMS',
            'error' => $e->getMessage()
        ], 500);
    }
});
