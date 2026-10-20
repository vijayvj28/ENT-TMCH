<?php
// delete_followup.php
require_once __DIR__ . '/config/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$followup_id = $_GET['followup_id'] ?? '';

if (empty($followup_id)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error: Follow-up ID is required.'];
    header("Location: index.php?section=view-patients");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM patient_followups WHERE id = :id");
    $stmt->execute([':id' => $followup_id]);
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Follow-up record deleted successfully!'];
} catch (PDOException $e) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error deleting follow-up record: ' . $e->getMessage()];
}

header("Location: index.php?section=view-patients");
exit;
