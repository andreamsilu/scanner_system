#!/bin/bash

# Create the qr-codes directory if it doesn't exist
mkdir -p storage/app/public/qr-codes

# Set proper permissions for the storage directory
chmod -R 775 storage/app/public
chmod -R 775 storage/app/public/qr-codes

# Create or recreate the storage link
php artisan storage:link

# Set proper permissions for the public storage link
chmod -R 775 public/storage

# Output the current status
echo "Storage setup completed. Checking status..."
echo "----------------------------------------"
echo "QR codes directory exists: $(test -d storage/app/public/qr-codes && echo 'Yes' || echo 'No')"
echo "Storage link exists: $(test -L public/storage && echo 'Yes' || echo 'No')"
echo "Storage link target: $(readlink public/storage)"
echo "QR codes directory permissions: $(ls -ld storage/app/public/qr-codes)"
echo "Public storage permissions: $(ls -ld public/storage)"
echo "----------------------------------------"
echo "If you see any issues above, please run these commands with sudo on the remote server." 