<?php
// delete_patient.php

require_once __DIR__ . '/config/db.php';
require_admin();

$patient_id = $_GET['patient_id'] ?? '';

require_once __DIR__ . '/src/Controllers/PatientController.php';
$patientCtrl = new PatientController();
$patientCtrl->delete($pdo, $patient_id);
exit;
