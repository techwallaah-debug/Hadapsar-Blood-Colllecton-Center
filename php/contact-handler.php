<?php
// Hadapsar Blood Collection Center - Contact Form Handler
// Handles booking submissions from the simplified contact form

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$location = sanitize_input($_POST['location'] ?? '');

if ($name === '' || $phone === '' || $location === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$normalizedPhone = preg_replace('/\D/', '', $phone);
if (!preg_match('/^\d{10}$/', $normalizedPhone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

$bookingData = [
    'booking_id' => 'BOOK-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
    'name' => $name,
    'phone' => $normalizedPhone,
    'location' => $location,
    'booking_date' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

$filename = 'bookings_' . date('Y-m-d') . '.csv';
$filepath = __DIR__ . '/../data/' . $filename;

if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

if (!file_exists($filepath)) {
    $header = "Booking ID,Name,WhatsApp,Location,Booking Date,Status\n";
    file_put_contents($filepath, $header);
}

$csvLine = implode(',', [
    $bookingData['booking_id'],
    '"' . addslashes($bookingData['name']) . '"',
    $bookingData['phone'],
    '"' . addslashes($bookingData['location']) . '"',
    $bookingData['booking_date'],
    $bookingData['status']
]) . "\n";

file_put_contents($filepath, $csvLine, FILE_APPEND);

$emailSent = send_admin_email($bookingData);
$whatsappMessage = build_whatsapp_message($bookingData);
$whatsappUrl = 'https://wa.me/' . BUSINESS_WHATSAPP . '?text=' . rawurlencode($whatsappMessage);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Booking received! Your Booking ID is ' . $bookingData['booking_id'],
    'booking_id' => $bookingData['booking_id'],
    'email_sent' => $emailSent,
    'whatsapp_url' => $whatsappUrl
]);

function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function send_admin_email($data) {
    $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : BUSINESS_EMAIL;
    $subject = 'New Booking ' . $data['booking_id'] . ' - ' . BUSINESS_NAME;

    $message = "
    <html>
    <head>
        <title>New Booking Notification</title>
    </head>
    <body style='font-family: Arial, sans-serif; color: #1f2937;'>
        <h2 style='color: #C0392B;'>New Booking Received</h2>
        <p>A new booking has been submitted from the website.</p>

        <table style='border-collapse: collapse; width: 100%; max-width: 700px;'>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Booking ID:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['booking_id']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Name:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['name']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>WhatsApp Number:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['phone']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Location:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['location']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Submitted At:</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['booking_date']}</td>
            </tr>
        </table>

        <p style='margin-top: 20px;'>Please follow up with the customer promptly.</p>
        <p style='margin-top: 16px;'>Regards,<br/>Hadapsar Blood Collection Center Website</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";

    return mail($to, $subject, $message, $headers);
}

function build_whatsapp_message($data) {
    return "New Booking Alert\nBooking ID: {$data['booking_id']}\nName: {$data['name']}\nWhatsApp Number: {$data['phone']}\nLocation: {$data['location']}\nSubmitted At: {$data['booking_date']}";
}
?>
