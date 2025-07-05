<?php

echo "Phone Number Formatting Test\n";
echo "============================\n\n";

// Test phone number formatting logic
function formatPhoneNumber(?string $phone): ?string
{
    if (empty($phone)) {
        return null;
    }

    // Remove any spaces, dashes, parentheses, or other non-digit characters
    $phone = preg_replace('/[^\d]/', '', $phone);

    // If it's already in the correct format (255XXXXXXXXX), return as is
    if (preg_match('/^255\d{9}$/', $phone)) {
        return $phone;
    }

    // If it starts with 0, replace with 255 (Tanzania country code)
    if (preg_match('/^0(\d{9})$/', $phone, $matches)) {
        return '255' . $matches[1];
    }

    // If it's 9 digits, assume it's a local number and add 255
    if (preg_match('/^(\d{9})$/', $phone)) {
        return '255' . $phone;
    }

    // If it's already 12 digits but doesn't start with 255, assume it's already formatted
    if (preg_match('/^(\d{12})$/', $phone)) {
        return $phone;
    }

    // If it starts with +255, remove the + and return
    if (preg_match('/^\+255(\d{9})$/', $phone, $matches)) {
        return '255' . $matches[1];
    }

    // If none of the above patterns match, return null
    return null;
}

// Test cases
$testNumbers = [
    '(076) 928-9824',  // Original format from log
    '0769289824',      // Local format
    '+255769289824',   // International format with +
    '255769289824',    // Already formatted
    '769289824',       // 9 digits
    '123456789',       // Invalid format
    '',                // Empty
    null,              // Null
];

echo "Phone Number Formatting Test\n";
echo "============================\n\n";

foreach ($testNumbers as $number) {
    $formatted = formatPhoneNumber($number);
    $status = $formatted ? '✅ Valid' : '❌ Invalid';
    echo sprintf("%-20s -> %-15s %s\n", 
        $number ?: 'null', 
        $formatted ?: 'null', 
        $status
    );
}

echo "\nExpected result for '(076) 928-9824' should be '255769289824'\n"; 