<?php
// export_patients.php

require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/PatientController.php';
$patientCtrl = new PatientController();
$patientCtrl->export($pdo);
exit;
