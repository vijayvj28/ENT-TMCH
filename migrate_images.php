<?php
// migrate_images.php - Widens image columns to TEXT for multi-file JSON support
// Access this once via browser: http://localhost/fine/migrate_images.php
require_once __DIR__ . '/config/db.php';

$sqls = [
    "ALTER TABLE patients MODIFY COLUMN xray_image TEXT NULL",
    "ALTER TABLE patients MODIFY COLUMN ct_image TEXT NULL",
    "ALTER TABLE patients MODIFY COLUMN spirometry_image TEXT NULL",
    "ALTER TABLE patients MODIFY COLUMN echo_image TEXT NULL",
];

echo "<pre>";
foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ OK: " . $sql . "\n";
    } catch (Exception $e) {
        // Column already TEXT is fine
        echo "⚠️  SKIP: " . $e->getMessage() . "\n";
    }
}
echo "\n✅ Migration complete. You can delete this file now.\n";
echo "</pre>";
