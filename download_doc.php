<?php
// download_doc.php

require_once __DIR__ . '/config/db.php';
check_auth();

$file_id = $_GET['file_id'] ?? '';

require_once __DIR__ . '/src/Controllers/DocumentController.php';
$docCtrl = new DocumentController();
$docCtrl->downloadDoc($pdo, $file_id);
exit;
