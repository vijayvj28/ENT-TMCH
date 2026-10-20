<?php
// get_census_data.php
require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/CensusController.php';

$year = $_GET['year'] ?? null;
if (!$year) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing year parameter."]);
    exit;
}

$censusCtrl = new CensusController();
$data = $censusCtrl->getCensusDataByYear($pdo, $year);

header('Content-Type: application/json');
echo json_encode(["status" => "success", "data" => $data]);
exit;
