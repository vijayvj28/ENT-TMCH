<?php
// user_mgmt.php
// Administrator User Management Entrypoint

require_once __DIR__ . '/config/db.php';

// Check admin privilege
require_admin();

require_once __DIR__ . '/src/Controllers/UserController.php';
$userCtrl = new UserController();
$usersList = $userCtrl->listUsers($pdo);
$deptsList = $userCtrl->listDepartments($pdo);

$view = 'user_mgmt';
require_once __DIR__ . '/templates/base.php';
exit;
