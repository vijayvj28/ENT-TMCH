<?php
// index.php
// Main dashboard entry point

require_once __DIR__ . '/config/db.php';

// Check auth
check_auth();

// Load Controllers
require_once __DIR__ . '/src/Controllers/PatientController.php';
require_once __DIR__ . '/src/Controllers/CensusController.php';
require_once __DIR__ . '/src/Controllers/DocumentController.php';
require_once __DIR__ . '/src/Controllers/AcademicExamController.php';
require_once __DIR__ . '/src/Controllers/NmcInspectionController.php';
require_once __DIR__ . '/src/Controllers/FacultyController.php';
require_once __DIR__ . '/src/Controllers/CochlearController.php';

// Instantiate controllers and load dashboard data
$patientCtrl = new PatientController();
$censusCtrl = new CensusController();
$docCtrl = new DocumentController();
$academicExamCtrl = new AcademicExamController();
$nmcInspectionCtrl = new NmcInspectionController();
$facultyCtrl = new FacultyController();
$cochlearCtrl = new CochlearController();

$dashboardData = $patientCtrl->getDashboardData($pdo);
$foldersData = $docCtrl->getFoldersAndFiles($pdo);

// Cochlear & AVT Data
$cochlear_search = $_GET['cochlear_search'] ?? '';
$cochlear_stage = $_GET['cochlear_stage'] ?? '';
$cochlear_surgery = $_GET['cochlear_surgery'] ?? '';
$cochlearCases = $cochlearCtrl->getCochlearCases($pdo, $cochlear_search, $cochlear_stage, $cochlear_surgery);

$avt_search = $_GET['avt_search'] ?? '';
$avt_status = $_GET['avt_status'] ?? '';
$avtCases = $cochlearCtrl->getAvtCases($pdo, $avt_search, $avt_status);

// Academic Exam Data
$coursesList = $academicExamCtrl->getCourses($pdo);
$selected_course_id = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? intval($_GET['course_id']) : null;
$selected_exam_id = isset($_GET['exam_id']) && $_GET['exam_id'] !== '' ? intval($_GET['exam_id']) : null;

$selected_course = $selected_course_id ? $academicExamCtrl->getCourse($pdo, $selected_course_id) : null;
$course_exams = $selected_course_id ? $academicExamCtrl->getExaminationsByCourse($pdo, $selected_course_id, $_GET['search'] ?? '') : [];
$exam_details = $selected_exam_id ? $academicExamCtrl->getExaminationDetails($pdo, $selected_exam_id) : null;

// NMC Inspection Data
$nmcProgramsList = $nmcInspectionCtrl->getPrograms($pdo);
$selected_nmc_program_id = isset($_GET['nmc_program_id']) && $_GET['nmc_program_id'] !== '' ? intval($_GET['nmc_program_id']) : null;
$selected_nmc_inspection_id = isset($_GET['nmc_inspection_id']) && $_GET['nmc_inspection_id'] !== '' ? intval($_GET['nmc_inspection_id']) : null;

$selected_nmc_program = $selected_nmc_program_id ? $nmcInspectionCtrl->getProgram($pdo, $selected_nmc_program_id) : null;
$nmc_program_inspections = $selected_nmc_program_id ? $nmcInspectionCtrl->getInspectionsByProgram($pdo, $selected_nmc_program_id, $_GET['search'] ?? '') : [];
$nmc_inspection_details = $selected_nmc_inspection_id ? $nmcInspectionCtrl->getInspectionDetails($pdo, $selected_nmc_inspection_id) : null;

// Faculty Management Data
$selected_dept_tab = $_GET['tab'] ?? null;
$selected_faculty_id = isset($_GET['faculty_id']) && $_GET['faculty_id'] !== '' ? intval($_GET['faculty_id']) : null;
$facultiesList = $facultyCtrl->getFaculties($pdo, $_GET['search'] ?? '');
$selected_faculty_details = $selected_faculty_id ? $facultyCtrl->getFacultyDetails($pdo, $selected_faculty_id) : null;

$active_section = $_GET['section'] ?? 'dashboard-section';
if ($selected_course_id || $selected_exam_id) {
    $active_section = 'examination-section';
} elseif ($selected_nmc_program_id || $selected_nmc_inspection_id) {
    $active_section = 'nmc-section';
} elseif (in_array($selected_dept_tab, ['faculties', 'roster', 'gold-medal', 'musqan', 'drb', 'cme']) || $selected_faculty_id) {
    $active_section = 'activities-section';
}

$active_folder_id = $_GET['folder_id'] ?? null;
if ($active_folder_id === 'null' || $active_folder_id === 'None') {
    $active_folder_id = null;
}

$view = 'dashboard';
require_once __DIR__ . '/templates/base.php';
exit;
