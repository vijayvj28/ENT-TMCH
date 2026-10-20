<?php
// cochlear_actions.php
// Backend Action Handler for Cochlear Implant Cases and AVT Tracking

require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/CochlearController.php';
$cochlearCtrl = new CochlearController();

$action = $_REQUEST['action'] ?? '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;

    try {
        switch ($action) {
            case 'save_case':
                $cochlearCtrl->updateCochlearCase($pdo, $_POST);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Cochlear case details updated successfully.']);
                    exit;
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Cochlear case updated successfully.'];
                header("Location: index.php?section=cochlear-section&case_id=" . urlencode($_POST['cochlear_case_id'] ?? ''));
                exit;

            case 'move_to_avt':
                $caseId = trim($_POST['cochlear_case_id'] ?? '');
                $enrollDate = !empty($_POST['enrollment_date']) ? $_POST['enrollment_date'] : date('Y-m-d');
                $startDate = !empty($_POST['training_start_date']) ? $_POST['training_start_date'] : date('Y-m-d');
                $therapist = trim($_POST['therapist_name'] ?? 'Department AVT Audiologist');

                $cochlearCtrl->moveToAvt($pdo, $caseId, $enrollDate, $startDate, $therapist, $userId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => "Case $caseId successfully moved to AVT 100-Day Program."]);
                    exit;
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Case $caseId successfully enrolled in AVT."];
                header("Location: index.php?section=avt-section&case_id=" . urlencode($caseId));
                exit;

            case 'record_session':
                $caseId = trim($_POST['cochlear_case_id'] ?? '');
                $cochlearCtrl->recordAvtSession($pdo, $_POST, $userId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'AVT Session recorded successfully.']);
                    exit;
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'AVT Session recorded successfully.'];
                header("Location: index.php?section=avt-section&case_id=" . urlencode($caseId));
                exit;

            case 'record_completion':
                $caseId = trim($_POST['cochlear_case_id'] ?? '');
                $status = $_POST['clinical_completion_status'] ?? 'Clinically Successful';
                $cDate = !empty($_POST['completion_date']) ? $_POST['completion_date'] : date('Y-m-d');
                $notes = trim($_POST['completion_notes'] ?? '');

                $cochlearCtrl->recordClinicalCompletion($pdo, $caseId, $status, $cDate, $notes, $userId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Clinical completion recorded successfully.']);
                    exit;
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'AVT Clinical completion recorded successfully.'];
                header("Location: index.php?section=avt-section&case_id=" . urlencode($caseId));
                exit;

            default:
                throw new Exception("Unknown action: $action");
        }
    } catch (Exception $e) {
        if ($isAjax) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error: ' . $e->getMessage()];
        header("Location: index.php?section=cochlear-section");
        exit;
    }
}

// GET Requests for AJAX details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    try {
        switch ($action) {
            case 'get_case':
                $caseId = trim($_GET['case_id'] ?? '');
                $data = $cochlearCtrl->getCochlearCaseDetails($pdo, $caseId);
                if (!$data) throw new Exception("Case not found.");
                echo json_encode(['success' => true, 'case' => $data]);
                exit;

            case 'get_avt_analysis':
                $caseId = trim($_GET['case_id'] ?? '');
                $data = $cochlearCtrl->getAvtCaseAnalysis($pdo, $caseId);
                if (!$data) throw new Exception("AVT enrollment not found for this case.");
                echo json_encode(['success' => true, 'data' => $data]);
                exit;

            default:
                throw new Exception("Unknown GET action.");
        }
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
