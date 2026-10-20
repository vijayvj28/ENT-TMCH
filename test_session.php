<?php
// test_session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin-ent';
$_SESSION['role'] = 'admin';
$_SESSION['department_id'] = 1;
$_SESSION['department_name'] = 'ENT';

$page = $_GET['page'] ?? 'index';
if ($page === 'user_mgmt') {
    require_once __DIR__ . '/user_mgmt.php';
} else {
    require_once __DIR__ . '/index.php';
}
