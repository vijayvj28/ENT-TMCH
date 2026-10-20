<?php
// submit_census.php

require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/CensusController.php';
$censusCtrl = new CensusController();
$censusCtrl->submit($pdo);
exit;
