<?php
// nmc_actions.php - Controller for NMC Inspection Management Module actions

require_once __DIR__ . '/config/db.php';
check_auth();

if (!is_admin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized: Admin privilege required.'));
    exit;
}

require_once __DIR__ . '/src/Controllers/NmcInspectionController.php';
NmcInspectionController::initTables($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add_inspection':
        $program_id = intval($_POST['program_id'] ?? 0);
        $inspection_date = trim($_POST['inspection_date'] ?? date('Y-m-d'));
        $title = trim($_POST['title'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($program_id > 0 && $inspection_date !== '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `nmc_inspections` (`program_id`, `inspection_date`, `title`, `academic_year`, `remarks`)
                    VALUES (:program_id, :inspection_date, :title, :academic_year, :remarks)
                ");
                $stmt->execute([
                    ':program_id' => $program_id,
                    ':inspection_date' => $inspection_date,
                    ':title' => $title,
                    ':academic_year' => $academic_year,
                    ':remarks' => $remarks
                ]);
                $new_id = $pdo->lastInsertId();

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'New NMC Inspection record created successfully!'];
                header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$new_id}");
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to create inspection: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Please fill in required fields.'];
        }
        $redirectProg = $program_id > 0 ? $program_id : '';
        header("Location: index.php?section=nmc-section&nmc_program_id={$redirectProg}");
        exit;

    case 'edit_inspection':
        $inspection_id = intval($_POST['inspection_id'] ?? 0);
        $program_id = intval($_POST['program_id'] ?? 0);
        $inspection_date = trim($_POST['inspection_date'] ?? date('Y-m-d'));
        $title = trim($_POST['title'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($inspection_id > 0 && $inspection_date !== '') {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `nmc_inspections`
                    SET `inspection_date` = :inspection_date, `title` = :title, `academic_year` = :academic_year, `remarks` = :remarks
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':inspection_date' => $inspection_date,
                    ':title' => $title,
                    ':academic_year' => $academic_year,
                    ':remarks' => $remarks,
                    ':id' => $inspection_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Inspection header details updated!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to update inspection: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'delete_inspection':
        $inspection_id = intval($_GET['inspection_id'] ?? $_POST['inspection_id'] ?? 0);
        $program_id = intval($_GET['program_id'] ?? $_POST['program_id'] ?? 0);

        if ($inspection_id > 0) {
            try {
                // Delete SAF file if exists
                $stmt = $pdo->prepare("SELECT saf_pdf FROM `nmc_inspections` WHERE id = :id");
                $stmt->execute([':id' => $inspection_id]);
                $saf_file = $stmt->fetchColumn();
                if ($saf_file && file_exists(__DIR__ . '/static/uploads/nmc_docs/' . $saf_file)) {
                    @unlink(__DIR__ . '/static/uploads/nmc_docs/' . $saf_file);
                }

                // Delete faculty declaration files
                $facStmt = $pdo->prepare("SELECT declaration_pdf FROM `nmc_inspection_faculty` WHERE inspection_id = :id");
                $facStmt->execute([':id' => $inspection_id]);
                $facFiles = $facStmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($facFiles as $ff) {
                    if ($ff && file_exists(__DIR__ . '/static/uploads/nmc_docs/' . $ff)) {
                        @unlink(__DIR__ . '/static/uploads/nmc_docs/' . $ff);
                    }
                }

                // Delete inspection record
                $del = $pdo->prepare("DELETE FROM `nmc_inspections` WHERE id = :id");
                $del->execute([':id' => $inspection_id]);

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'NMC Inspection record deleted successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Deletion failed: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}");
        exit;

    case 'add_faculty':
        $inspection_id = intval($_POST['inspection_id'] ?? 0);
        $program_id = intval($_POST['program_id'] ?? 0);
        $name = trim($_POST['faculty_name'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($inspection_id > 0 && $name !== '') {
            try {
                $decl_pdf = null;
                $decl_orig = null;
                $uploaded_at = null;

                if (!empty($_FILES['declaration_pdf']['name']) && $_FILES['declaration_pdf']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['declaration_pdf'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])) {
                        $nmcDir = __DIR__ . '/static/uploads/nmc_docs';
                        if (!file_exists($nmcDir)) {
                            @mkdir($nmcDir, 0777, true);
                        }
                        $uniqueName = 'decl_' . time() . '_' . uniqid() . '.' . $ext;
                        $targetPath = $nmcDir . '/' . $uniqueName;
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $decl_pdf = $uniqueName;
                            $decl_orig = $file['name'];
                            $uploaded_at = date('Y-m-d H:i:s');
                        }
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO `nmc_inspection_faculty` (`inspection_id`, `faculty_name`, `designation`, `department`, `declaration_pdf`, `declaration_original_name`, `declaration_uploaded_at`)
                    VALUES (:inspection_id, :name, :designation, :department, :decl_pdf, :decl_orig, :uploaded_at)
                ");
                $stmt->execute([
                    ':inspection_id' => $inspection_id,
                    ':name' => $name,
                    ':designation' => $designation,
                    ':department' => $department,
                    ':decl_pdf' => $decl_pdf,
                    ':decl_orig' => $decl_orig,
                    ':uploaded_at' => $uploaded_at
                ]);

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Participating faculty added successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add faculty: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'edit_faculty':
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $inspection_id = intval($_POST['inspection_id'] ?? 0);
        $program_id = intval($_POST['program_id'] ?? 0);
        $name = trim($_POST['faculty_name'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($faculty_id > 0 && $name !== '') {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `nmc_inspection_faculty`
                    SET `faculty_name` = :name, `designation` = :designation, `department` = :department
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':designation' => $designation,
                    ':department' => $department,
                    ':id' => $faculty_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Faculty details updated!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Update failed: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'delete_faculty':
        $faculty_id = intval($_GET['id'] ?? 0);
        $inspection_id = intval($_GET['inspection_id'] ?? 0);
        $program_id = intval($_GET['program_id'] ?? 0);

        if ($faculty_id > 0) {
            try {
                // Delete declaration file
                $stmt = $pdo->prepare("SELECT declaration_pdf FROM `nmc_inspection_faculty` WHERE id = :id");
                $stmt->execute([':id' => $faculty_id]);
                $file = $stmt->fetchColumn();
                if ($file && file_exists(__DIR__ . '/static/uploads/nmc_docs/' . $file)) {
                    @unlink(__DIR__ . '/static/uploads/nmc_docs/' . $file);
                }

                $del = $pdo->prepare("DELETE FROM `nmc_inspection_faculty` WHERE id = :id");
                $del->execute([':id' => $faculty_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Participating faculty member removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove faculty: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'upload_faculty_declaration':
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $inspection_id = intval($_POST['inspection_id'] ?? 0);
        $program_id = intval($_POST['program_id'] ?? 0);

        if ($faculty_id > 0 && !empty($_FILES['declaration_file']['name']) && $_FILES['declaration_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['declaration_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Only PDF or Image files are allowed.'];
                header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
                exit;
            }

            $nmcDir = __DIR__ . '/static/uploads/nmc_docs';
            if (!file_exists($nmcDir)) {
                @mkdir($nmcDir, 0777, true);
            }

            $uniqueName = 'decl_fac_' . $faculty_id . '_' . time() . '.' . $ext;
            $targetPath = $nmcDir . '/' . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Remove old file if exists
                $stmtPrev = $pdo->prepare("SELECT declaration_pdf FROM `nmc_inspection_faculty` WHERE id = :id");
                $stmtPrev->execute([':id' => $faculty_id]);
                $oldFile = $stmtPrev->fetchColumn();
                if ($oldFile && file_exists($nmcDir . '/' . $oldFile)) {
                    @unlink($nmcDir . '/' . $oldFile);
                }

                $stmt = $pdo->prepare("
                    UPDATE `nmc_inspection_faculty`
                    SET `declaration_pdf` = :pdf,
                        `declaration_original_name` = :orig,
                        `declaration_uploaded_at` = NOW()
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':pdf' => $uniqueName,
                    ':orig' => $file['name'],
                    ':id' => $faculty_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Faculty declaration document uploaded successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to save declaration document.'];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'delete_faculty_declaration':
        $faculty_id = intval($_GET['id'] ?? 0);
        $inspection_id = intval($_GET['inspection_id'] ?? 0);
        $program_id = intval($_GET['program_id'] ?? 0);

        if ($faculty_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT declaration_pdf FROM `nmc_inspection_faculty` WHERE id = :id");
                $stmt->execute([':id' => $faculty_id]);
                $file = $stmt->fetchColumn();

                if ($file && file_exists(__DIR__ . '/static/uploads/nmc_docs/' . $file)) {
                    @unlink(__DIR__ . '/static/uploads/nmc_docs/' . $file);
                }

                $upd = $pdo->prepare("
                    UPDATE `nmc_inspection_faculty`
                    SET `declaration_pdf` = NULL, `declaration_original_name` = NULL, `declaration_uploaded_at` = NULL
                    WHERE id = :id
                ");
                $upd->execute([':id' => $faculty_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Declaration document removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove declaration document.'];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'upload_saf':
        $inspection_id = intval($_POST['inspection_id'] ?? 0);
        $program_id = intval($_POST['program_id'] ?? 0);

        if ($inspection_id > 0 && !empty($_FILES['saf_file']['name']) && $_FILES['saf_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['saf_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Only PDF or Image files are allowed.'];
                header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
                exit;
            }

            $nmcDir = __DIR__ . '/static/uploads/nmc_docs';
            if (!file_exists($nmcDir)) {
                @mkdir($nmcDir, 0777, true);
            }

            $uniqueName = 'saf_doc_' . $inspection_id . '_' . time() . '.' . $ext;
            $targetPath = $nmcDir . '/' . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Delete previous SAF file if exists
                $stmtPrev = $pdo->prepare("SELECT saf_pdf FROM `nmc_inspections` WHERE id = :id");
                $stmtPrev->execute([':id' => $inspection_id]);
                $oldFile = $stmtPrev->fetchColumn();
                if ($oldFile && file_exists($nmcDir . '/' . $oldFile)) {
                    @unlink($nmcDir . '/' . $oldFile);
                }

                $stmt = $pdo->prepare("
                    UPDATE `nmc_inspections`
                    SET `saf_pdf` = :pdf,
                        `saf_original_name` = :orig,
                        `saf_uploaded_at` = NOW()
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':pdf' => $uniqueName,
                    ':orig' => $file['name'],
                    ':id' => $inspection_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Standard Assessment Form (SAF) uploaded successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to save uploaded SAF document.'];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    case 'delete_saf':
        $inspection_id = intval($_GET['inspection_id'] ?? $_POST['inspection_id'] ?? 0);
        $program_id = intval($_GET['program_id'] ?? $_POST['program_id'] ?? 0);

        if ($inspection_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT saf_pdf FROM `nmc_inspections` WHERE id = :id");
                $stmt->execute([':id' => $inspection_id]);
                $file = $stmt->fetchColumn();

                if ($file && file_exists(__DIR__ . '/static/uploads/nmc_docs/' . $file)) {
                    @unlink(__DIR__ . '/static/uploads/nmc_docs/' . $file);
                }

                $upd = $pdo->prepare("
                    UPDATE `nmc_inspections`
                    SET `saf_pdf` = NULL, `saf_original_name` = NULL, `saf_uploaded_at` = NULL
                    WHERE id = :id
                ");
                $upd->execute([':id' => $inspection_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Standard Assessment Form (SAF) document removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to delete SAF document.'];
            }
        }
        header("Location: index.php?section=nmc-section&nmc_program_id={$program_id}&nmc_inspection_id={$inspection_id}");
        exit;

    default:
        header("Location: index.php?section=nmc-section");
        exit;
}
