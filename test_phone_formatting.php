<?php

require_once 'vendor/autoload.php';

use App\Services\BaseSmsService;

// Create a test class that extends BaseSmsService for testing
class TestSmsService extends BaseSmsService
{
    public function getProviderName(): string
    {
        return 'test';
    }
    
    public function send(string $to, string $message, array $options = []): array
    {
        // Mock implementation for testing
        return ['success' => true, 'message_id' => 'test_123'];
    }
    
    public function getBalance(): array
    {
        // Mock implementation for testing
        return ['success' => true, 'credits' => 100];
    }
    
    public function getStatus(string $messageId): array
    {
        // Mock implementation for testing
        return ['success' => true, 'status' => 'delivered'];
    }
    
    public function testFormatPhoneNumber(string $number): string
    {
        return $this->formatPhoneNumber($number);
    }
    
    public function testIsValidPhoneNumber(string $number): bool
    {
        return $this->isValidPhoneNumber($number);
    }
}

echo "Testing Phone Number Formatting\n";
echo "===============================\n\n";

$service = new TestSmsService();

$testNumbers = [
    '0712345678',
    '712345678', 
    '255712345678',
    '+255712345678',
    '769289824',
    '0769289824',
    '255769289824',
    '123456789',
    '0123456789',
    '255123456789'
];

foreach ($testNumbers as $number) {
    try {
        $formatted = $service->testFormatPhoneNumber($number);
        $isValid = $service->testIsValidPhoneNumber($number);
        
        echo sprintf(
            "Original: %-15s -> Formatted: %-15s -> Valid: %s\n",
            $number,
            $formatted,
            $isValid ? 'YES' : 'NO'
        );
    } catch (Exception $e) {
        echo sprintf(
            "Original: %-15s -> ERROR: %s\n",
            $number,
            $e->getMessage()
        );
    }
}

echo "\nExpected format: 255XXXXXXXXX (12 digits starting with 255)\n";
echo "Valid mobile prefixes: 6, 7, 8\n"; 