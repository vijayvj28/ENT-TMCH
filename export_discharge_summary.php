<?php
// export_discharge_summary.php
require_once __DIR__ . '/config/db.php';
check_auth();

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) {
    die("Patient ID required.");
}

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient record not found.");
}

// Helper to escape HTML safely
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Decode procedures if stored as JSON
$procedures_display = [];
if (!empty($patient['procedures'])) {
    $proc_data = json_decode($patient['procedures'], true);
    if (is_array($proc_data)) {
        foreach ($proc_data as $key => $val) {
            if (is_array($val)) {
                $pName = $val['name'] ?? $key;
                $pDetails = $val['details'] ?? '';
                $procedures_display[] = $pName . ($pDetails ? " ($pDetails)" : '');
            } else {
                $procedures_display[] = (string)$val;
            }
        }
    } else {
        $procedures_display[] = $patient['procedures'];
    }
}

// Decode special management if stored as JSON
$sm_display = [];
if (!empty($patient['special_management'])) {
    $sm_data = json_decode($patient['special_management'], true);
    if (is_array($sm_data)) {
        foreach ($sm_data as $key => $val) {
            if (is_array($val)) {
                $smName = $val['name'] ?? $key;
                $smDetails = $val['details'] ?? '';
                $sm_display[] = $smName . ($smDetails ? " ($smDetails)" : '');
            } else {
                $sm_display[] = (string)$val;
            }
        }
    } else {
        $sm_display[] = $patient['special_management'];
    }
}

// Decode investigations JSON if present
$inv_data = [];
if (!empty($patient['investigations'])) {
    $decoded = json_decode($patient['investigations'], true);
    if (is_array($decoded)) {
        $inv_data = $decoded;
    }
}

$filename = "Discharge_Summary_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $patient['name']) . ".doc";

header("Content-Type: application/msword");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: false");
header("Pragma: no-cache");
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="utf-8">
<title>Discharge Summary - <?= h($patient['name']) ?></title>
<!--[if gte mso 9]>
<xml>
 <w:WordDocument>
  <w:View>Print</w:View>
  <w:Zoom>100</w:Zoom>
  <w:DoNotOptimizeForCustomXerox>true</w:DoNotOptimizeForCustomXerox>
 </w:WordDocument>
</xml>
<![endif]-->
<style>
    @page {
        size: 8.5in 11in;
        margin: 0.6in 0.6in 0.6in 0.6in;
        mso-header-margin: 0.4in;
        mso-footer-margin: 0.4in;
    }
    body {
        font-family: 'Calibri', 'Arial', sans-serif;
        font-size: 10.5pt;
        line-height: 1.3;
        color: #111111;
    }
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    .header-title {
        font-size: 15pt;
        font-weight: bold;
        color: #56339A;
        text-align: center;
        text-transform: uppercase;
    }
    .header-subtitle {
        font-size: 12pt;
        font-weight: bold;
        color: #29233A;
        text-align: center;
        margin-top: 2px;
    }
    .doc-badge {
        font-size: 13pt;
        font-weight: bold;
        color: #ffffff;
        background-color: #56339A;
        text-align: center;
        padding: 5px;
        margin-top: 6px;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .section-title {
        font-size: 11pt;
        font-weight: bold;
        color: #56339A;
        background-color: #F0EAFE;
        padding: 4px 8px;
        margin-top: 10px;
        margin-bottom: 6px;
        border-left: 4px solid #7952CC;
    }
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    table.data-table td, table.data-table th {
        border: 1px solid #D5CAEA;
        padding: 5px 7px;
        font-size: 10pt;
        vertical-align: top;
    }
    table.data-table th {
        background-color: #F8F6FF;
        font-weight: bold;
        color: #56339A;
        text-align: left;
    }
    .label-col {
        font-weight: bold;
        color: #29233A;
        background-color: #FAF8FF;
        width: 22%;
    }
    .val-col {
        width: 28%;
    }
    .page-break {
        page-break-before: always;
        mso-break-type: page-break;
    }
    .footer-sig-table {
        width: 100%;
        margin-top: 35px;
        border-collapse: collapse;
    }
    .footer-sig-table td {
        border: none;
        padding: 15px 10px 0 10px;
        vertical-align: bottom;
    }
    .bullet-list {
        margin: 0;
        padding-left: 18px;
    }
    .bullet-list li {
        margin-bottom: 3px;
    }
</style>
</head>
<body>

<!-- PAGE 1 -->
<table class="header-table">
    <tr>
        <td style="border:none; text-align:center;">
            <div class="header-title">Department of ENT (Otorhinolaryngology)</div>
            <div class="header-subtitle">Thanjavur Medical College & Hospital, Thanjavur</div>
        </td>
    </tr>
</table>

<div class="doc-badge">PATIENT DISCHARGE SUMMARY</div>

<!-- Patient Demographics Section -->
<div class="section-title">1. PATIENT DEMOGRAPHIC DETAILS</div>
<table class="data-table">
    <tr>
        <td class="label-col">Patient Name:</td>
        <td class="val-col"><strong><?= h($patient['name']) ?></strong></td>
        <td class="label-col">Age / Sex:</td>
        <td class="val-col"><?= h($patient['age_sex']) ?></td>
    </tr>
    <tr>
        <td class="label-col">IP Number:</td>
        <td class="val-col"><strong><?= h($patient['ip_number']) ?></strong></td>
        <td class="label-col">ABHA Number:</td>
        <td class="val-col"><strong><?= h($patient['abha_number'] ?: '-') ?></strong></td>
    </tr>
    <?php if (!empty($patient['cochlear_case_id'])): ?>
    <tr>
        <td class="label-col" style="color: #56339A;">Cochlear Case ID:</td>
        <td colspan="3" class="val-col"><strong style="color: #56339A;"><?= h($patient['cochlear_case_id']) ?></strong></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="label-col">Mobile Number:</td>
        <td class="val-col"><?= h($patient['mobile_number']) ?></td>
        <td class="label-col">Place / Address:</td>
        <td class="val-col"><?= h($patient['place']) ?></td>
    </tr>
    <tr>
        <td class="label-col">Date of Admission:</td>
        <td class="val-col"><?= h($patient['doa'] ? date('d-M-Y', strtotime($patient['doa'])) : '-') ?></td>
        <td class="label-col">Date of Discharge:</td>
        <td class="val-col"><?= h($patient['dod'] ? date('d-M-Y', strtotime($patient['dod'])) : '-') ?></td>
    </tr>
    <?php if (!empty($patient['transfer_in_date'])): ?>
    <tr>
        <td class="label-col">Transfer In Date:</td>
        <td colspan="3" class="val-col"><?= h(date('d-M-Y', strtotime($patient['transfer_in_date']))) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="label-col">Place / Address:</td>
        <td class="val-col"><?= h($patient['place']) ?></td>
        <td class="label-col">Discharge Status:</td>
        <td class="val-col">
            <span style="font-weight:bold; color: <?= ($patient['patient_status'] === 'Death') ? '#c0392b' : (($patient['patient_status'] === 'Discharged') ? '#27ae60' : '#2980b9') ?>;">
                <?= h($patient['patient_status'] ?: 'Discharged') ?>
            </span>
        </td>
    </tr>
    <?php if (!empty($patient['cause_of_death']) || $patient['patient_status'] === 'Death'): ?>
    <tr>
        <td class="label-col" style="color:#c0392b;">Cause of Death:</td>
        <td colspan="3" style="color:#c0392b; font-weight:bold;"><?= h($patient['cause_of_death'] ?: '-') ?></td>
    </tr>
    <?php endif; ?>
</table>

<!-- Diagnosis & Clinical Summary -->
<div class="section-title">2. DIAGNOSIS & CLINICAL PRESENTATION</div>
<table class="data-table">
    <tr>
        <td class="label-col">Primary Diagnosis:</td>
        <td colspan="3" style="font-size: 11pt; font-weight: bold; color: #003366;"><?= h($patient['main_diagnosis'] ?: $patient['diagnosis'] ?: '-') ?></td>
    </tr>
    <?php if (!empty($patient['diagnosis']) && $patient['diagnosis'] !== $patient['main_diagnosis']): ?>
    <tr>
        <td class="label-col">Secondary Diagnosis:</td>
        <td colspan="3"><?= h($patient['diagnosis']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($inv_data['vitals']) || !empty($inv_data['symptoms'])): ?>
    <tr>
        <td class="label-col">Admission Vitals:</td>
        <td colspan="3">
            <?php 
            $v = $inv_data['vitals'] ?? [];
            $v_str = [];
            if (!empty($v['pulse'])) $v_str[] = "Pulse: {$v['pulse']} bpm";
            if (!empty($v['bp'])) $v_str[] = "BP: {$v['bp']} mmHg";
            if (!empty($v['spo2'])) $v_str[] = "SpO2: {$v['spo2']}%";
            if (!empty($v['temp'])) $v_str[] = "Temp: {$v['temp']}";
            if (!empty($v['rr'])) $v_str[] = "RR: {$v['rr']} /min";
            echo !empty($v_str) ? implode(" | ", $v_str) : "Vitals stable on admission.";
            ?>
        </td>
    </tr>
    <?php endif; ?>
</table>

<!-- Treatment & Procedures Provided -->
<div class="section-title">3. TREATMENT & PROCEDURES PROVIDED</div>
<table class="data-table">
    <tr>
        <td class="label-col">Medical Management / Drugs:</td>
        <td colspan="3"><?= nl2br(h($patient['treatment'] ?: 'Standard medical management administered as per department protocol.')) ?></td>
    </tr>
    <?php if (!empty($procedures_display)): ?>
    <tr>
        <td class="label-col">Procedures Performed:</td>
        <td colspan="3">
            <ul class="bullet-list">
                <?php foreach ($procedures_display as $pItem): ?>
                <li><?= h($pItem) ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($sm_display)): ?>
    <tr>
        <td class="label-col">Special Management:</td>
        <td colspan="3">
            <ul class="bullet-list">
                <?php foreach ($sm_display as $smItem): ?>
                <li><?= h($smItem) ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
    </tr>
    <?php endif; ?>
</table>

<!-- PAGE BREAK FOR EXACT 2-PAGE LAYOUT -->
<br style="page-break-before:always;" />
<div class="page-break"></div>

<!-- PAGE 2 -->
<table class="header-table">
    <tr>
        <td style="border:none; text-align:center;">
            <div class="header-title">Department of ENT (Otorhinolaryngology)</div>
            <div class="header-subtitle">Thanjavur Medical College & Hospital, Thanjavur</div>
        </td>
    </tr>
</table>

<div class="section-title">4. LABORATORY & DIAGNOSTIC INVESTIGATIONS</div>
<table class="data-table">
    <?php 
    $lab = $inv_data['lab'] ?? [];
    $abg = $inv_data['abg'] ?? [];
    ?>
    <tr>
        <th colspan="4">A. Routine Laboratory Findings</th>
    </tr>
    <tr>
        <td class="label-col">Hemoglobin (Hb):</td>
        <td class="val-col"><?= h($lab['hb'] ?? '-') ?></td>
        <td class="label-col">Total WBC Count:</td>
        <td class="val-col"><?= h($lab['wbc'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="label-col">Platelets:</td>
        <td class="val-col"><?= h($lab['platelets'] ?? '-') ?></td>
        <td class="label-col">Blood Urea / Creatinine:</td>
        <td class="val-col"><?= h(($lab['urea'] ?? '-') . ' / ' . ($lab['creatinine'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label-col">Sputum AFB / CBNAAT:</td>
        <td colspan="3"><?= h(($lab['afb'] ?? '-') . ' / ' . ($lab['cbnaat'] ?? '-')) ?></td>
    </tr>

    <?php if (!empty($abg)): ?>
    <tr>
        <th colspan="4">B. Arterial Blood Gas (ABG) Analysis</th>
    </tr>
    <tr>
        <td class="label-col">pH:</td>
        <td class="val-col"><?= h($abg['ph'] ?? '-') ?></td>
        <td class="label-col">pCO2:</td>
        <td class="val-col"><?= h($abg['pco2'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="label-col">pO2:</td>
        <td class="val-col"><?= h($abg['po2'] ?? '-') ?></td>
        <td class="label-col">HCO3 / SaO2:</td>
        <td class="val-col"><?= h(($abg['hco3'] ?? '-') . ' / ' . ($abg['sao2'] ?? '-')) ?></td>
    </tr>
    <?php endif; ?>

    <tr>
        <th colspan="4">C. Radiology, Audiometry & Diagnostic Reports</th>
    </tr>
    <tr>
        <td class="label-col">X-Ray (PNS / Mastoid / Neck):</td>
        <td colspan="3"><?= h($patient['xray_notes'] ?: ($inv_data['radiology']['cxr_notes'] ?? 'No acute bony lesion / findings recorded.')) ?></td>
    </tr>
    <tr>
        <td class="label-col">CT Scan (PNS / Temporal Bone):</td>
        <td colspan="3"><?= h($patient['ct_notes'] ?: ($inv_data['radiology']['ct_notes'] ?? 'Not performed / Normal.')) ?></td>
    </tr>
    <?php if (!empty($patient['spirometry_notes'])): ?>
    <tr>
        <td class="label-col">Audiometry (PTA / Impedance):</td>
        <td colspan="3"><?= h($patient['spirometry_notes']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($patient['echo_notes'])): ?>
    <tr>
        <td class="label-col">Endoscopy (DNE / VLS / Otoscopy):</td>
        <td colspan="3"><?= h($patient['echo_notes']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<!-- Condition at Discharge & Follow-up Advice -->
<div class="section-title">5. CONDITION AT DISCHARGE & ADVICE</div>
<table class="data-table">
    <tr>
        <td class="label-col">Condition at Discharge:</td>
        <td colspan="3">Hemodynamically stable, afebrile, wound healthy, surgical pack removed (if applicable), no active bleeding/epistaxis, airway clear.</td>
    </tr>
    <tr>
        <td class="label-col">Discharge Advice & Medications:</td>
        <td colspan="3">
            <ul class="bullet-list">
                <li>Continue prescribed oral antibiotics, analgesics and topical ear/nasal medications regularly.</li>
                <li>Strict ear precautions: Keep operated ear completely dry; prevent water ingress during head bath.</li>
                <li>Avoid forceful nose blowing, vigorous sneezing, and heavy weight lifting.</li>
                <li>Perform warm saline gargles / steam inhalation and nasal douching as demonstrated.</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="label-col">Follow-up Schedule:</td>
        <td colspan="3">Review in ENT Out-Patient Department (OPD) in <strong>1 to 2 weeks</strong> for suture/pack review, or immediately in casualty if severe pain, bleeding, or dizziness occurs.</td>
    </tr>
</table>

<!-- Signature Section -->
<table class="footer-sig-table">
    <tr>
        <td style="width: 50%; text-align: left;">
            <p>____________________________________</p>
            <p><strong>Assistant Professor / Resident Medical Officer</strong><br>Dept of ENT (Otorhinolaryngology)<br>Thanjavur Medical College</p>
        </td>
        <td style="width: 50%; text-align: right;">
            <p>____________________________________</p>
            <p><strong>Professor & Head of Department</strong><br>Dept of ENT (Otorhinolaryngology)<br>Thanjavur Medical College</p>
        </td>
    </tr>
</table>

</body>
</html>
