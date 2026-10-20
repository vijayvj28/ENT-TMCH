<?php
// backup_restore_handler.php
require_once __DIR__ . '/config/db.php';
check_auth();

$action = $_REQUEST['action'] ?? '';
$backupDir = __DIR__ . '/backups';

if (!file_exists($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

/**
 * Generate full SQL dump string for all database tables
 */
function generate_sql_dump($pdo) {
    $output = "-- ========================================================\n";
    $output .= "-- Medical Portal Database Backup\n";
    $output .= "-- Exported on: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database Engine: MySQL / InnoDB\n";
    $output .= "-- ========================================================\n\n";

    $output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";

    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $output .= "-- --------------------------------------------------------\n";
        $output .= "-- Structure for table `$table`\n";
        $output .= "-- --------------------------------------------------------\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";

        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $createTableSql = $createStmt[1] ?? '';
        $output .= $createTableSql . ";\n\n";

        $output .= "-- Data for table `$table`\n";
        $dataStmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $cols = array_keys($rows[0]);
            $colList = '`' . implode('`, `', $cols) . '`';

            foreach (array_chunk($rows, 100) as $chunk) {
                $valSqls = [];
                foreach ($chunk as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote($val);
                        }
                    }
                    $valSqls[] = '(' . implode(', ', $vals) . ')';
                }
                $output .= "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $valSqls) . ";\n";
            }
        }
        $output .= "\n";
    }

    $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $output .= "-- Backup complete\n";

    return $output;
}

/**
 * Generate patient JSON export string
 */
function generate_json_export($pdo) {
    $patients = $pdo->query("SELECT * FROM patients ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
    $followups = $pdo->query("SELECT * FROM patient_followups ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

    $payload = [
        "system" => "Medical Department Portal",
        "version" => "1.0",
        "exported_at" => date('Y-m-d H:i:s'),
        "patient_count" => count($patients),
        "followup_count" => count($followups),
        "patients" => $patients,
        "patient_followups" => $followups
    ];

    return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Helper to format bytes into readable string
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Automatic Weekly Backup Check & Execution
 */
function check_and_run_weekly_backup($pdo, $force = false, $backupDir = null) {
    if (!$backupDir) {
        $backupDir = __DIR__ . '/backups';
    }
    if (!file_exists($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }

    $sqlFiles = glob($backupDir . '/medical_db_weekly_*.sql');
    $latestMtime = 0;

    if (!empty($sqlFiles)) {
        foreach ($sqlFiles as $f) {
            $mtime = filemtime($f);
            if ($mtime > $latestMtime) {
                $latestMtime = $mtime;
            }
        }
    }

    $now = time();
    $sevenDaysInSeconds = 7 * 86400;
    $isDue = ($now - $latestMtime) >= $sevenDaysInSeconds;

    if ($force || $isDue) {
        $dateStr = date('Y-m-d_H-i-s');
        $sqlFileName = "medical_db_weekly_{$dateStr}.sql";
        $jsonFileName = "patient_data_weekly_{$dateStr}.json";

        $sqlContent = generate_sql_dump($pdo);
        $jsonContent = generate_json_export($pdo);

        file_put_contents($backupDir . '/' . $sqlFileName, $sqlContent);
        file_put_contents($backupDir . '/' . $jsonFileName, $jsonContent);

        return [
            "created" => true,
            "sql_file" => $sqlFileName,
            "json_file" => $jsonFileName,
            "message" => "Weekly backup generated successfully in backups/ folder."
        ];
    }

    return [
        "created" => false,
        "message" => "Weekly backup is up to date (last backup was taken less than 7 days ago)."
    ];
}

// ------------------------------------------------------------------------
// ACTION HANDLERS
// ------------------------------------------------------------------------

// GET STATS & LIST OF SAVED BACKUPS
if ($action === 'get_stats') {
    header('Content-Type: application/json');
    try {
        // Run auto weekly backup check silently when modal stats are requested
        check_and_run_weekly_backup($pdo, false, $backupDir);

        $pCount = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $fCount = $pdo->query("SELECT COUNT(*) FROM patient_followups")->fetchColumn();
        $cCount = $pdo->query("SELECT COUNT(*) FROM census")->fetchColumn();
        $dCount = $pdo->query("SELECT COUNT(*) FROM files")->fetchColumn();
        $uCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Scan backups folder
        $files = [];
        if (file_exists($backupDir)) {
            $dirFiles = scandir($backupDir);
            foreach ($dirFiles as $f) {
                if ($f === '.' || $f === '..') continue;
                $filePath = $backupDir . '/' . $f;
                if (is_file($filePath)) {
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (in_array($ext, ['sql', 'json'])) {
                        $files[] = [
                            "filename" => $f,
                            "size_bytes" => filesize($filePath),
                            "size_formatted" => formatBytes(filesize($filePath)),
                            "mtime" => filemtime($filePath),
                            "date_formatted" => date('d M Y, h:i A', filemtime($filePath)),
                            "type" => $ext,
                            "is_weekly" => (strpos($f, '_weekly_') !== false)
                        ];
                    }
                }
            }
            // Sort newest first
            usort($files, function($a, $b) {
                return $b['mtime'] - $a['mtime'];
            });
        }

        echo json_encode([
            "status" => "success",
            "counts" => [
                "patients" => (int)$pCount,
                "followups" => (int)$fCount,
                "census" => (int)$cCount,
                "files" => (int)$dCount,
                "users" => (int)$uCount
            ],
            "backup_folder" => "backups/",
            "backups" => $files
        ]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// FORCE RUN WEEKLY BACKUP NOW
if ($action === 'run_weekly_backup') {
    header('Content-Type: application/json');
    try {
        $res = check_and_run_weekly_backup($pdo, true, $backupDir);
        echo json_encode(array_merge(["status" => "success"], $res));
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// DOWNLOAD SAVED BACKUP FILE FROM FOLDER
if ($action === 'download_file') {
    $filename = basename($_GET['filename'] ?? '');
    $filePath = $backupDir . '/' . $filename;

    if (empty($filename) || !file_exists($filePath)) {
        die("Backup file not found.");
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, ['sql', 'json'])) {
        die("Invalid file type.");
    }

    if (ob_get_level()) ob_end_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// DIRECT BROWSER STREAM: FULL DATABASE SQL DUMP
if ($action === 'export_sql') {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "medical_db_backup_{$timestamp}.sql";
        $sqlContent = generate_sql_dump($pdo);

        if (ob_get_level()) ob_end_clean();

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($sqlContent));
        echo $sqlContent;
        exit;
    } catch (Throwable $e) {
        die("SQL Backup failed: " . $e->getMessage());
    }
}

// DIRECT BROWSER STREAM: PATIENT DATA JSON
if ($action === 'export_json') {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "patient_data_backup_{$timestamp}.json";
        $jsonContent = generate_json_export($pdo);

        if (ob_get_level()) ob_end_clean();

        header('Content-Description: File Transfer');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($jsonContent));
        echo $jsonContent;
        exit;
    } catch (Throwable $e) {
        die("JSON Export failed: " . $e->getMessage());
    }
}

// RESTORE DATABASE / PATIENT DATA
if ($action === 'restore') {
    header('Content-Type: application/json');

    $fileTmp = null;
    $fileName = null;

    // Check if restore is triggered via uploaded file or selecting a saved backup from backups/ folder
    if (!empty($_POST['saved_filename'])) {
        $savedName = basename($_POST['saved_filename']);
        $targetSavedPath = $backupDir . '/' . $savedName;
        if (file_exists($targetSavedPath)) {
            $fileTmp = $targetSavedPath;
            $fileName = $savedName;
        }
    }

    if (!$fileTmp && !empty($_FILES['restore_file']['name']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['restore_file']['tmp_name'];
        $fileName = $_FILES['restore_file']['name'];
    }

    if (!$fileTmp) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No file uploaded or selected for restore."]);
        exit;
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['sql', 'json'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid file format. Only SQL (.sql) and JSON (.json) files are allowed."]);
        exit;
    }

    $fileContent = file_get_contents($fileTmp);
    if (empty($fileContent)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "The selected backup file is empty."]);
        exit;
    }

    try {
        if ($ext === 'sql') {
            // Restore SQL script reliably
            $fileContent = str_replace("\r\n", "\n", $fileContent);

            $pdo->beginTransaction();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // Split queries by semicolon followed by newline
            $queries = preg_split('/;\s*\n/', $fileContent);
            $executedCount = 0;

            foreach ($queries as $q) {
                $q = trim($q);
                if (empty($q) || strpos($q, '--') === 0 || strpos($q, '/*') === 0) {
                    continue;
                }
                $pdo->exec($q);
                $executedCount++;
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $pdo->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Database successfully restored from $fileName! Executed $executedCount SQL blocks."
            ]);
            exit;

        } elseif ($ext === 'json') {
            // Restore JSON backup
            $jsonData = json_decode($fileContent, true);
            if (!is_array($jsonData) || (!isset($jsonData['patients']) && !isset($jsonData[0]['ip_number']))) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid patient JSON backup format."]);
                exit;
            }

            $patients = $jsonData['patients'] ?? $jsonData;
            $followups = $jsonData['patient_followups'] ?? [];

            $pdo->beginTransaction();

            $pStmt = $pdo->prepare("
                INSERT INTO patients (
                    id, name, age_sex, ip_number, abha_number, doa, dod, place, mobile_number,
                    diagnosis, procedures, special_management, main_diagnosis, patient_status,
                    cause_of_death, treatment, investigations, xray_notes, ct_notes, spirometry_notes, echo_notes, created_by
                ) VALUES (
                    :id, :name, :age_sex, :ip_number, :abha_number, :doa, :dod, :place, :mobile_number,
                    :diagnosis, :procedures, :special_management, :main_diagnosis, :patient_status,
                    :cause_of_death, :treatment, :investigations, :xray_notes, :ct_notes, :spirometry_notes, :echo_notes, :created_by
                ) ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    age_sex = VALUES(age_sex),
                    abha_number = VALUES(abha_number),
                    doa = VALUES(doa),
                    dod = VALUES(dod),
                    place = VALUES(place),
                    mobile_number = VALUES(mobile_number),
                    diagnosis = VALUES(diagnosis),
                    procedures = VALUES(procedures),
                    special_management = VALUES(special_management),
                    main_diagnosis = VALUES(main_diagnosis),
                    patient_status = VALUES(patient_status),
                    cause_of_death = VALUES(cause_of_death),
                    treatment = VALUES(treatment),
                    investigations = VALUES(investigations),
                    xray_notes = VALUES(xray_notes),
                    ct_notes = VALUES(ct_notes),
                    spirometry_notes = VALUES(spirometry_notes),
                    echo_notes = VALUES(echo_notes)
            ");

            $restoredP = 0;
            foreach ($patients as $p) {
                if (empty($p['id']) || empty($p['ip_number'])) continue;
                $pStmt->execute([
                    ':id' => $p['id'],
                    ':name' => $p['name'] ?? '',
                    ':age_sex' => $p['age_sex'] ?? '',
                    ':ip_number' => $p['ip_number'],
                    ':abha_number' => $p['abha_number'] ?? null,
                    ':doa' => $p['doa'] ?? null,
                    ':dod' => $p['dod'] ?? null,
                    ':place' => $p['place'] ?? '',
                    ':mobile_number' => $p['mobile_number'] ?? '',
                    ':diagnosis' => $p['diagnosis'] ?? '',
                    ':procedures' => $p['procedures'] ?? null,
                    ':special_management' => $p['special_management'] ?? null,
                    ':main_diagnosis' => $p['main_diagnosis'] ?? '',
                    ':patient_status' => $p['patient_status'] ?? 'Admission',
                    ':cause_of_death' => $p['cause_of_death'] ?? null,
                    ':treatment' => $p['treatment'] ?? '',
                    ':investigations' => $p['investigations'] ?? '',
                    ':xray_notes' => $p['xray_notes'] ?? null,
                    ':ct_notes' => $p['ct_notes'] ?? null,
                    ':spirometry_notes' => $p['spirometry_notes'] ?? null,
                    ':echo_notes' => $p['echo_notes'] ?? null,
                    ':created_by' => $p['created_by'] ?? null
                ]);
                $restoredP++;
            }

            // Restore followups if present
            $restoredF = 0;
            if (!empty($followups)) {
                $fStmt = $pdo->prepare("
                    INSERT INTO patient_followups (id, patient_id, followup_date, notes, investigations)
                    VALUES (:id, :patient_id, :followup_date, :notes, :investigations)
                    ON DUPLICATE KEY UPDATE
                        followup_date = VALUES(followup_date),
                        notes = VALUES(notes),
                        investigations = VALUES(investigations)
                ");
                foreach ($followups as $f) {
                    if (empty($f['id']) || empty($f['patient_id'])) continue;
                    $fStmt->execute([
                        ':id' => $f['id'],
                        ':patient_id' => $f['patient_id'],
                        ':followup_date' => $f['followup_date'],
                        ':notes' => $f['notes'] ?? null,
                        ':investigations' => $f['investigations'] ?? null
                    ]);
                    $restoredF++;
                }
            }

            $pdo->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Patient JSON restore completed: $restoredP patients and $restoredF follow-up records updated."
            ]);
            exit;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Restore failed: " . $e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid action specified."]);
exit;
