<?php
// submit_patient.php

require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/PatientController.php';
$patientCtrl = new PatientController();
$patientCtrl->submit($pdo);
exit;
