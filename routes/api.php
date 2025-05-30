// SMS Routes
Route::prefix('sms')->group(function () {
    Route::post('/attendee-invitation', [SmsController::class, 'sendAttendeeInvitation']);
    Route::post('/ticket-usage', [SmsController::class, 'sendTicketUsageNotification']);
    Route::post('/event-reminder', [SmsController::class, 'sendEventReminder']);
    Route::get('/templates', [SmsController::class, 'getTemplates']);
    Route::get('/logs', [SmsController::class, 'getLogs']);
}); 