<?php
// import_patients_handler.php
require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

header('Content-Type: application/json');

function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function parseFormattedDate($val) {
    if (empty($val)) return null;
    $val = trim((string)$val);
    if (empty($val) || $val === '-') return null;

    // Check numeric Excel timestamp
    if (is_numeric($val) && (float)$val > 10000 && (float)$val < 100000) {
        try {
            $dt = ExcelDate::excelToDateTimeObject((float)$val);
            return $dt->format('Y-m-d');
        } catch (Throwable $e) {}
    }

    // Try standard formats
    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $val);
        if ($d && $d->format($fmt) === $val) {
            return $d->format('Y-m-d');
        }
    }

    // String parse fallback
    $timestamp = strtotime($val);
    if ($timestamp !== false && $timestamp > 0) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}

$action = $_POST['action'] ?? 'preview';

if (empty($_FILES['import_file']['name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No file uploaded or file upload error."]);
    exit;
}

$fileTmp = $_FILES['import_file']['tmp_name'];
$fileName = $_FILES['import_file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid file format. Only Excel (.xlsx, .xls) and CSV (.csv) files are allowed."]);
    exit;
}

try {
    $spreadsheet = IOFactory::load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    if (empty($rows) || count($rows) < 2) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "The uploaded file is empty or missing data rows."]);
        exit;
    }

    // Map header columns
    $headerRow = array_shift($rows);
    $headerMap = [];

    foreach ($headerRow as $colKey => $cellVal) {
        $headerName = strtolower(trim((string)$cellVal));
        if (empty($headerName)) continue;

        if (strpos($headerName, 'name') !== false && strpos($headerName, 'patient') !== false) {
            $headerMap['name'] = $colKey;
        } elseif ($headerName === 'name' || $headerName === 'patient name') {
            $headerMap['name'] = $colKey;
        } elseif (strpos($headerName, 'age') !== false || strpos($headerName, 'sex') !== false) {
            $headerMap['age_sex'] = $colKey;
        } elseif (strpos($headerName, 'ip') !== false) {
            $headerMap['ip_number'] = $colKey;
        } elseif (strpos($headerName, 'abha') !== false) {
            $headerMap['abha_number'] = $colKey;
        } elseif (strpos($headerName, 'admission') !== false || $headerName === 'doa') {
            $headerMap['doa'] = $colKey;
        } elseif (strpos($headerName, 'transfer') !== false) {
            $headerMap['transfer_in_date'] = $colKey;
        } elseif (strpos($headerName, 'discharge date') !== false || strpos($headerName, 'dod') !== false || $headerName === 'date of discharge (dod)') {
            $headerMap['dod'] = $colKey;
        } elseif (strpos($headerName, 'place') !== false || strpos($headerName, 'address') !== false) {
            $headerMap['place'] = $colKey;
        } elseif (strpos($headerName, 'mobile') !== false || strpos($headerName, 'phone') !== false) {
            $headerMap['mobile_number'] = $colKey;
        } elseif (strpos($headerName, 'main diagnosis') !== false) {
            $headerMap['main_diagnosis'] = $colKey;
        } elseif ($headerName === 'diagnosis') {
            $headerMap['diagnosis'] = $colKey;
        } elseif (strpos($headerName, 'procedure') !== false) {
            $headerMap['procedures'] = $colKey;
        } elseif (strpos($headerName, 'special') !== false || strpos($headerName, 'management') !== false) {
            $headerMap['special_management'] = $colKey;
        } elseif (strpos($headerName, 'status') !== false) {
            $headerMap['patient_status'] = $colKey;
        } elseif (strpos($headerName, 'death') !== false) {
            $headerMap['cause_of_death'] = $colKey;
        } elseif (strpos($headerName, 'treatment') !== false) {
            $headerMap['treatment'] = $colKey;
        } elseif (strpos($headerName, 'investigation') !== false) {
            $headerMap['investigations'] = $colKey;
        } elseif (strpos($headerName, 'xray') !== false || strpos($headerName, 'x-ray') !== false) {
            $headerMap['xray_notes'] = $colKey;
        } elseif (strpos($headerName, 'ct') !== false) {
            $headerMap['ct_notes'] = $colKey;
        } elseif (strpos($headerName, 'spirometry') !== false) {
            $headerMap['spirometry_notes'] = $colKey;
        } elseif (strpos($headerName, 'echo') !== false) {
            $headerMap['echo_notes'] = $colKey;
        } elseif (strpos($headerName, 'id proof') !== false || strpos($headerName, 'cmchis proof') !== false) {
            $headerMap['cmchis_id_proof'] = $colKey;
        } elseif (strpos($headerName, 'proof remark') !== false || strpos($headerName, 'id remark') !== false) {
            $headerMap['cmchis_id_proof_remarks'] = $colKey;
        } elseif (strpos($headerName, 'scheme available') !== false || strpos($headerName, 'scheme avail') !== false) {
            $headerMap['cmchis_scheme_available'] = $colKey;
        } elseif (strpos($headerName, 'scheme remark') !== false || strpos($headerName, 'scheme detail') !== false) {
            $headerMap['cmchis_scheme_remarks'] = $colKey;
        }
    }

    // Positional fallback if header search failed for core fields
    $cols = array_keys($headerRow);
    if (!isset($headerMap['name']) && isset($cols[0])) $headerMap['name'] = $cols[0];
    if (!isset($headerMap['age_sex']) && isset($cols[1])) $headerMap['age_sex'] = $cols[1];
    if (!isset($headerMap['ip_number']) && isset($cols[2])) $headerMap['ip_number'] = $cols[2];
    if (!isset($headerMap['abha_number']) && isset($cols[3])) $headerMap['abha_number'] = $cols[3];
    if (!isset($headerMap['doa']) && isset($cols[4])) $headerMap['doa'] = $cols[4];

    // Fetch existing IP numbers from database to identify duplicates
    $stmt = $pdo->query("SELECT ip_number, id FROM patients");
    $existingIpMap = [];
    while ($r = $stmt->fetch()) {
        $existingIpMap[trim($r['ip_number'])] = $r['id'];
    }

    $validStatuses = ['Admission', 'Discharged', 'AMA', 'Death', 'Discharge/Referal'];

    $parsedPatients = [];
    $duplicateCount = 0;

    foreach ($rows as $rowIndex => $r) {
        $pName = trim((string)($r[$headerMap['name'] ?? 'A'] ?? ''));
        $ipNo  = trim((string)($r[$headerMap['ip_number'] ?? 'C'] ?? ''));

        // Skip completely empty rows
        if (empty($pName) && empty($ipNo)) {
            continue;
        }

        $ageSex = trim((string)($r[$headerMap['age_sex'] ?? 'B'] ?? ''));
        if (empty($ageSex)) $ageSex = 'N/A';

        $abhaNo = trim((string)($r[$headerMap['abha_number'] ?? 'D'] ?? ''));
        $doaRaw = $r[$headerMap['doa'] ?? 'E'] ?? null;
        $transferInRaw = $r[$headerMap['transfer_in_date'] ?? ''] ?? null;
        $dodRaw = $r[$headerMap['dod'] ?? 'F'] ?? null;
        $doa = parseFormattedDate($doaRaw);
        $transferInDate = parseFormattedDate($transferInRaw);
        $dod = parseFormattedDate($dodRaw);

        $place = trim((string)($r[$headerMap['place'] ?? 'G'] ?? ''));
        if (empty($place)) $place = 'General';

        $mobile = trim((string)($r[$headerMap['mobile_number'] ?? 'H'] ?? ''));
        if (empty($mobile)) $mobile = 'N/A';

        $mainDiag = trim((string)($r[$headerMap['main_diagnosis'] ?? 'I'] ?? ''));
        if (empty($mainDiag)) $mainDiag = 'General ENT Evaluation';

        $diag = trim((string)($r[$headerMap['diagnosis'] ?? 'J'] ?? ''));
        if (empty($diag)) $diag = 'Others';

        $procedures = trim((string)($r[$headerMap['procedures'] ?? 'K'] ?? ''));
        $specialMgmt = trim((string)($r[$headerMap['special_management'] ?? 'L'] ?? ''));

        $statusRaw = trim((string)($r[$headerMap['patient_status'] ?? 'M'] ?? ''));
        $status = 'Admission';
        foreach ($validStatuses as $vs) {
            if (strcasecmp($statusRaw, $vs) === 0) {
                $status = $vs;
                break;
            }
        }

        $causeOfDeath = trim((string)($r[$headerMap['cause_of_death'] ?? 'N'] ?? ''));
        $treatment = trim((string)($r[$headerMap['treatment'] ?? 'O'] ?? ''));
        if (empty($treatment)) $treatment = 'Standard Supportive Care';

        $investigations = trim((string)($r[$headerMap['investigations'] ?? 'P'] ?? ''));
        if (empty($investigations)) $investigations = 'Routine investigations done.';

        $xray = trim((string)($r[$headerMap['xray_notes'] ?? 'Q'] ?? ''));
        $ct = trim((string)($r[$headerMap['ct_notes'] ?? 'R'] ?? ''));
        $spiro = trim((string)($r[$headerMap['spirometry_notes'] ?? 'S'] ?? ''));
        $echo = trim((string)($r[$headerMap['echo_notes'] ?? 'T'] ?? ''));

        $cmProofRaw = trim((string)($r[$headerMap['cmchis_id_proof'] ?? ''] ?? ''));
        $cmProof = (strcasecmp($cmProofRaw, 'yes') === 0 || strcasecmp($cmProofRaw, 'y') === 0) ? 'Yes' : 'No';
        $cmProofRem = trim((string)($r[$headerMap['cmchis_id_proof_remarks'] ?? ''] ?? ''));

        $cmAvailRaw = trim((string)($r[$headerMap['cmchis_scheme_available'] ?? ''] ?? ''));
        $cmAvail = ($cmProof === 'Yes') ? ((strcasecmp($cmAvailRaw, 'yes') === 0 || strcasecmp($cmAvailRaw, 'y') === 0) ? 'Yes' : 'No') : null;
        $cmSchemeRem = trim((string)($r[$headerMap['cmchis_scheme_remarks'] ?? ''] ?? ''));

        $isDuplicate = isset($existingIpMap[$ipNo]);
        if ($isDuplicate) $duplicateCount++;

        $isValid = (!empty($pName) && !empty($ipNo));
        $errorMsg = '';
        if (empty($pName)) $errorMsg = 'Missing Patient Name';
        elseif (empty($ipNo)) $errorMsg = 'Missing IP Number';

        $parsedPatients[] = [
            'row_num' => $rowIndex + 2,
            'name' => $pName,
            'age_sex' => $ageSex,
            'ip_number' => $ipNo,
            'abha_number' => $abhaNo,
            'cmchis_id_proof' => $cmProof,
            'cmchis_id_proof_remarks' => $cmProofRem,
            'cmchis_scheme_available' => $cmAvail,
            'cmchis_scheme_remarks' => $cmSchemeRem,
            'doa' => $doa,
            'transfer_in_date' => $transferInDate,
            'dod' => $dod,
            'place' => $place,
            'mobile_number' => $mobile,
            'main_diagnosis' => $mainDiag,
            'diagnosis' => $diag,
            'procedures' => $procedures,
            'special_management' => $specialMgmt,
            'patient_status' => $status,
            'cause_of_death' => $causeOfDeath,
            'treatment' => $treatment,
            'investigations' => $investigations,
            'xray_notes' => $xray,
            'ct_notes' => $ct,
            'spirometry_notes' => $spiro,
            'echo_notes' => $echo,
            'is_duplicate' => $isDuplicate,
            'existing_id' => $existingIpMap[$ipNo] ?? null,
            'is_valid' => $isValid,
            'error_msg' => $errorMsg
        ];
    }

    if ($action === 'preview') {
        echo json_encode([
            "status" => "success",
            "filename" => $fileName,
            "total_rows" => count($parsedPatients),
            "duplicate_count" => $duplicateCount,
            "data" => $parsedPatients
        ]);
        exit;
    } elseif ($action === 'import') {
        $conflictResolution = $_POST['conflict_resolution'] ?? 'replace'; // replace, skip, update
        if (!in_array($conflictResolution, ['replace', 'skip', 'update'])) {
            $conflictResolution = 'replace';
        }

        $userId = $_SESSION['user_id'] ?? null;
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        // Auto schema migration check for transfer_in_date
        try {
            $pdo->exec("ALTER TABLE `patients` ADD COLUMN `transfer_in_date` DATE NULL AFTER `doa`");
        } catch (\PDOException $ex) {
            // Ignored if column already exists or DDL restricted
        }

        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare("
            INSERT INTO patients (
                id, name, age_sex, ip_number, abha_number, doa, transfer_in_date, dod, place, mobile_number,
                diagnosis, procedures, special_management, main_diagnosis, patient_status,
                cause_of_death, treatment, investigations, xray_notes, ct_notes, spirometry_notes, echo_notes,
                cmchis_id_proof, cmchis_id_proof_remarks, cmchis_scheme_available, cmchis_scheme_remarks, created_by
            ) VALUES (
                :id, :name, :age_sex, :ip_number, :abha_number, :doa, :transfer_in_date, :dod, :place, :mobile_number,
                :diagnosis, :procedures, :special_management, :main_diagnosis, :patient_status,
                :cause_of_death, :treatment, :investigations, :xray_notes, :ct_notes, :spirometry_notes, :echo_notes,
                :cmchis_id_proof, :cmchis_id_proof_remarks, :cmchis_scheme_available, :cmchis_scheme_remarks, :created_by
            )
        ");

        $updateStmt = $pdo->prepare("
            UPDATE patients SET
                name = :name,
                age_sex = :age_sex,
                abha_number = :abha_number,
                doa = :doa,
                transfer_in_date = :transfer_in_date,
                dod = :dod,
                place = :place,
                mobile_number = :mobile_number,
                diagnosis = :diagnosis,
                procedures = :procedures,
                special_management = :special_management,
                main_diagnosis = :main_diagnosis,
                patient_status = :patient_status,
                cause_of_death = :cause_of_death,
                treatment = :treatment,
                investigations = :investigations,
                xray_notes = :xray_notes,
                ct_notes = :ct_notes,
                spirometry_notes = :spirometry_notes,
                echo_notes = :echo_notes,
                cmchis_id_proof = :cmchis_id_proof,
                cmchis_id_proof_remarks = :cmchis_id_proof_remarks,
                cmchis_scheme_available = :cmchis_scheme_available,
                cmchis_scheme_remarks = :cmchis_scheme_remarks
            WHERE id = :id
        ");

        foreach ($parsedPatients as $pt) {
            if (!$pt['is_valid']) {
                $skippedCount++;
                continue;
            }

            if ($pt['is_duplicate']) {
                if ($conflictResolution === 'skip') {
                    $skippedCount++;
                    continue;
                }

                $existingId = $pt['existing_id'];
                if ($conflictResolution === 'replace' || $conflictResolution === 'update') {
                    $updateStmt->execute([
                        ':name' => $pt['name'],
                        ':age_sex' => $pt['age_sex'],
                        ':abha_number' => $pt['abha_number'],
                        ':doa' => $pt['doa'],
                        ':transfer_in_date' => $pt['transfer_in_date'],
                        ':dod' => $pt['dod'],
                        ':place' => $pt['place'],
                        ':mobile_number' => $pt['mobile_number'],
                        ':diagnosis' => $pt['diagnosis'],
                        ':procedures' => $pt['procedures'],
                        ':special_management' => $pt['special_management'],
                        ':main_diagnosis' => $pt['main_diagnosis'],
                        ':patient_status' => $pt['patient_status'],
                        ':cause_of_death' => $pt['cause_of_death'],
                        ':treatment' => $pt['treatment'],
                        ':investigations' => $pt['investigations'],
                        ':xray_notes' => $pt['xray_notes'],
                        ':ct_notes' => $pt['ct_notes'],
                        ':spirometry_notes' => $pt['spirometry_notes'],
                        ':echo_notes' => $pt['echo_notes'],
                        ':cmchis_id_proof' => $pt['cmchis_id_proof'],
                        ':cmchis_id_proof_remarks' => $pt['cmchis_id_proof_remarks'],
                        ':cmchis_scheme_available' => $pt['cmchis_scheme_available'],
                        ':cmchis_scheme_remarks' => $pt['cmchis_scheme_remarks'],
                        ':id' => $existingId
                    ]);
                    $updatedCount++;
                }
            } else {
                $newId = generateUuid();
                $insertStmt->execute([
                    ':id' => $newId,
                    ':name' => $pt['name'],
                    ':age_sex' => $pt['age_sex'],
                    ':ip_number' => $pt['ip_number'],
                    ':abha_number' => $pt['abha_number'],
                    ':doa' => $pt['doa'],
                    ':transfer_in_date' => $pt['transfer_in_date'],
                    ':dod' => $pt['dod'],
                    ':place' => $pt['place'],
                    ':mobile_number' => $pt['mobile_number'],
                    ':diagnosis' => $pt['diagnosis'],
                    ':procedures' => $pt['procedures'],
                    ':special_management' => $pt['special_management'],
                    ':main_diagnosis' => $pt['main_diagnosis'],
                    ':patient_status' => $pt['patient_status'],
                    ':cause_of_death' => $pt['cause_of_death'],
                    ':treatment' => $pt['treatment'],
                    ':investigations' => $pt['investigations'],
                    ':xray_notes' => $pt['xray_notes'],
                    ':ct_notes' => $pt['ct_notes'],
                    ':spirometry_notes' => $pt['spirometry_notes'],
                    ':echo_notes' => $pt['echo_notes'],
                    ':cmchis_id_proof' => $pt['cmchis_id_proof'],
                    ':cmchis_id_proof_remarks' => $pt['cmchis_id_proof_remarks'],
                    ':cmchis_scheme_available' => $pt['cmchis_scheme_available'],
                    ':cmchis_scheme_remarks' => $pt['cmchis_scheme_remarks'],
                    ':created_by' => $userId
                ]);
                $importedCount++;
            }
        }

        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "imported_count" => $importedCount,
            "updated_count" => $updatedCount,
            "skipped_count" => $skippedCount,
            "message" => "Import completed: $importedCount new patients added, $updatedCount updated, $skippedCount skipped."
        ]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid action specified."]);
        exit;
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Processing error: " . $e->getMessage()]);
    exit;
}
