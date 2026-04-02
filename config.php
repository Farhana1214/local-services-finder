<?php
// Configuration file for the Service Finder Application

// Site Configuration
define('SITE_NAME', 'Service Finder Pakistan');
define('SITE_URL', 'http://localhost/mids/');
define('CURRENCY', 'Rs');
define('TIMEZONE', 'Asia/Karachi');

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Pagination
define('ITEMS_PER_PAGE', 12);
define('SEARCH_RESULTS_PER_PAGE', 20);

// File Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_DIR', 'uploads/');

// Rating System
define('MIN_RATING', 1);
define('MAX_RATING', 5);

// Booking Status
define('BOOKING_STATUS_PENDING', 'pending');
define('BOOKING_STATUS_CONFIRMED', 'confirmed');
define('BOOKING_STATUS_IN_PROGRESS', 'in_progress');
define('BOOKING_STATUS_COMPLETED', 'completed');
define('BOOKING_STATUS_CANCELLED', 'cancelled');

// Payment Status
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_COMPLETED', 'completed');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// User Types
define('USER_TYPE_CUSTOMER', 'customer');
define('USER_TYPE_SERVICE_PROVIDER', 'service_provider');

// Location Configuration
define('USER_DEFAULT_CITY', 'Jauharabad');
define('USER_DEFAULT_PROVINCE', 'Punjab');
define('ENABLE_GEOLOCATION', true);
define('SHOW_NEARBY_SERVICES', true);
define('NEARBY_DISTANCE_KM', 50);

// Email Configuration (optional)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-password');
define('FROM_EMAIL', 'noreply@servicefinder.com');
define('FROM_NAME', 'Service Finder');

// Admin Settings
define('ADMIN_EMAIL', 'admin@servicefinder.com');
define('SUPPORT_EMAIL', 'support@servicefinder.com');

// Service Commission (percentage)
define('SERVICE_COMMISSION', 10); // 10%

// Default values
define('DEFAULT_DISTANCE_RADIUS', 50); // km

// Set timezone
date_default_timezone_set(TIMEZONE);

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>
