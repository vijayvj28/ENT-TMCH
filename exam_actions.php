<?php
// exam_actions.php - Controller for Academic Examination Module actions

require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/AcademicExamController.php';
AcademicExamController::initTables($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!is_admin() && $action !== 'download_sample_template') {
    header('Location: index.php?error=' . urlencode('Unauthorized: Admin privilege required.'));
    exit;
}

switch ($action) {
    case 'add_exam':
        $course_id = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $exam_type = trim($_POST['exam_type'] ?? 'Practical Examination');
        $exam_date = trim($_POST['exam_date'] ?? date('Y-m-d'));
        $academic_year = trim($_POST['academic_year'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($course_id > 0 && $title !== '') {
            try {
                $pdf_filename = null;
                $orig_filename = null;
                $uploaded_at = null;

                if (!empty($_FILES['final_marksheet']['name']) && $_FILES['final_marksheet']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['final_marksheet'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])) {
                        $marksheetDir = __DIR__ . '/static/uploads/marksheets';
                        if (!file_exists($marksheetDir)) {
                            @mkdir($marksheetDir, 0777, true);
                        }
                        $uniqueName = 'marksheet_' . time() . '_' . uniqid() . '.' . $ext;
                        $targetPath = $marksheetDir . '/' . $uniqueName;
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $pdf_filename = $uniqueName;
                            $orig_filename = $file['name'];
                            $uploaded_at = date('Y-m-d H:i:s');
                        }
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO `academic_examinations` 
                    (`course_id`, `title`, `exam_type`, `exam_date`, `academic_year`, `notes`, `final_marksheet_pdf`, `final_marksheet_original_name`, `final_marksheet_uploaded_at`)
                    VALUES (:course_id, :title, :exam_type, :exam_date, :academic_year, :notes, :pdf, :orig, :uploaded_at)
                ");
                $stmt->execute([
                    ':course_id' => $course_id,
                    ':title' => $title,
                    ':exam_type' => $exam_type,
                    ':exam_date' => $exam_date,
                    ':academic_year' => $academic_year,
                    ':notes' => $notes,
                    ':pdf' => $pdf_filename,
                    ':orig' => $orig_filename,
                    ':uploaded_at' => $uploaded_at
                ]);
                $new_id = $pdo->lastInsertId();

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'New Examination created successfully!'];
                header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$new_id}");
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to create examination: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Please provide examination title and course.'];
        }
        $redirectCourse = $course_id > 0 ? $course_id : '';
        header("Location: index.php?section=examination-section&course_id={$redirectCourse}");
        exit;

    case 'edit_exam':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $exam_type = trim($_POST['exam_type'] ?? 'Practical Examination');
        $exam_date = trim($_POST['exam_date'] ?? date('Y-m-d'));
        $academic_year = trim($_POST['academic_year'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($exam_id > 0 && $title !== '') {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `academic_examinations`
                    SET `title` = :title, `exam_type` = :exam_type, `exam_date` = :exam_date, 
                        `academic_year` = :academic_year, `notes` = :notes
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':exam_type' => $exam_type,
                    ':exam_date' => $exam_date,
                    ':academic_year' => $academic_year,
                    ':notes' => $notes,
                    ':id' => $exam_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Examination header updated successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Update failed: ' . $e->getMessage()];
            }
        }
        $course_id = intval($_POST['course_id'] ?? 0);
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'delete_exam':
        $exam_id = intval($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);
        $course_id = intval($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

        if ($exam_id > 0) {
            try {
                // Delete uploaded marksheet file if any
                $stmt = $pdo->prepare("SELECT final_marksheet_pdf FROM `academic_examinations` WHERE id = :id");
                $stmt->execute([':id' => $exam_id]);
                $file = $stmt->fetchColumn();
                if ($file && file_exists(__DIR__ . '/static/uploads/marksheets/' . $file)) {
                    @unlink(__DIR__ . '/static/uploads/marksheets/' . $file);
                }

                $delStmt = $pdo->prepare("DELETE FROM `academic_examinations` WHERE id = :id");
                $delStmt->execute([':id' => $exam_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Examination record deleted successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Deletion failed: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}");
        exit;

    case 'add_examiner':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $institution = trim($_POST['institution'] ?? '');

        if ($exam_id > 0 && $name !== '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_examiners` (`examination_id`, `name`, `designation`, `department`, `institution`)
                    VALUES (:exam_id, :name, :designation, :department, :institution)
                ");
                $stmt->execute([
                    ':exam_id' => $exam_id,
                    ':name' => $name,
                    ':designation' => $designation,
                    ':department' => $department,
                    ':institution' => $institution
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Examiner added successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add examiner: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'delete_examiner':
        $examiner_id = intval($_GET['id'] ?? 0);
        $exam_id = intval($_GET['exam_id'] ?? 0);
        $course_id = intval($_GET['course_id'] ?? 0);

        if ($examiner_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `academic_exam_examiners` WHERE id = :id");
                $stmt->execute([':id' => $examiner_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Examiner removed successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove examiner.'];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'import_examiners':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $json = $_POST['examiners_json'] ?? '';
        $examiners = json_decode($json, true);

        if ($exam_id > 0 && is_array($examiners) && count($examiners) > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_examiners` (`examination_id`, `name`, `designation`, `department`, `institution`)
                    VALUES (:exam_id, :name, :designation, :department, :institution)
                ");
                $count = 0;
                foreach ($examiners as $ex) {
                    $name = trim($ex['name'] ?? $ex['Examiner Name'] ?? '');
                    if ($name === '') continue;
                    $designation = trim($ex['designation'] ?? $ex['Designation'] ?? '');
                    $department = trim($ex['department'] ?? $ex['Department'] ?? '');
                    $institution = trim($ex['institution'] ?? $ex['Institution'] ?? '');
                    
                    $stmt->execute([
                        ':exam_id' => $exam_id,
                        ':name' => $name,
                        ':designation' => $designation,
                        ':department' => $department,
                        ':institution' => $institution
                    ]);
                    $count++;
                }
                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Successfully imported {$count} examiners!"];
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Import failed: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'No valid examiner data to import.'];
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'add_participant':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $name = trim($_POST['student_name'] ?? '');
        $reg = trim($_POST['register_number'] ?? '');
        $batch = trim($_POST['batch_year'] ?? '');

        if ($exam_id > 0 && $name !== '' && $reg !== '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_participants` (`examination_id`, `student_name`, `register_number`, `batch_year`)
                    VALUES (:exam_id, :name, :reg, :batch)
                ");
                $stmt->execute([
                    ':exam_id' => $exam_id,
                    ':name' => $name,
                    ':reg' => $reg,
                    ':batch' => $batch
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Participant registered successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add participant: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'delete_participant':
        $part_id = intval($_GET['id'] ?? 0);
        $exam_id = intval($_GET['exam_id'] ?? 0);
        $course_id = intval($_GET['course_id'] ?? 0);

        if ($part_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `academic_exam_participants` WHERE id = :id");
                $stmt->execute([':id' => $part_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Participant removed successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove participant.'];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'import_participants':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $json = $_POST['participants_json'] ?? '';
        $participants = json_decode($json, true);

        if ($exam_id > 0 && is_array($participants) && count($participants) > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_participants` (`examination_id`, `student_name`, `register_number`, `batch_year`)
                    VALUES (:exam_id, :name, :reg, :batch)
                ");
                $count = 0;
                foreach ($participants as $p) {
                    $name = trim($p['student_name'] ?? $p['Student Name'] ?? '');
                    $reg = trim($p['register_number'] ?? $p['Register Number'] ?? '');
                    if ($name === '' || $reg === '') continue;
                    $batch = trim($p['batch_year'] ?? $p['Batch / Year'] ?? $p['Batch'] ?? '');

                    $stmt->execute([
                        ':exam_id' => $exam_id,
                        ':name' => $name,
                        ':reg' => $reg,
                        ':batch' => $batch
                    ]);
                    $count++;
                }
                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Successfully imported {$count} participants!"];
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Import failed: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'No valid participant data to import.'];
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'add_component':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $name = trim($_POST['component_name'] ?? '');
        $max_marks = floatval($_POST['max_marks'] ?? 100);

        if ($exam_id > 0 && $name !== '' && $max_marks > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_components` (`examination_id`, `component_name`, `max_marks`)
                    VALUES (:exam_id, :name, :max)
                ");
                $stmt->execute([
                    ':exam_id' => $exam_id,
                    ':name' => $name,
                    ':max' => $max_marks
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Assessment component added successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add assessment component: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'delete_component':
        $comp_id = intval($_GET['id'] ?? 0);
        $exam_id = intval($_GET['exam_id'] ?? 0);
        $course_id = intval($_GET['course_id'] ?? 0);

        if ($comp_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `academic_exam_components` WHERE id = :id");
                $stmt->execute([':id' => $comp_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Assessment component removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove assessment component.'];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'save_marks':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $marksData = $_POST['marks'] ?? []; // Array [participant_id][component_id] = val

        if ($exam_id > 0 && is_array($marksData)) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_marks` (`examination_id`, `participant_id`, `component_id`, `marks_obtained`)
                    VALUES (:exam_id, :part_id, :comp_id, :marks)
                    ON DUPLICATE KEY UPDATE `marks_obtained` = :marks_upd
                ");
                $savedCount = 0;
                foreach ($marksData as $part_id => $comps) {
                    if (!is_array($comps)) continue;
                    foreach ($comps as $comp_id => $val) {
                        $val_num = ($val !== '' && $val !== null) ? floatval($val) : null;
                        $stmt->execute([
                            ':exam_id' => $exam_id,
                            ':part_id' => intval($part_id),
                            ':comp_id' => intval($comp_id),
                            ':marks' => $val_num,
                            ':marks_upd' => $val_num
                        ]);
                        $savedCount++;
                    }
                }
                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Student marks saved successfully!"];
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to save marks: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'add_custom_section':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $json_data = $_POST['content_json'] ?? '';

        if ($exam_id > 0 && $title !== '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `academic_exam_custom_sections` (`examination_id`, `title`, `content_json`)
                    VALUES (:exam_id, :title, :json_data)
                ");
                $stmt->execute([
                    ':exam_id' => $exam_id,
                    ':title' => $title,
                    ':json_data' => $json_data
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Custom table/section added!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add custom section: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'delete_custom_section':
        $sec_id = intval($_GET['id'] ?? 0);
        $exam_id = intval($_GET['exam_id'] ?? 0);
        $course_id = intval($_GET['course_id'] ?? 0);

        if ($sec_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `academic_exam_custom_sections` WHERE id = :id");
                $stmt->execute([':id' => $sec_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Custom section removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove custom section.'];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'upload_final_marksheet':
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);

        if ($exam_id > 0 && !empty($_FILES['marksheet_file']['name']) && $_FILES['marksheet_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['marksheet_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Only PDF or Image files are allowed.'];
                header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
                exit;
            }

            if ($file['size'] > 20 * 1024 * 1024) { // 20MB limit
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'File size exceeds 20MB limit.'];
                header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
                exit;
            }

            $marksheetDir = __DIR__ . '/static/uploads/marksheets';
            if (!file_exists($marksheetDir)) {
                @mkdir($marksheetDir, 0777, true);
            }

            $uniqueName = 'final_marksheet_' . $exam_id . '_' . time() . '.' . $ext;
            $targetPath = $marksheetDir . '/' . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("
                    UPDATE `academic_examinations`
                    SET `final_marksheet_pdf` = :pdf,
                        `final_marksheet_original_name` = :orig,
                        `final_marksheet_uploaded_at` = NOW()
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':pdf' => $uniqueName,
                    ':orig' => $file['name'],
                    ':id' => $exam_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Final Marksheet uploaded successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to save uploaded file.'];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'delete_final_marksheet':
        $exam_id = intval($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);
        $course_id = intval($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

        if ($exam_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT final_marksheet_pdf FROM `academic_examinations` WHERE id = :id");
                $stmt->execute([':id' => $exam_id]);
                $file = $stmt->fetchColumn();

                if ($file && file_exists(__DIR__ . '/static/uploads/marksheets/' . $file)) {
                    @unlink(__DIR__ . '/static/uploads/marksheets/' . $file);
                }

                $upd = $pdo->prepare("
                    UPDATE `academic_examinations`
                    SET `final_marksheet_pdf` = NULL, `final_marksheet_original_name` = NULL, `final_marksheet_uploaded_at` = NULL
                    WHERE id = :id
                ");
                $upd->execute([':id' => $exam_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Final Marksheet document removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to delete mark sheet document.'];
            }
        }
        header("Location: index.php?section=examination-section&course_id={$course_id}&exam_id={$exam_id}");
        exit;

    case 'download_sample_template':
        $type = $_GET['type'] ?? 'participants';
        header('Content-Type: text/csv');

        if ($type === 'examiners') {
            header('Content-Disposition: attachment; filename="Sample_Examiners_Import_Template.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['S.No', 'Examiner Name', 'Designation', 'Department', 'Institution']);
            fputcsv($output, [1, 'Dr. Arunkumar M.', 'Professor & HOD', 'ENT (Otorhinolaryngology)', 'Government Medical College']);
            fputcsv($output, [2, 'Dr. Senthil Nathan', 'Associate Professor', 'ENT - Head & Neck Surgery', 'Stanley Medical College']);
            fclose($output);
        } else {
            header('Content-Disposition: attachment; filename="Sample_Participants_Import_Template.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['S.No', 'Student Name', 'Register Number', 'Batch / Year']);
            fputcsv($output, [1, 'VAISHNAVI R.', '86502101', '2026-2027']);
            fputcsv($output, [2, 'KUMARAN S.', '86502102', '2026-2027']);
            fclose($output);
        }
        exit;

    default:
        header("Location: index.php?section=examination-section");
        exit;
}
