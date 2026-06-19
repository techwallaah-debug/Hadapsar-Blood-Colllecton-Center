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
$age = sanitize_input($_POST['age'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$bookingDate = sanitize_input($_POST['booking_date'] ?? '');
$preferredSlot = sanitize_input($_POST['preferred_slot'] ?? '');
$collectionAddress = sanitize_input($_POST['collection_address'] ?? '');
$packageName = sanitize_input($_POST['package_name'] ?? '');
$packagePrice = sanitize_input($_POST['package_price'] ?? '');
$packageInfo = sanitize_input($_POST['package_info'] ?? '');

if ($name === '' || $age === '' || $phone === '' || $bookingDate === '' || $preferredSlot === '' || $collectionAddress === '') {
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

if (!preg_match('/^\d{1,3}$/', preg_replace('/\D/', '', $age))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid age']);
    exit;
}

$normalizedAge = preg_replace('/\D/', '', $age);
$normalizedBookingDate = date_create_from_format('Y-m-d', $bookingDate) ?: null;
if (!$normalizedBookingDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking date']);
    exit;
}

$appointmentDate = $normalizedBookingDate->format('d-M-Y');
$packageInfoText = trim($packageInfo);
if ($packageInfoText === '') {
    $infoParts = [];
    if ($packageName !== '') {
        $infoParts[] = 'Package: ' . $packageName;
    }
    if ($packagePrice !== '') {
        $infoParts[] = 'Price: ' . $packagePrice;
    }
    $packageInfoText = implode(' | ', $infoParts);
}

$filename = 'bookings_' . date('Y-m-d') . '.csv';
$filepath = __DIR__ . '/../data/' . $filename;

if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

if (!file_exists($filepath)) {
    $header = "Booking ID,Name,Age,WhatsApp,Email,Booking Date,Preferred Slot,Collection Address,Package Name,Package Price,Package Info,Created At,Status\n";
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
    'age' => $normalizedAge,
    'phone' => $normalizedPhone,
    'email' => $email,
    'appointment_date' => $appointmentDate,
    'preferred_slot' => $preferredSlot,
    'collection_address' => $collectionAddress,
    'package_name' => $packageName,
    'package_price' => $packagePrice,
    'package_info' => $packageInfoText,
    'created_at' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

$csvLine = implode(',', [
    $bookingData['booking_id'],
    '"' . addslashes($bookingData['name']) . '"',
    $bookingData['age'],
    $bookingData['phone'],
    '"' . addslashes($bookingData['email']) . '"',
    '"' . addslashes($bookingData['appointment_date']) . '"',
    '"' . addslashes($bookingData['preferred_slot']) . '"',
    '"' . addslashes($bookingData['collection_address']) . '"',
    '"' . addslashes($bookingData['package_name']) . '"',
    '"' . addslashes($bookingData['package_price']) . '"',
    '"' . addslashes($bookingData['package_info']) . '"',
    $bookingData['created_at'],
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
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Age</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['age']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>WhatsApp Number</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['phone']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Email</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>" . (!empty($data['email']) ? $data['email'] : '—') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Booking Date</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['appointment_date']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Preferred Slot</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['preferred_slot']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Collection Address</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['collection_address']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Package Name</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['package_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Package Price</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>{$data['package_price']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;'>Package Info</td>
                        <td style='padding: 10px 12px; border: 1px solid #e5e7eb;'>" . nl2br(str_replace(' | ', "\n", $data['package_info'])) . "</td>
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
    $message = "Hello, I want to book a blood collection appointment.";
    $message .= "\nBooking Details";
    $message .= "\nBooking ID: {$data['booking_id']}";
    $message .= "\nPatient Name: {$data['name']}";
    $message .= "\nAge: {$data['age']}";
    $message .= "\nWhatsApp Number: {$data['phone']}";
    if (!empty($data['email'])) {
        $message .= "\nEmail: {$data['email']}";
    }
    $message .= "\nBooking Date: {$data['appointment_date']}";
    $message .= "\nPreferred Slot: {$data['preferred_slot']}";
    $message .= "\nCollection Address: {$data['collection_address']}";

    $message .= "\n\nPackage Details";
    if (!empty($data['package_info'])) {
        // Multi-package: split by pipe and format each entry
        $infoParts = array_map('trim', explode(' | ', $data['package_info']));
        foreach ($infoParts as $part) {
            if ($part !== '') {
                $message .= "\n• " . $part;
            }
        }
    } elseif (!empty($data['package_name'])) {
        $message .= "\nPackage: {$data['package_name']}";
        if (!empty($data['package_price'])) {
            $message .= " — {$data['package_price']}";
        }
    }

    $message .= "\n\nHome collection included.";
    return $message;
}
?>
