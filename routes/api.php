// Remove these routes as they are now handled by web routes
// Route::prefix('sms')->middleware(['auth:sanctum'])->group(function () {
//     Route::post('/attendee-invitation', [SmsController::class, 'sendAttendeeInvitation']);
//     Route::post('/ticket-usage', [SmsController::class, 'sendTicketUsageNotification']);
//     Route::post('/event-reminder', [SmsController::class, 'sendEventReminder']);
//     Route::get('/templates', [SmsController::class, 'getTemplates']);
//     Route::get('/logs', [SmsController::class, 'getLogs']);
// }); 