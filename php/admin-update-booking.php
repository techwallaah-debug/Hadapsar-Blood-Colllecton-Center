<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$sourceFile = isset($payload['source_file']) ? basename((string) $payload['source_file']) : '';
$rowIndex = isset($payload['row_index']) ? (int) $payload['row_index'] : -1;
$status = isset($payload['status']) ? strtolower(trim((string) $payload['status'])) : '';

$allowedStatuses = ['pending', 'confirmed', 'completed'];
if ($sourceFile === '' || !preg_match('/^bookings_\d{4}-\d{2}-\d{2}\.csv$/', $sourceFile)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid source file']);
    exit;
}

if ($rowIndex < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid row index']);
    exit;
}

if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$path = __DIR__ . '/../data/' . $sourceFile;
if (!file_exists($path)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Source file not found']);
    exit;
}

$rows = [];
$header = [];
if (($handle = fopen($path, 'r')) !== false) {
    $header = fgetcsv($handle);
    if (!is_array($header)) {
        fclose($handle);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid CSV header']);
        exit;
    }

    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
}

if ($rowIndex >= count($rows)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Row not found']);
    exit;
}

$statusIndex = array_search('Status', $header, true);
if ($statusIndex === false) {
    $header[] = 'Status';
    $statusIndex = count($header) - 1;
}

$target = $rows[$rowIndex];
$target = array_values($target);
while (count($target) < count($header)) {
    $target[] = '';
}
$target[$statusIndex] = $status;
$rows[$rowIndex] = $target;

if (($handle = fopen($path, 'w')) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to write file']);
    exit;
}

fputcsv($handle, $header);
foreach ($rows as $row) {
    if (count($row) < count($header)) {
        $row = array_pad($row, count($header), '');
    }
    fputcsv($handle, $row);
}

fclose($handle);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Status updated',
    'status' => $status
]);
?>
