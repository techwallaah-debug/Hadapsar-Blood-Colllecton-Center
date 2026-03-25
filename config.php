<?php
/**
 * Configuration File
 * Hadapsar Blood Collection Center Website
 */

// Business Configuration
define('BUSINESS_NAME', 'Hadapsar Blood Collection Center');
define('BUSINESS_PHONE', '+91 93569 55601');
define('BUSINESS_EMAIL', 'info@hadapsarbloodcenter.com');
define('BUSINESS_WHATSAPP', '919356955601'); // Without + sign
define('BUSINESS_ADDRESS', 'Hadapsar, Pune, Maharashtra');

// Opening Hours
define('OPENING_MORNING_START', '06:00');
define('OPENING_MORNING_END', '10:00');
define('OPENING_EVENING_START', '16:00');
define('OPENING_EVENING_END', '19:00');

// Doctor Information
define('DOCTOR_NAME', 'Dr. Pannu Bhaware');
define('DOCTOR_QUALIFICATION', 'B.Sc. MLT');

// Service Areas
$SERVICE_AREAS = [
    'Hadapsar',
    'Mundhwa',
    'Kharadi',
    'Magarpatta'
];

// Branding
define('PRIMARY_COLOR', '#C0392B');    // Red
define('SECONDARY_COLOR', '#1A3C6E');  // Navy Blue
define('ACCENT_COLOR', '#FFFFFF');     // White
define('LIGHT_COLOR', '#F2F3F4');      // Light Gray

// Website
define('WEBSITE_URL', 'http://localhost/Hadapsar-Blood-Colllecton-Center');
define('SITE_TITLE', 'Hadapsar Blood Collection Center');
define('SITE_DESCRIPTION', 'Professional blood collection and diagnostic testing center in Hadapsar, Pune');

// Database (for future use)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hadapsar_blood');

// Email Configuration
define('MAIL_FROM', 'noreply@hadapsarbloodcenter.com');
define('MAIL_FROM_NAME', 'Hadapsar Blood Center');
define('ADMIN_EMAIL', 'admin@hadapsarbloodcenter.com');

// WhatsApp Configuration
define('WHATSAPP_NUMBER', '+91 93569 55601');
define('WHATSAPP_API_URL', ''); // Add WhatsApp API endpoint if using

// Security
define('ENABLE_CSRF_PROTECTION', true);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 10);

// File Upload Limits
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

?>
