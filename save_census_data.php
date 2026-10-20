<?php
// save_census_data.php
require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/CensusController.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['type']) || empty($data['year'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid payload."]);
    exit;
}

$type = $data['type']; // e.g. 'ipop_stats' or 'activities_stats' or 'cmchis_stats'
$year = (int)$data['year'];
$stats = $data['stats'] ?? null;
$main = $data['main'] ?? null;
$dc = $data['dc'] ?? null;

$payload = [];
if ($type === 'cmchis_stats') {
    $payload = [
        'type' => 'cmchis_stats',
        'main' => $main,
        'dc' => $dc
    ];
} else {
    $payload = [
        'type' => $type,
        'stats' => $stats
    ];
}

$censusCtrl = new CensusController();
try {
    $censusCtrl->saveCensusDataByYear($pdo, $type, $year, $payload);
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "message" => "Data saved successfully for year $year."]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Error saving data: " . $e->getMessage()]);
    exit;
}
