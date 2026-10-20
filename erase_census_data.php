<?php
// erase_census_data.php
require_once __DIR__ . '/config/db.php';
check_auth();

header('Content-Type: application/json');

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Admin privilege required."]);
    exit;
}

require_once __DIR__ . '/src/Controllers/CensusController.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['module']) || empty($data['year'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request parameters."]);
    exit;
}

$module = $data['module'];
if (str_ends_with($module, '_census') || str_ends_with($module, '_stats')) {
    $type = $module;
} else {
    $type = $module . '_stats';
}
$year = (int)$data['year'];

$censusCtrl = new CensusController();
try {
    $censusCtrl->eraseCensusDataByYear($pdo, $type, $year);
    echo json_encode(["status" => "success", "message" => "Census data for " . strtoupper($module) . " ($year) erased successfully."]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error erasing data: " . $e->getMessage()]);
    exit;
}
