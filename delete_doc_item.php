<?php
// delete_doc_item.php

require_once __DIR__ . '/config/db.php';
require_admin();

require_once __DIR__ . '/src/Controllers/DocumentController.php';
$docCtrl = new DocumentController();
$docCtrl->deleteDocItem($pdo);
exit;
