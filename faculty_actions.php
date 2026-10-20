<?php
// faculty_actions.php - Controller for Faculty Management Module actions

require_once __DIR__ . '/config/db.php';
check_auth();

if (!is_admin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized: Admin privilege required.'));
    exit;
}

require_once __DIR__ . '/src/Controllers/FacultyController.php';
FacultyController::initTables($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add_faculty':
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $nationality = trim($_POST['nationality'] ?? 'Indian');
        $medical_reg_no = trim($_POST['medical_reg_no'] ?? '');

        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? 'Department of ENT (Otorhinolaryngology)');
        $institution = trim($_POST['institution'] ?? 'Government Medical College & Hospital');
        $doj = !empty($_POST['doj']) ? $_POST['doj'] : null;
        $employment_status = trim($_POST['employment_status'] ?? 'Regular');
        $faculty_status = trim($_POST['faculty_status'] ?? 'Current');
        $nmc_code = trim($_POST['nmc_code'] ?? '');

        $overview_summary = trim($_POST['overview_summary'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');
        $achievements = trim($_POST['achievements'] ?? '');

        if ($full_name !== '' && $designation !== '') {
            try {
                $photo_filename = null;
                if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['photo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $photosDir = __DIR__ . '/static/uploads/faculty_photos';
                        if (!file_exists($photosDir)) {
                            @mkdir($photosDir, 0777, true);
                        }
                        $uniqueName = 'photo_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $photosDir . '/' . $uniqueName)) {
                            $photo_filename = $uniqueName;
                        }
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO `faculties` 
                    (`full_name`, `gender`, `dob`, `mobile_number`, `email`, `address`, `nationality`, `medical_reg_no`, `photo`, 
                     `designation`, `department`, `institution`, `doj`, `employment_status`, `faculty_status`, `nmc_code`, `overview_summary`, `specialties`, `achievements`)
                    VALUES (:name, :gender, :dob, :mobile, :email, :address, :nat, :reg_no, :photo, 
                            :desig, :dept, :inst, :doj, :emp_status, :fac_status, :nmc_code, :overview, :spec, :achieve)
                ");
                $stmt->execute([
                    ':name' => $full_name,
                    ':gender' => $gender,
                    ':dob' => $dob,
                    ':mobile' => $mobile_number,
                    ':email' => $email,
                    ':address' => $address,
                    ':nat' => $nationality,
                    ':reg_no' => $medical_reg_no,
                    ':photo' => $photo_filename,
                    ':desig' => $designation,
                    ':dept' => $department,
                    ':inst' => $institution,
                    ':doj' => $doj,
                    ':emp_status' => $employment_status,
                    ':fac_status' => $faculty_status,
                    ':nmc_code' => $nmc_code,
                    ':overview' => $overview_summary,
                    ':spec' => $specialties,
                    ':achieve' => $achievements
                ]);
                $new_id = $pdo->lastInsertId();

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'New Faculty member registered successfully!'];
                header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$new_id}");
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to register faculty: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Please provide Full Name and Designation.'];
        }
        header("Location: index.php?section=activities-section&tab=faculties");
        exit;

    case 'edit_faculty':
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $nationality = trim($_POST['nationality'] ?? 'Indian');
        $medical_reg_no = trim($_POST['medical_reg_no'] ?? '');

        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $institution = trim($_POST['institution'] ?? '');
        $doj = !empty($_POST['doj']) ? $_POST['doj'] : null;
        $employment_status = trim($_POST['employment_status'] ?? 'Regular');
        $faculty_status = trim($_POST['faculty_status'] ?? 'Current');
        $nmc_code = trim($_POST['nmc_code'] ?? '');

        $overview_summary = trim($_POST['overview_summary'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');
        $achievements = trim($_POST['achievements'] ?? '');

        if ($faculty_id > 0 && $full_name !== '') {
            try {
                $photo_clause = "";
                $params = [
                    ':name' => $full_name,
                    ':gender' => $gender,
                    ':dob' => $dob,
                    ':mobile' => $mobile_number,
                    ':email' => $email,
                    ':address' => $address,
                    ':nat' => $nationality,
                    ':reg_no' => $medical_reg_no,
                    ':desig' => $designation,
                    ':dept' => $department,
                    ':inst' => $institution,
                    ':doj' => $doj,
                    ':emp_status' => $employment_status,
                    ':fac_status' => $faculty_status,
                    ':nmc_code' => $nmc_code,
                    ':overview' => $overview_summary,
                    ':spec' => $specialties,
                    ':achieve' => $achievements,
                    ':id' => $faculty_id
                ];

                if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['photo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $photosDir = __DIR__ . '/static/uploads/faculty_photos';
                        $uniqueName = 'photo_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $photosDir . '/' . $uniqueName)) {
                            $photo_clause = ", `photo` = :photo";
                            $params[':photo'] = $uniqueName;
                        }
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE `faculties`
                    SET `full_name` = :name, `gender` = :gender, `dob` = :dob, `mobile_number` = :mobile,
                        `email` = :email, `address` = :address, `nationality` = :nat, `medical_reg_no` = :reg_no,
                        `designation` = :desig, `department` = :dept, `institution` = :inst, `doj` = :doj,
                        `employment_status` = :emp_status, `faculty_status` = :fac_status, `nmc_code` = :nmc_code,
                        `overview_summary` = :overview, `specialties` = :spec, `achievements` = :achieve {$photo_clause}
                    WHERE `id` = :id
                ");
                $stmt->execute($params);

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Faculty details updated successfully!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Update failed: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    case 'delete_faculty':
        $faculty_id = intval($_GET['faculty_id'] ?? $_POST['faculty_id'] ?? 0);

        if ($faculty_id > 0) {
            try {
                // Delete photo
                $stmt = $pdo->prepare("SELECT photo FROM `faculties` WHERE id = :id");
                $stmt->execute([':id' => $faculty_id]);
                $photo = $stmt->fetchColumn();
                if ($photo && file_exists(__DIR__ . '/static/uploads/faculty_photos/' . $photo)) {
                    @unlink(__DIR__ . '/static/uploads/faculty_photos/' . $photo);
                }

                // Delete document files
                $docStmt = $pdo->prepare("SELECT filename FROM `faculty_documents` WHERE faculty_id = :id");
                $docStmt->execute([':id' => $faculty_id]);
                $docs = $docStmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($docs as $df) {
                    if ($df && file_exists(__DIR__ . '/static/uploads/faculty_docs/' . $df)) {
                        @unlink(__DIR__ . '/static/uploads/faculty_docs/' . $df);
                    }
                }

                $del = $pdo->prepare("DELETE FROM `faculties` WHERE id = :id");
                $del->execute([':id' => $faculty_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Faculty record deleted successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Deletion failed: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties");
        exit;

    case 'add_qualification':
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $qualification = trim($_POST['qualification'] ?? '');
        $speciality = trim($_POST['speciality'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $passing_year = !empty($_POST['passing_year']) ? intval($_POST['passing_year']) : null;

        if ($faculty_id > 0 && $qualification !== '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `faculty_qualifications` (`faculty_id`, `qualification`, `speciality`, `university`, `passing_year`)
                    VALUES (:fid, :qual, :spec, :univ, :year)
                ");
                $stmt->execute([
                    ':fid' => $faculty_id,
                    ':qual' => $qualification,
                    ':spec' => $speciality,
                    ':univ' => $university,
                    ':year' => $passing_year
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Educational qualification added!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add qualification: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    case 'delete_qualification':
        $qid = intval($_GET['id'] ?? 0);
        $faculty_id = intval($_GET['faculty_id'] ?? 0);

        if ($qid > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `faculty_qualifications` WHERE id = :id");
                $stmt->execute([':id' => $qid]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Qualification removed.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove qualification.'];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    case 'add_experience':
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $institution = trim($_POST['institution'] ?? '');
        $start_date = $_POST['start_date'] ?? date('Y-m-d');
        $currently_working = isset($_POST['currently_working']) ? 1 : 0;
        $end_date = ($currently_working || empty($_POST['end_date'])) ? null : $_POST['end_date'];

        if ($faculty_id > 0 && $designation !== '' && $start_date !== '') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `faculty_experiences` (`faculty_id`, `designation`, `department`, `institution`, `start_date`, `end_date`, `currently_working`)
                    VALUES (:fid, :desig, :dept, :inst, :sdate, :edate, :current)
                ");
                $stmt->execute([
                    ':fid' => $faculty_id,
                    ':desig' => $designation,
                    ':dept' => $department,
                    ':inst' => $institution,
                    ':sdate' => $start_date,
                    ':edate' => $end_date,
                    ':current' => $currently_working
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Teaching experience added! Total experience updated.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to add experience: ' . $e->getMessage()];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    case 'delete_experience':
        $eid = intval($_GET['id'] ?? 0);
        $faculty_id = intval($_GET['faculty_id'] ?? 0);

        if ($eid > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `faculty_experiences` WHERE id = :id");
                $stmt->execute([':id' => $eid]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Experience entry removed. Total experience updated.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove experience entry.'];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    case 'upload_document':
        $faculty_id = intval($_POST['faculty_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Educational Certificate');

        if ($faculty_id > 0 && $title !== '' && !empty($_FILES['document_file']['name']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'])) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Only PDF, PNG, JPG, or JPEG files are allowed.'];
                header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
                exit;
            }

            $docsDir = __DIR__ . '/static/uploads/faculty_docs';
            if (!file_exists($docsDir)) {
                @mkdir($docsDir, 0777, true);
            }

            $uniqueName = 'doc_fac_' . $faculty_id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $docsDir . '/' . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("
                    INSERT INTO `faculty_documents` (`faculty_id`, `title`, `category`, `filename`, `original_name`, `file_size`)
                    VALUES (:fid, :title, :cat, :filename, :orig, :size)
                ");
                $stmt->execute([
                    ':fid' => $faculty_id,
                    ':title' => $title,
                    ':cat' => $category,
                    ':filename' => $uniqueName,
                    ':orig' => $file['name'],
                    ':size' => $file['size']
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Supporting document uploaded successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to save document.'];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    case 'delete_document':
        $doc_id = intval($_GET['id'] ?? 0);
        $faculty_id = intval($_GET['faculty_id'] ?? 0);

        if ($doc_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT filename FROM `faculty_documents` WHERE id = :id");
                $stmt->execute([':id' => $doc_id]);
                $file = $stmt->fetchColumn();

                if ($file && file_exists(__DIR__ . '/static/uploads/faculty_docs/' . $file)) {
                    @unlink(__DIR__ . '/static/uploads/faculty_docs/' . $file);
                }

                $del = $pdo->prepare("DELETE FROM `faculty_documents` WHERE id = :id");
                $del->execute([':id' => $doc_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Document removed successfully.'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to remove document.'];
            }
        }
        header("Location: index.php?section=activities-section&tab=faculties&faculty_id={$faculty_id}");
        exit;

    default:
        header("Location: index.php?section=activities-section&tab=faculties");
        exit;
}
