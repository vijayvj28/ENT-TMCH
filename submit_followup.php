<?php
// submit_followup.php
require_once __DIR__ . '/config/db.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$patient_id = $_POST['patient_id'] ?? '';
$followup_date = $_POST['followup_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$investigations = trim($_POST['investigations'] ?? '');

if (empty($patient_id) || empty($followup_date)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error: Patient ID and Follow-up Date are required.'];
    header("Location: index.php?section=view-patients");
    exit;
}

// Generate UUID v4 helper
function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    $id = generateUuid();
    
    $stmt = $pdo->prepare("
        INSERT INTO patient_followups (id, patient_id, followup_date, notes, investigations)
        VALUES (:id, :patient_id, :followup_date, :notes, :investigations)
    ");
    
    $stmt->execute([
        ':id' => $id,
        ':patient_id' => $patient_id,
        ':followup_date' => $followup_date,
        ':notes' => $notes,
        ':investigations' => $investigations
    ]);
    
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Follow-up record added successfully!'];
} catch (PDOException $e) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error adding follow-up record: ' . $e->getMessage()];
}

header("Location: index.php?section=view-patients");
exit;
