# SMS Implementation Summary

## Overview
This document summarizes the automatic SMS sending functionality implemented for the Scannable Event System. The system now automatically sends SMS messages in two key scenarios:

1. **Invitation SMS** - Sent when tickets are created
2. **Notification SMS** - Sent when tickets are scanned/used

## Implementation Details

### 1. SMS Architecture

#### Core Components
- **SmsServiceInterface** - Contract defining SMS provider methods
- **BaseSmsService** - Abstract base class with common functionality
- **NextSmsService** - Concrete implementation for NextSMS provider
- **SmsService** - Main service orchestrating SMS operations
- **SendSingleSms** - Job for sending individual SMS messages
- **SendBulkSms** - Job for sending multiple SMS messages
#### Key Features
- ✅ **Queue-based processing** - SMS sent asynchronously via Laravel jobs
- ✅ **Retry mechanism** - Failed SMS automatically retried up to 3 times
- ✅ **Comprehensive logging** - All SMS activities logged to database and files
- ✅ **Error handling** - Graceful error handling without affecting main operations
- ✅ **Rate limiting** - Built-in rate limiting to prevent API abuse

### 2. SMS Triggers

#### A. Ticket Creation (Invitation SMS)
**When:** Tickets are created for attendees
**Where:** 
- `TicketController::store()` - Individual ticket creation
- `AttendeeController::store()` - Ticket creation during attendee registration
- `BulkUploadController::processUpload()` - Ticket creation during bulk upload

**Message Format:**
```
Ndugu [Name], unakaribishwa kwenye [Event] itakayofanyika [Date] saa [Time]. 
Mahali: [Location]. Ukumbi: [Venue]. Tiketi: [TicketNumber] ([Type]). 
Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824
```

#### B. Ticket Scanning (Notification SMS)
**When:** Tickets are successfully scanned/validated
**Where:**
- `ScannerController::processSuccessfulValidation()` - QR code and manual scanning
- `ScanController::scan()` - Public QR code scanning
- `ScanController::validateToken()` - Manual token validation

**Message Format:**
```
Ndugu [Name], tiketi yako ya [Event] imethibitishwa na kuingia kupitia [EntryPoint] 
saa [Time]. Asante kwa kuhudhuria [Event]. Karibu tena!
```

### 3. Configuration

#### Environment Variables
```env
# NextSMS Configuration
NEXTSMS_API_URL=https://messaging-service.co.tz/api/sms/v1
NEXTSMS_USERNAME=your_username
NEXTSMS_PASSWORD=your_password
NEXTSMS_SENDER_ID=EVENT
SMS_DEFAULT_COUNTRY_CODE=255

# SMS Logging
SMS_LOG_LEVEL=debug
```

#### Service Provider Registration
```php
// app/Providers/SmsServiceProvider.php
$this->app->singleton(SmsServiceInterface::class, function ($app) {
    return new NextSmsService();
});

$this->app->singleton(SmsService::class, function ($app) {
    return new SmsService($app->make(SmsServiceInterface::class));
});
```

### 4. Database Schema

#### SMS Logs Table
```sql
CREATE TABLE sms_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    recipient VARCHAR(255),
    sender_id VARCHAR(255),
    message TEXT,
    status VARCHAR(255),
    provider_status VARCHAR(255),
    provider_message TEXT,
    message_id VARCHAR(255),
    reference VARCHAR(255),
    delivered_at TIMESTAMP NULL,
    last_status_check TIMESTAMP NULL,
    status_check_count INT DEFAULT 0,
    error TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

#### SMS Templates Table
```sql
CREATE TABLE sms_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(255),
    name VARCHAR(255),
    content TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 5. Job Configuration

#### Queue Configuration
```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
    ],
],
```

#### Job Settings
- **Retry Attempts:** 3 times
- **Backoff Delay:** 30-60 seconds between retries
- **Queue:** Default queue
- **Timeout:** 30 seconds per SMS

### 6. Security Features

#### Rate Limiting
- **Individual SMS:** 60 SMS per minute per user
- **Bulk SMS:** 100 SMS per minute per user
- **API Rate Limiting:** Built into NextSMS service

#### Input Validation
- Phone number format validation (E.164)
- Message content sanitization
- Metadata validation

#### Error Handling
- Graceful degradation - SMS failures don't affect main operations
- Comprehensive error logging
- Automatic retry mechanism

### 7. Monitoring & Logging

#### Log Channels
- **SMS Logs:** `storage/logs/sms.log`
- **Database Logs:** `sms_logs` table
- **Activity Logs:** Laravel's main log file

#### Log Information
- Recipient phone number
- Message content
- Delivery status
- Provider response
- Error details
- Metadata (event, attendee, ticket info)

### 8. Testing

#### Test Routes
- `/test-sms-send` - Test direct SMS sending
- `/test-sms-job` - Test SMS job dispatching

#### Manual Testing
1. Create a ticket for an attendee with phone number
2. Check SMS logs for invitation message
3. Scan the ticket
4. Check SMS logs for notification message

### 9. Performance Considerations

#### Optimization Features
- **Asynchronous Processing:** SMS sent via jobs, not blocking main operations
- **Batch Processing:** Bulk SMS support for multiple recipients
- **Caching:** SMS templates and configuration cached
- **Database Indexing:** Optimized queries for SMS logs

#### Scalability
- **Queue Workers:** Can run multiple queue workers for high volume
- **Load Balancing:** SMS jobs distributed across workers
- **Rate Limiting:** Prevents API overload

### 10. Maintenance

#### Regular Tasks
- Monitor SMS delivery rates
- Check SMS balance/credits
- Review failed SMS logs
- Update SMS templates as needed
- Monitor queue performance

#### Troubleshooting
- Check SMS logs for delivery failures
- Verify API credentials
- Monitor queue worker status
- Check phone number formats

## Usage Examples

### Creating a Ticket with SMS
```php
// Ticket creation automatically triggers invitation SMS
$ticket = Ticket::create([
    'event_id' => $event->id,
    'attendee_id' => $attendee->id,
    'type' => 'regular',
    'status' => 'active',
    'ticket_number' => $ticketNumber,
    'token' => $attendee->token
]);

// SMS job is automatically dispatched if attendee has phone number
```

### Scanning a Ticket with SMS
```php
// Ticket scanning automatically triggers notification SMS
$ticket->update([
    'status' => 'used',
    'used_at' => now()
]);

// SMS job is automatically dispatched if attendee has phone number
```

### Manual SMS Sending
```php
// Send custom SMS
$smsService = app(SmsService::class);
$result = $smsService->send(
    '+255123456789',
    'Custom message content',
    'custom',
    ['metadata' => 'value']
);
```

## Conclusion

The SMS implementation provides a robust, scalable solution for automatic communication with event attendees. The system ensures reliable delivery through queue-based processing, comprehensive logging, and error handling, while maintaining security through rate limiting and input validation.

The implementation follows Laravel best practices and integrates seamlessly with the existing event management system, providing a complete solution for attendee communication throughout the event lifecycle. 