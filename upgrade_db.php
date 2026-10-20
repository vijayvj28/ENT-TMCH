<?php
// upgrade_db.php
// Script to upgrade the database schema for Spirometry and ECHO options.

require_once __DIR__ . '/config/db.php';

if (php_sapi_name() !== 'cli') {
    if (empty($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
        die("Unauthorized access. Run via CLI or log in as admin.");
    }
}

try {
    $queries = [
        "ALTER TABLE `patients` ADD COLUMN `spirometry_notes` TEXT NULL AFTER `ct_notes`",
        "ALTER TABLE `patients` ADD COLUMN `echo_notes` TEXT NULL AFTER `spirometry_notes`",
        "ALTER TABLE `patients` ADD COLUMN `spirometry_image` VARCHAR(255) NULL AFTER `ct_image`",
        "ALTER TABLE `patients` ADD COLUMN `echo_image` VARCHAR(255) NULL AFTER `spirometry_image`",
        "ALTER TABLE `patients` ADD COLUMN `investigations_image` VARCHAR(255) NULL AFTER `echo_image`",
        "ALTER TABLE `patients` ADD COLUMN `cmchis_id_proof` ENUM('Yes', 'No') NOT NULL DEFAULT 'No' AFTER `investigations_image`",
        "ALTER TABLE `patients` ADD COLUMN `cmchis_id_proof_remarks` TEXT NULL AFTER `cmchis_id_proof`",
        "ALTER TABLE `patients` ADD COLUMN `cmchis_scheme_available` ENUM('Yes', 'No') NULL AFTER `cmchis_id_proof_remarks`",
        "ALTER TABLE `patients` ADD COLUMN `cmchis_scheme_remarks` TEXT NULL AFTER `cmchis_scheme_available`",
        "ALTER TABLE `patients` ADD COLUMN `abha_number` VARCHAR(50) NULL AFTER `ip_number`",
        "ALTER TABLE `patients` ADD COLUMN `transfer_in_date` DATE NULL AFTER `doa`",
        "ALTER TABLE `census` ADD COLUMN `year` INT NULL AFTER `type`",
        "CREATE TABLE IF NOT EXISTS `patient_followups` (
            `id` VARCHAR(36) PRIMARY KEY,
            `patient_id` VARCHAR(36) NOT NULL,
            `followup_date` DATE NOT NULL,
            `notes` TEXT NULL,
            `investigations` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
            INDEX idx_followup_patient (`patient_id`),
            INDEX idx_followup_date (`followup_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "Successfully executed: $query<br>\n";
        } catch (PDOException $e) {
            // Check if column already exists (SQLSTATE 42S21, error code 1060)
            if ($e->getCode() == '42S21' || strpos($e->getMessage(), "Duplicate column name") !== false) {
                echo "Column already exists, skipping: $query<br>\n";
            } else {
                throw $e;
            }
        }
    }
    echo "Database upgrade completed successfully!<br>\n";
} catch (Exception $e) {
    echo "Database upgrade failed: " . $e->getMessage() . "<br>\n";
}
