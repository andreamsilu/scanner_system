#!/bin/bash

# Create the qr-codes directory if it doesn't exist
mkdir -p storage/app/public/qr-codes

# Set proper permissions for the storage directory
chmod -R 775 storage/app/public
chmod -R 775 storage/app/public/qr-codes

# Remove existing storage link if it exists
if [ -L "public/storage" ]; then
    echo "Removing existing storage link..."
    rm public/storage
fi

# Create the storage link
php artisan storage:link

# Set proper ownership and permissions
echo "Setting ownership and permissions..."
chown -R www-data:www-data storage/app/public
chown -R www-data:www-data public/storage
chmod -R 775 storage/app/public
chmod -R 775 public/storage

# Output the current status
echo "Storage setup completed. Checking status..."
echo "----------------------------------------"
echo "QR codes directory exists: $(test -d storage/app/public/qr-codes && echo 'Yes' || echo 'No')"
echo "Storage link exists: $(test -L public/storage && echo 'Yes' || echo 'No')"
echo "Storage link target: $(readlink public/storage)"
echo "QR codes directory permissions: $(ls -ld storage/app/public/qr-codes)"
echo "Public storage permissions: $(ls -ld public/storage)"
echo "Storage directory owner: $(ls -ld storage/app/public | awk '{print $3":"$4}')"
echo "Public storage owner: $(ls -ld public/storage | awk '{print $3":"$4}')"
echo "----------------------------------------"

# Check if web server can write to the directory
echo "Testing write permissions..."
if sudo -u www-data touch storage/app/public/qr-codes/test.txt 2>/dev/null; then
    echo "Write permission test: SUCCESS"
    sudo -u www-data rm storage/app/public/qr-codes/test.txt
else
    echo "Write permission test: FAILED - Web server cannot write to the directory"
    echo "Please run this script with sudo: sudo ./setup-qr-storage.sh"
fi 