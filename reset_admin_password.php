<?php
// reset_admin_password.php

require_once __DIR__ . '/config/db.php';
require_admin();

require_once __DIR__ . '/src/Controllers/UserController.php';
$userCtrl = new UserController();
$userCtrl->resetAdminCredentials($pdo);
exit;
