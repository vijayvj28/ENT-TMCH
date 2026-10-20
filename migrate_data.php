<?php
// migrate_data.php
// Utility script to migrate medical_db.json into the MySQL database

require_once __DIR__ . '/config/db.php';

// Only allow execution via CLI or if logged in as admin in session
if (php_sapi_name() !== 'cli') {
    if (empty($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
        die("Unauthorized access. This script can only be run via CLI command-line or by logged-in admin.");
    }
}

// In php/ folder, the medical_db.json is in the parent directory
$json_file = __DIR__ . '/../medical_db.json';
if (!is_file($json_file)) {
    die("Error: medical_db.json file not found in " . dirname(__DIR__) . "\n");
}

$data = json_decode(file_get_contents($json_file), true);
if (!$data) {
    die("Error: Failed to parse medical_db.json. Ensure it contains valid JSON.\n");
}

echo "Starting data migration...\n";

// Map department names to database IDs
$dept_map = [];
$stmt_depts = $pdo->query("SELECT id, name FROM departments");
while ($row = $stmt_depts->fetch()) {
    $dept_map[$row['name']] = $row['id'];
}

// 1. Migrate Patients
if (!empty($data['patients'])) {
    echo "Migrating patients (" . count($data['patients']) . " records)...\n";
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->query("TRUNCATE TABLE patients;");
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1;");
    
    $stmt_p = $pdo->prepare("
        INSERT INTO patients (
            id, name, age_sex, ip_number, abha_number, doa, transfer_in_date, dod, place, mobile_number, 
            diagnosis, main_diagnosis, patient_status, cause_of_death, 
            treatment, investigations, xray_notes, ct_notes, spirometry_notes, echo_notes,
            xray_image, ct_image, spirometry_image, echo_image, created_by
        ) VALUES (
            :id, :name, :age_sex, :ip_number, :abha_number, :doa, :transfer_in_date, :dod, :place, :mobile_number, 
            :diagnosis, :main_diagnosis, :patient_status, :cause_of_death, 
            :treatment, :investigations, :xray_notes, :ct_notes, :spirometry_notes, :echo_notes,
            :xray_image, :ct_image, :spirometry_image, :echo_image, :created_by
        )
    ");
    
    $migrated_patients = 0;
    foreach ($data['patients'] as $p) {
        $doa = !empty($p['doa']) ? $p['doa'] : null;
        $transfer_in_date = !empty($p['transfer_in_date']) ? $p['transfer_in_date'] : null;
        $dod = !empty($p['dod']) ? $p['dod'] : null;
        $cause_of_death = !empty($p['cause_of_death']) && $p['cause_of_death'] !== 'N/A' ? $p['cause_of_death'] : null;
        
        $stmt_p->execute([
            ':id' => $p['id'],
            ':name' => $p['name'],
            ':age_sex' => $p['age_sex'],
            ':ip_number' => $p['ip_number'],
            ':abha_number' => $p['abha_number'] ?? null,
            ':doa' => $doa,
            ':transfer_in_date' => $transfer_in_date,
            ':dod' => $dod,
            ':place' => $p['place'],
            ':mobile_number' => $p['mobile_number'],
            ':diagnosis' => $p['diagnosis'],
            ':main_diagnosis' => $p['main_diagnosis'],
            ':patient_status' => $p['patient_status'] ? $p['patient_status'] : 'Admission',
            ':cause_of_death' => $cause_of_death,
            ':treatment' => $p['treatment'],
            ':investigations' => $p['investigations'],
            ':xray_notes' => $p['xray_notes'] ? $p['xray_notes'] : null,
            ':ct_notes' => $p['ct_notes'] ? $p['ct_notes'] : null,
            ':spirometry_notes' => $p['spirometry_notes'] ?? null,
            ':echo_notes' => $p['echo_notes'] ?? null,
            ':xray_image' => $p['xray_image'] ? $p['xray_image'] : null,
            ':ct_image' => $p['ct_image'] ? $p['ct_image'] : null,
            ':spirometry_image' => $p['spirometry_image'] ?? null,
            ':echo_image' => $p['echo_image'] ?? null,
            ':created_by' => 1 // Link to default admin user
        ]);
        $migrated_patients++;
    }
    echo "Successfully migrated $migrated_patients patient records!\n";
}

// 2. Migrate Folders
if (!empty($data['folders'])) {
    echo "Migrating folders (" . count($data['folders']) . " records)...\n";
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->query("TRUNCATE TABLE folders;");
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1;");

    $stmt_f = $pdo->prepare("
        INSERT INTO folders (id, name, parent_id, department_id, created_by)
        VALUES (:id, :name, :parent_id, :dept_id, :created_by)
    ");

    $migrated_folders = 0;
    foreach ($data['folders'] as $f) {
        $dept_id = $dept_map[$f['department']] ?? null;
        $parent_id = !empty($f['parent_id']) && $f['parent_id'] !== 'None' ? $f['parent_id'] : null;

        $stmt_f->execute([
            ':id' => $f['id'],
            ':name' => $f['name'],
            ':parent_id' => $parent_id,
            ':dept_id' => $dept_id,
            ':created_by' => 1
        ]);
        $migrated_folders++;
    }
    echo "Successfully migrated $migrated_folders folders!\n";
}

// 3. Migrate Files
if (!empty($data['files'])) {
    echo "Migrating files (" . count($data['files']) . " records)...\n";
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->query("TRUNCATE TABLE files;");
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1;");

    $stmt_fi = $pdo->prepare("
        INSERT INTO files (id, name, filename, parent_id, department_id, description, extension, size, uploaded_by)
        VALUES (:id, :name, :filename, :parent_id, :dept_id, :description, :extension, :size, :uploaded_by)
    ");

    $migrated_files = 0;
    foreach ($data['files'] as $fi) {
        $dept_id = $dept_map[$fi['department']] ?? null;
        $parent_id = !empty($fi['parent_id']) && $fi['parent_id'] !== 'None' ? $fi['parent_id'] : null;

        $stmt_fi->execute([
            ':id' => $fi['id'],
            ':name' => $fi['name'],
            ':filename' => $fi['filename'],
            ':parent_id' => $parent_id,
            ':dept_id' => $dept_id,
            ':description' => $fi['description'] ? $fi['description'] : null,
            ':extension' => $fi['extension'],
            ':size' => intval($fi['size']),
            ':uploaded_by' => 1
        ]);
        $migrated_files++;
    }
    echo "Successfully migrated $migrated_files files!\n";
}

// 4. Migrate Census
if (!empty($data['census'])) {
    echo "Migrating census (" . count($data['census']) . " records)...\n";
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->query("TRUNCATE TABLE census;");
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1;");

    $stmt_c = $pdo->prepare("
        INSERT INTO census (type, activities, cmchis, stats_data, submitted_by)
        VALUES (:type, :activities, :cmchis, :stats_data, :submitted_by)
    ");

    $migrated_census = 0;
    foreach ($data['census'] as $c) {
        $type = $c['type'];
        $activities = $c['activities'] ?? null;
        $cmchis = $c['cmchis'] ?? null;
        
        $stats_data = null;
        if ($type === 'ipop_stats' && isset($c['stats'])) {
            $stats_data = json_encode($c['stats']);
        }

        $stmt_c->execute([
            ':type' => $type,
            ':activities' => $activities,
            ':cmchis' => $cmchis,
            ':stats_data' => $stats_data,
            ':submitted_by' => 1
        ]);
        $migrated_census++;
    }
    echo "Successfully migrated $migrated_census census logs!\n";
}

echo "Data migration successfully completed!\n";
