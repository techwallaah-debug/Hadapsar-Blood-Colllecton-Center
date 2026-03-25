<?php
// Hadapsar Blood Collection Center - Contact Form Handler
// This handles booking and contact form submissions

header('Content-Type: application/json');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate CSRF token (implement later with sessions)

// Get form data
$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$serviceType = sanitize_input($_POST['serviceType'] ?? '');
$testType = sanitize_input($_POST['testType'] ?? '');
$preferredDate = sanitize_input($_POST['preferredDate'] ?? '');
$preferredTime = sanitize_input($_POST['preferredTime'] ?? '');
$address = sanitize_input($_POST['address'] ?? '');
$requirements = sanitize_input($_POST['requirements'] ?? '');

// Validation
if (empty($name) || empty($phone) || empty($serviceType) || empty($testType) || empty($preferredDate) || empty($preferredTime)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate phone
if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $phone))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate date (should be future date)
$bookingDate = strtotime($preferredDate);
$today = strtotime(date('Y-m-d'));
if ($bookingDate < $today) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking date must be in the future']);
    exit;
}

// Save booking to database (if database is configured)
// For now, save to a file or email
$bookingData = [
    'booking_id' => 'BOOK-' . date('YmdHis') . '-' . substr(md5(uniqid()), 0, 6),
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'service_type' => $serviceType,
    'test_type' => $testType,
    'preferred_date' => $preferredDate,
    'preferred_time' => $preferredTime,
    'address' => $address,
    'requirements' => $requirements,
    'booking_date' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

// Save record to file (temporary solution)
$filename = 'bookings_' . date('Y-m-d') . '.csv';
$filepath = __DIR__ . '/../data/' . $filename;

// Create directory if not exists
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// CSV header
if (!file_exists($filepath)) {
    $header = "Booking ID,Name,Phone,Email,Service Type,Test Type,Preferred Date,Preferred Time,Address,Requirements,Booking Date,Status\n";
    file_put_contents($filepath, $header);
}

// Append booking data
$csvLine = implode(',', [
    $bookingData['booking_id'],
    '"' . addslashes($bookingData['name']) . '"',
    $bookingData['phone'],
    $bookingData['email'],
    $bookingData['service_type'],
    $bookingData['test_type'],
    $bookingData['preferred_date'],
    $bookingData['preferred_time'],
    '"' . addslashes($bookingData['address']) . '"',
    '"' . addslashes($bookingData['requirements']) . '"',
    $bookingData['booking_date'],
    $bookingData['status']
]) . "\n";

file_put_contents($filepath, $csvLine, FILE_APPEND);

// Send confirmation email
send_confirmation_email($bookingData);

// Send WhatsApp notification (if WhatsApp API is integrated)
// send_whatsapp_notification($bookingData);

// Return success response
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Booking confirmed! We will contact you soon.',
    'booking_id' => $bookingData['booking_id']
]);

// Helper Functions

/**
 * Sanitize input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

/**
 * Send confirmation email to customer
 */
function send_confirmation_email($data) {
    $to = !empty($data['email']) ? $data['email'] : '';
    
    if (empty($to)) {
        return false;
    }

    $subject = 'Booking Confirmation - Hadapsar Blood Collection Center';
    
    $message = "
    <html>
    <head>
        <title>Booking Confirmation</title>
    </head>
    <body style='font-family: Arial, sans-serif;'>
        <h2 style='color: #C0392B;'>Booking Confirmation</h2>
        <p>Dear {$data['name']},</p>
        <p>Thank you for booking with Hadapsar Blood Collection Center.</p>
        
        <h3>Booking Details:</h3>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Booking ID:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['booking_id']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Service Type:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . ucfirst(str_replace('_', ' ', $data['service_type'])) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Test Type:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . ucfirst(str_replace('_', ' ', $data['test_type'])) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Date:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['preferred_date']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Time Slot:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . ucfirst(str_replace('_', ' ', $data['preferred_time'])) . "</td>
            </tr>
        </table>
        
        <p style='margin-top: 20px;'>We will confirm your appointment soon. If you have any questions, please call us at <strong>+91 93569 55601</strong></p>
        
        <p>Best regards,<br/>Hadapsar Blood Collection Center Team</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: info@hadapsarbloodcenter.com\r\n";

    mail($to, $subject, $message, $headers);
}

/**
 * Send WhatsApp notification (placeholder for WhatsApp Business API integration)
 */
function send_whatsapp_notification($data) {
    // Integration with WhatsApp Business API
    // This is a placeholder - implement with your WhatsApp API credentials
    
    $message = "Hi {$data['name']}, your booking (ID: {$data['booking_id']}) has been confirmed for {$data['preferred_date']} at {$data['preferred_time']}. We'll contact you soon. - Hadapsar Blood Center";
    
    // TODO: Implement WhatsApp API call
    // Example: Send to WhatsApp API endpoint
}
?>
