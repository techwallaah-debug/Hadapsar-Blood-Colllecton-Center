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
$packageInfo = sanitize_input($_POST['package_info'] ?? '');

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

$filename = 'bookings_' . date('Y-m-d') . '.csv';
$filepath = __DIR__ . '/../data/' . $filename;

if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

if (!file_exists($filepath)) {
    $header = "Booking ID,Name,WhatsApp,Location,Package Info,Booking Date,Status\n";
    file_put_contents($filepath, $header);
}

$sequence = 1;
if (file_exists($filepath)) {
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines) && count($lines) > 1) {
        $sequence = count($lines);
    }
}

$bookingId = date('Y/m/d/H:i') . '/' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);

$bookingData = [
    'booking_id' => $bookingId,
    'name' => $name,
    'phone' => $normalizedPhone,
    'location' => $location,
    'package_info' => $packageInfo,
    'booking_date' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

$csvLine = implode(',', [
    $bookingData['booking_id'],
    '"' . addslashes($bookingData['name']) . '"',
    $bookingData['phone'],
    '"' . addslashes($bookingData['location']) . '"',
    '"' . addslashes($bookingData['package_info']) . '"',
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
    $subject = 'New Home Collection Booking | ' . $data['booking_id'] . ' | ' . $data['name'];

    $message = "
    <html>
    <head>
        <title>New Booking Notification</title>
    </head>
    <body style='font-family: Arial, sans-serif; color: #111827; background: #f9fafb; padding: 24px;'>
        <div style='max-width: 700px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;'>
            <div style='background: #b91c1c; color: #ffffff; padding: 16px 20px;'>
                <div style='font-size: 14px; letter-spacing: 0.08em; text-transform: uppercase;'>New Booking Alert</div>
                <div style='font-size: 20px; font-weight: 700; margin-top: 6px;'>New Home Collection Booking</div>
            </div>

            <div style='padding: 20px;'>
                <table style='border-collapse: collapse; width: 100%; font-size: 14px;'>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Booking ID</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['booking_id']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Name</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>WhatsApp Number</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['phone']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Location</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['location']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Package</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['package_info']}</td>
                    </tr>
                </table>

                <p style='margin-top: 18px; font-size: 13px; color: #6b7280;'>Please follow up with the client promptly.</p>
                <p style='margin-top: 14px; font-size: 13px; color: #9ca3af;'>Hadapsar Blood Collection Center Website</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";

    return mail($to, $subject, $message, $headers);
}

function build_whatsapp_message($data) {
    $message = "New Booking Alert: New Home Collection Booking";
    $message .= "\nBooking ID: {$data['booking_id']}";
    $message .= "\nName: {$data['name']}";
    $message .= "\nWhatsApp Number: {$data['phone']}";
    $message .= "\nLocation: {$data['location']}";
    if (!empty($data['package_info'])) {
        $message .= "\nPackage: {$data['package_info']}";
    }
    return $message;
}
?>
