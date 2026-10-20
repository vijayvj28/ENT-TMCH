<?php
// logout.php

require_once __DIR__ . '/config/db.php';

session_unset();
session_destroy();

// Redirect back to login
$base_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
header("Location: " . $base_dir . "/login.php");
exit;
