<?php
// upload_doc.php

require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/DocumentController.php';
$docCtrl = new DocumentController();
$docCtrl->uploadDoc($pdo);
exit;
