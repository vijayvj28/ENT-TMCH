<?php
// import_handler.php
require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Controllers/CensusController.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'preview';
$module = $_POST['module'] ?? 'auto';
$year = $_POST['year'] ?? '';

if (empty($_FILES['import_file']['name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No file uploaded or file upload failed."]);
    exit;
}

$fileTmp = $_FILES['import_file']['tmp_name'];
$fileName = $_FILES['import_file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid file format. Only Excel (.xlsx, .xls) and CSV (.csv) are allowed."]);
    exit;
}

$censusCtrl = new CensusController();

try {
    if ($action === 'preview') {
        $result = $censusCtrl->previewImport($fileTmp, $module, $year, $pdo, $fileName);
        echo json_encode([
            "status" => "success",
            "detected_module" => $result['module'],
            "detected_year" => $result['year'],
            "duplicate_detected" => $result['duplicate_detected'],
            "duplicate_months" => $result['duplicate_months'],
            "data" => $result['data']
        ]);
        exit;
    } elseif ($action === 'import') {
        $conflictResolution = $_POST['conflict_resolution'] ?? 'replace'; // replace, update, skip
        if (!in_array($conflictResolution, ['replace', 'update', 'skip'])) {
            $conflictResolution = 'replace';
        }
        
        // Reparse file to get data
        $result = $censusCtrl->previewImport($fileTmp, $module, $year, $pdo, $fileName);
        $finalModule = $result['module'];
        $finalYear = $result['year'];
        
        $censusCtrl->executeImport($result['data'], $finalModule, $finalYear, $conflictResolution, $pdo);
        
        echo json_encode([
            "status" => "success",
            "module" => $finalModule,
            "year" => $finalYear,
            "message" => "Data imported successfully for year $finalYear."
        ]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid action."]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Processing error: " . $e->getMessage()]);
    exit;
}

