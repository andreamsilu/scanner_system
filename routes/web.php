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

// Authentication Routes
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
    
    // Admin Routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
        
        // Events Management
        Route::middleware(['auth', 'role:admin'])->group(function () {
            Route::get('/events', [EventController::class, 'index'])->name('events.index');
            Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
            Route::post('/events', [EventController::class, 'store'])->name('events.store');
            Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
            Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
            Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
            Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

            // Event tickets routes
            Route::get('events/{event}/tickets', [EventController::class, 'tickets'])->name('events.tickets');
            Route::post('events/{event}/tickets/generate', [TicketController::class, 'generate'])->name('tickets.generate');
            Route::patch('tickets/{ticket}/deactivate', [TicketController::class, 'deactivate'])->name('tickets.deactivate');
            Route::get('tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');
            Route::get('tickets/{ticket}/export', [TicketController::class, 'export'])->name('tickets.export');
            Route::resource('tickets', TicketController::class);

            // Event attendees routes
            Route::get('/events/{event}/attendees', [EventController::class, 'attendees'])->name('events.attendees');
            Route::get('/events/{event}/attendees/create', [AttendeeController::class, 'create'])->name('events.attendees.create');
            Route::post('/events/{event}/attendees', [AttendeeController::class, 'store'])->name('events.attendees.store');
            Route::get('/events/{event}/attendees/{attendee}', [AttendeeController::class, 'show'])->name('events.attendees.show');
            Route::get('/events/{event}/attendees/{attendee}/edit', [AttendeeController::class, 'edit'])->name('events.attendees.edit');
            Route::put('/events/{event}/attendees/{attendee}', [AttendeeController::class, 'update'])->name('events.attendees.update');
            Route::delete('/events/{event}/attendees/{attendee}', [AttendeeController::class, 'destroy'])->name('events.attendees.destroy');
            Route::get('/events/{event}/attendees/bulk-upload', [AttendeeController::class, 'bulkUploadForm'])->name('events.attendees.bulk-upload');
            Route::post('/events/{event}/attendees/bulk-upload', [AttendeeController::class, 'bulkUpload'])->name('events.attendees.bulk-upload.store');
        });
        
        // Scanners Management
        Route::resource('scanners', ScannerController::class);

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    });
    
    // Scanner Routes
    Route::middleware('role:scanner')->group(function () {
        Route::get('/scanner/dashboard', [DashboardController::class, 'scannerDashboard'])->name('scanner.dashboard');
        Route::get('/scanner/events', [ScannerController::class, 'events'])->name('scanner.events.index');
        Route::get('/scanner/scan', [ScanController::class, 'index'])->name('scanner.scan.index');
        Route::get('/scanner/history', [ScanController::class, 'history'])->name('scanner.history.index');
    });

    // Scan routes
    Route::prefix('events/{event}/scans')->name('events.scans.')->group(function () {
        Route::post('/', [ScanController::class, 'scan'])->name('scan');
        Route::get('/', [ScanController::class, 'index'])->name('index');
        Route::get('{scan}', [ScanController::class, 'show'])->name('show');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
