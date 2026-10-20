<?php
// edit_patient.php

require_once __DIR__ . '/config/db.php';
check_auth();

$patient_id = $_GET['patient_id'] ?? '';

require_once __DIR__ . '/src/Controllers/PatientController.php';
$patientCtrl = new PatientController();
$patientCtrl->edit($pdo, $patient_id);
exit;
