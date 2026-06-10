<?php
header('Content-Type: application/json; charset=UTF-8');

$baseDir = __DIR__ . '/../data';
$rows = [];
$sources = [];

if (!is_dir($baseDir)) {
    echo json_encode([
        'success' => true,
        'rows' => [],
        'sources' => []
    ]);
    exit;
}

$files = glob($baseDir . '/bookings_*.csv');
if (!$files) {
    echo json_encode([
        'success' => true,
        'rows' => [],
        'sources' => []
    ]);
    exit;
}

foreach ($files as $file) {
    $basename = basename($file);
    $handle = fopen($file, 'r');
    if (!$handle) {
        continue;
    }

    $header = fgetcsv($handle);
    if (!is_array($header)) {
        fclose($handle);
        continue;
    }

    $header = array_map('trim', $header);
    $sources[] = $basename;

    $rowIndex = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row) || count($row) === 0) {
            continue;
        }

        $rowAssoc = [];
        for ($i = 0; $i < count($header); $i++) {
            $key = $header[$i];
            $rowAssoc[$key] = $row[$i] ?? '';
        }

        $normalized = normalize_row($rowAssoc, $basename, $rowIndex);
        if ($normalized) {
            $rows[] = $normalized;
        }

        $rowIndex += 1;
    }

    fclose($handle);
}

usort($rows, function ($a, $b) {
    return strcmp($b['date_sort'], $a['date_sort']);
});

http_response_code(200);
echo json_encode([
    'success' => true,
    'rows' => $rows,
    'sources' => array_values(array_unique($sources))
]);

function normalize_row(array $row, string $source, int $rowIndex) {
    $bookingId = pick_first($row, ['Booking ID', 'Booking Id', 'BookingID', 'Id']);
    $name = pick_first($row, ['Name', 'Patient Name']);
    $phone = pick_first($row, ['Phone', 'WhatsApp', 'WhatsApp Number', 'Contact']);
    $serviceType = pick_first($row, ['Service Type']);
    $testType = pick_first($row, ['Test Type']);
    $service = pick_first($row, ['Service Type', 'Package Name', 'Package Info', 'Test Type', 'Service']);
    $slot = pick_first($row, ['Preferred Time', 'Preferred Slot']);
    $status = pick_first($row, ['Status']);

    $preferredDate = pick_first($row, ['Preferred Date']);
    $bookingDate = pick_first($row, ['Booking Date', 'Appointment Date']);
    $appointmentDate = pick_first($row, ['Booking Date']);

    $date = $preferredDate !== '' ? $preferredDate : ($appointmentDate !== '' ? $appointmentDate : $bookingDate);

    if ($date === '' && looks_like_date($serviceType)) {
        $date = $serviceType;
    }

    if ($status === '' && looks_like_status($testType)) {
        $status = $testType;
    }

    $createdAt = pick_first($row, ['Created At', 'Booking Date']);

    $dateSort = normalize_date_sort($date);

    if ($name === '' && $phone === '' && $date === '') {
        return null;
    }

    $email = pick_first($row, ['Email', 'email']);

    return [
        'id' => $bookingId,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'service' => $service,
        'slot' => $slot,
        'date' => $date,
        'date_sort' => $dateSort,
        'status' => $status !== '' ? $status : 'pending',
        'created_at' => $createdAt,
        'source_file' => $source,
        'row_index' => $rowIndex,
        'raw' => $row
    ];
}

function pick_first(array $row, array $keys) {
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
            return trim((string) $row[$key]);
        }
    }
    return '';
}

function normalize_date_sort(string $dateStr) {
    $dateStr = trim($dateStr);
    if ($dateStr === '') {
        return '';
    }

    $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd-M-Y', 'd-M-Y H:i', 'd-M-Y H:i:s'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $dateStr);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }

    $timestamp = strtotime($dateStr);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return '';
}

function looks_like_date(string $value) {
    if ($value === '') {
        return false;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return true;
    }
    if (preg_match('/^\d{1,2}-[A-Za-z]{3}-\d{4}/', $value)) {
        return true;
    }
    return strtotime($value) !== false;
}

function looks_like_status(string $value) {
    $clean = strtolower(trim($value));
    return in_array($clean, ['pending', 'confirmed', 'completed'], true);
}
?>
