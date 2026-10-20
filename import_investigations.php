<?php
// import_investigations.php
require_once __DIR__ . '/config/db.php';
check_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gemini API Key is not configured. Please define GEMINI_API_KEY in config/db.php. Get a free key from: https://aistudio.google.com/app/apikey'
    ]);
    exit;
}

if (empty($_FILES['import_file']['name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error occurred.']);
    exit;
}

$allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
$filename = $_FILES['import_file']['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_exts)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed types: PNG, JPG, JPEG, GIF, PDF.']);
    exit;
}

$tmp_path = $_FILES['import_file']['tmp_name'];
$file_data = file_get_contents($tmp_path);
$base64_data = base64_encode($file_data);

$mime_type = $_FILES['import_file']['type'];
if (empty($mime_type)) {
    if ($ext === 'pdf') $mime_type = 'application/pdf';
    else $mime_type = 'image/' . $ext;
}

// Full schema containing the exact keys utilized in the investigations JSON chart
$schema_keys = [
    'height', 'weight', 'bmi', 'blood_group', 'scheme_card', 'scheme_preauth',
    'cbc1_date', 'cbc1_wbc', 'cbc1_lym', 'cbc1_mxd', 'cbc1_neut', 'cbc1_rbc', 'cbc1_hgb', 'cbc1_hct', 'cbc1_mcv', 'cbc1_mch', 'cbc1_mchc', 'cbc1_rdw_sd', 'cbc1_plt',
    'cbc2_date', 'cbc2_wbc', 'cbc2_lym', 'cbc2_mxd', 'cbc2_neut', 'cbc2_rbc', 'cbc2_hgb', 'cbc2_hct', 'cbc2_mcv', 'cbc2_mch', 'cbc2_mchc', 'cbc2_rdw_sd', 'cbc2_plt',
    'cbc3_date', 'cbc3_wbc', 'cbc3_lym', 'cbc3_mxd', 'cbc3_neut', 'cbc3_rbc', 'cbc3_hgb', 'cbc3_hct', 'cbc3_mcv', 'cbc3_mch', 'cbc3_mchc', 'cbc3_rdw_sd', 'cbc3_plt',
    'lft1_date', 'lft1_sgot', 'lft1_sgpt', 'lft1_sbt', 'lft1_conju', 'lft1_unconju', 'lft1_sap', 'lft1_sr_prot', 'lft1_albu', 'lft1_globu', 'lft1_na', 'lft1_k', 'lft1_cl', 'lft1_ca',
    'lft2_date', 'lft2_sgot', 'lft2_sgpt', 'lft2_sbt', 'lft2_conju', 'lft2_unconju', 'lft2_sap', 'lft2_sr_prot', 'lft2_albu', 'lft2_globu', 'lft2_na', 'lft2_k', 'lft2_cl', 'lft2_ca',
    'lft3_date', 'lft3_sgot', 'lft3_sgpt', 'lft3_sbt', 'lft3_conju', 'lft3_unconju', 'lft3_sap', 'lft3_sr_prot', 'lft3_albu', 'lft3_globu', 'lft3_na', 'lft3_k', 'lft3_cl', 'lft3_ca',
    'rft1_date', 'rft1_rbs', 'rft1_fbs', 'rft1_ppbs', 'rft1_urea', 'rft1_crea', 'rft1_pt_inr', 'rft1_hbsag', 'rft1_hcv', 'rft1_vctc',
    'rft2_date', 'rft2_rbs', 'rft2_fbs', 'rft2_ppbs', 'rft2_urea', 'rft2_crea', 'rft2_pt_inr', 'rft2_hbsag', 'rft2_hcv', 'rft2_vctc',
    'rft3_date', 'rft3_rbs', 'rft3_fbs', 'rft3_ppbs', 'rft3_urea', 'rft3_crea', 'rft3_pt_inr', 'rft3_hbsag', 'rft3_hcv', 'rft3_vctc',
    'sputum_afb_date', 'sputum_afb_num', 'sputum_afb_report',
    'sputum_cbnaat_date', 'sputum_cbnaat_num', 'sputum_cbnaat_report',
    'sputum_mgit_date', 'sputum_mgit_num', 'sputum_mgit_report',
    'sputum_cs_date', 'sputum_cs_num', 'sputum_cs_report',
    'echo_report', 'radiological_xray', 'radiological_ct', 'radiological_usg',
    'pfa_colour', 'pfa_ada', 'pfa_protein', 'pfa_sugar', 'pfa_pf_ldh', 'pfa_serum_ldh', 'pfa_cbnaat', 'pfa_culture_sensitivity', 'pfa_gram_stain',
    'pfa_cell_count_s1', 'pfa_cell_count_s2', 'pfa_cell_count_s3',
    'pfa_cytology_s1', 'pfa_cytology_s2', 'pfa_cytology_s3',
    'pfa_cell_block_s1', 'pfa_cell_block_s2', 'pfa_cell_block_s3',
    'broncho_bal_cytology', 'broncho_cbnaat', 'broncho_cs', 'broncho_gs', 'broncho_brush_cytology', 'broncho_biopsy_hpe'
];

$keys_list = implode(', ', $schema_keys);

$prompt = "You are a professional AI OCR assistant for a medical department portal.
Analyze the uploaded medical investigation findings document (handwritten or printed, image or PDF scan).
Extract all relevant metrics and details, mapping them to the keys listed below.
Return a single JSON object containing only the extracted key-value pairs. 
- For 'scheme_card' and 'scheme_preauth', check if selected/checked and return JSON boolean true or false.
- For all other keys, return string values.
- If a value is missing or not legible, use \"\" (empty string).
- Do NOT wrap the JSON in markdown code blocks (like ```json), return ONLY the raw JSON text.

Keys to extract:
$keys_list";

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt],
                [
                    'inlineData' => [
                        'mimeType' => $mime_type,
                        'data'     => $base64_data
                    ]
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json'
    ]
];

// Models to try in order of preference (auto-fallback if one fails)
$models_to_try = [
    'gemini-2.5-flash',
    'gemini-2.0-flash',
    'gemini-2.0-flash-lite',
    'gemini-2.5-flash-lite',
    'gemini-3.5-flash',
];

/**
 * Helper: call Gemini API with one model, returns [http_code, response_body, curl_error]
 */
function call_gemini_model(string $model, array $payload, string $api_key): array {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);
    return [$http_code, $response, $error];
}

$last_error  = '';
$response    = '';
$http_code   = 0;

foreach ($models_to_try as $model) {
    [$http_code, $response, $curl_err] = call_gemini_model($model, $payload, GEMINI_API_KEY);

    if ($curl_err) {
        $last_error = "cURL error on model {$model}: {$curl_err}";
        continue; // try next model
    }

    if ($http_code === 200) {
        break; // success – stop trying models
    }

    // 404 = model not found for this account → try next
    // 429 = rate limit → try next model (different quota buckets)
    // 503 = server overload → try next model
    $res_tmp    = json_decode($response, true);
    $api_msg    = $res_tmp['error']['message'] ?? "HTTP {$http_code}";
    $last_error = "Model [{$model}] failed: {$api_msg}";
    continue;
}

if ($http_code !== 200) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'All Gemini models failed. Last error: ' . $last_error .
                     ' — Please verify your API key at https://aistudio.google.com/app/apikey'
    ]);
    exit;
}

$res_data      = json_decode($response, true);
$text_response = $res_data['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($text_response)) {
    echo json_encode(['status' => 'error', 'message' => 'No content returned from Gemini API. The document may be unreadable.']);
    exit;
}

$clean_text = trim($text_response);
// Strip markdown code fences if returned despite the prompt
if (strpos($clean_text, '```') === 0) {
    $clean_text = preg_replace('/^```(?:json)?\s*/i', '', $clean_text);
    $clean_text = preg_replace('/\s*```$/', '', $clean_text);
    $clean_text = trim($clean_text);
}

$extracted_json = json_decode($clean_text, true);

if ($extracted_json === null) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to parse JSON from Gemini response. The document may be unclear.',
        'raw'     => $text_response
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'data'   => $extracted_json
]);
