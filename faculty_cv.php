<?php
// faculty_cv.php - Professional Medical Curriculum Vitae / Faculty Bio-Data Generator
require_once __DIR__ . '/config/db.php';
check_auth();

require_once __DIR__ . '/src/Controllers/FacultyController.php';

$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
if (!$faculty_id) {
    die("Invalid Faculty ID.");
}

$facultyCtrl = new FacultyController();
$details = $facultyCtrl->getFacultyDetails($pdo, $faculty_id);

if (!$details || empty($details['faculty'])) {
    die("Faculty record not found.");
}

$fac = $details['faculty'];
$quals = $details['qualifications'];
$exps = $details['experiences'];
$docs = $details['documents'];
$totalExp = $details['total_experience'];

$facMobile = $fac['mobile_number'] ?? $fac['mobile'] ?? '';
$facOverview = $fac['overview_summary'] ?? $fac['overview'] ?? '';
$facSpecialties = $fac['specialties'] ?? $fac['specialities'] ?? '';
$facNmcCode = $fac['nmc_code'] ?? $fac['nmc_details'] ?? '';

$rawName = trim($fac['full_name']);
$displayName = preg_match('/^(dr\.|prof\.|dr\b|prof\b)/i', $rawName) ? $rawName : 'Dr. ' . $rawName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curriculum Vitae – Dr. <?= esc($fac['full_name']) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- html2pdf.js for Client-side Mobile & Desktop PDF Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        :root {
            --cv-primary: #7952CC;
            --cv-primary-dark: #56339A;
            --cv-accent: #7952CC;
            --cv-bg: #F8F6FF;
            --cv-text: #29233A;
            --cv-muted: #777187;
            --cv-border: #E7E0F5;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #0f172a;
            color: var(--cv-text);
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Bar */
        .cv-action-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
        }

        /* CV Document Canvas */
        .cv-container {
            max-width: 850px;
            width: calc(100% - 30px);
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            padding: 45px 50px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        /* Header Header Styling */
        .cv-header-banner {
            border-bottom: 3px double var(--cv-primary);
            padding-bottom: 25px;
            margin-bottom: 25px;
        }

        .institution-title {
            font-family: 'Outfit', sans-serif;
            color: var(--cv-primary-dark);
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .dept-subtitle {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--cv-accent);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .cv-main-title {
            display: inline-block;
            background: var(--cv-primary);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 20px;
            margin-top: 8px;
        }

        .doctor-photo-container {
            width: 140px;
            height: 170px;
            border-radius: 12px;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 14px rgba(15, 76, 129, 0.18);
            outline: 1px solid var(--cv-border);
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .doctor-photo {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: top center !important;
            display: block;
        }

        .doctor-photo-placeholder {
            width: 140px;
            height: 170px;
            border-radius: 12px;
            background: var(--cv-bg);
            border: 2px dashed var(--cv-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--cv-muted);
            font-weight: 700;
            font-size: 28px;
            flex-shrink: 0;
        }

        .doctor-name {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--cv-primary-dark);
            margin: 0;
            line-height: 1.2;
        }

        .doctor-designation {
            font-size: 16px;
            font-weight: 700;
            color: var(--cv-accent);
            margin-top: 4px;
        }

        /* Section Block Styling */
        .cv-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .cv-section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: var(--cv-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid var(--cv-border);
            padding-bottom: 6px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 20px;
            background: var(--cv-bg);
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid var(--cv-border);
        }

        .grid-item {
            font-size: 13px;
        }

        .grid-label {
            font-weight: 600;
            color: var(--cv-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .grid-value {
            font-weight: 700;
            color: var(--cv-text);
        }

        /* Tables */
        .cv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            margin-top: 8px;
        }

        .cv-table th {
            background-color: #f1f5f9;
            color: var(--cv-primary-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            padding: 10px 12px;
            border: 1px solid var(--cv-border);
            text-align: left;
        }

        .cv-table td {
            padding: 9px 12px;
            border: 1px solid var(--cv-border);
            color: var(--cv-text);
        }

        .cv-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .exp-highlight-bar {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12.5px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Specialty Pills */
        .specialty-tag {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            font-size: 11.5px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        /* Declaration & Signature Block */
        .declaration-box {
            font-size: 12px;
            color: var(--cv-muted);
            font-style: italic;
            border-top: 1px solid var(--cv-border);
            padding-top: 15px;
            margin-top: 30px;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            page-break-inside: avoid;
        }

        .sig-line {
            border-top: 1.5px solid var(--cv-text);
            width: 200px;
            text-align: center;
            padding-top: 4px;
            font-weight: 700;
            font-size: 12px;
            color: var(--cv-primary-dark);
        }

        /* PRINT STYLES */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .no-print, .cv-action-bar {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .cv-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .details-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px 20px !important;
            }

            .doctor-photo-container, .doctor-photo-placeholder {
                width: 140px !important;
                height: 170px !important;
            }

            .doctor-photo {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                object-position: top center !important;
            }

            .cv-table th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .cv-main-title {
                background: #0f4c81 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* MOBILE OPTIMIZATIONS */
        @media (max-width: 768px) {
            .cv-container {
                width: calc(100% - 24px);
                margin: 15px auto;
                padding: 20px 15px;
                box-sizing: border-box;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .doctor-photo-container, .doctor-photo-placeholder {
                width: 110px;
                height: 135px;
            }

            .doctor-photo {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                object-position: top center !important;
            }

            .doctor-name {
                font-size: 20px;
            }

            .institution-title {
                font-size: 16px;
            }

            .cv-action-bar {
                padding: 10px;
            }

            .cv-action-bar .btn {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

    <!-- TOP ACTION CONTROL BAR (HIDDEN IN PRINT / PDF) -->
        <div class="cv-action-bar no-print text-white d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="ph ph-file-pdf text-warning fs-3"></i>
            <div>
                <div class="fw-bold fs-6">Faculty Bio-Data & Curriculum Vitae</div>
                <div class="text-white-50 small"><?= esc($displayName) ?></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-success font-weight-bold d-flex align-items-center gap-1 shadow-sm px-3" onclick="downloadPDF()" id="dlPdfBtn">
                <i class="ph ph-download-simple fs-5"></i> <span>Download PDF File</span>
            </button>
            <button type="button" class="btn btn-outline-light font-weight-bold d-flex align-items-center gap-1 px-3" onclick="window.print()">
                <i class="ph ph-printer fs-5"></i> <span>Print / Save PDF</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger px-3" onclick="window.close()">
                <i class="ph ph-x fs-5"></i> Close
            </button>
        </div>
    </div>

    <!-- MAIN CV DOCUMENT CANVAS -->
    <div class="cv-container" id="cv-document">
        
        <!-- HEADER BANNER -->
        <div class="cv-header-banner">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="institution-title"><?= esc($fac['institution'] ?: 'GOVERNMENT MEDICAL COLLEGE & HOSPITAL') ?></div>
                    <div class="dept-subtitle"><?= esc($fac['department'] ?: 'DEPARTMENT OF ENT (OTORHINOLARYNGOLOGY)') ?></div>
                    <div class="cv-main-title">FACULTY CURRICULUM VITAE & BIO-DATA</div>
                </div>
                <div>
                    <?php if (!empty($fac['photo'])): ?>
                        <div class="doctor-photo-container">
                            <img src="static/uploads/faculty_photos/<?= esc($fac['photo']) ?>" alt="<?= esc($displayName) ?>" class="doctor-photo">
                        </div>
                    <?php else: ?>
                        <div class="doctor-photo-placeholder">
                            <?= strtoupper(substr(preg_replace('/^(dr\.|prof\.|dr\b|prof\b|\s+)/i', '', $fac['full_name']), 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DOCTOR NAME & DESIGNATION BUBBLE -->
        <div class="mb-4">
            <h1 class="doctor-name"><?= esc($displayName) ?></h1>
            <div class="doctor-designation"><?= esc($fac['designation']) ?></div>
            <div class="text-muted small mt-1">
                <i class="ph ph-buildings me-1"></i><?= esc($fac['department']) ?> | <?= esc($fac['institution']) ?>
            </div>
        </div>

        <!-- PERSONAL & PROFESSIONAL INFORMATION GRID -->
        <div class="cv-section">
            <div class="cv-section-title">
                <i class="ph ph-user-circle fs-5"></i> Personal & Professional Details
            </div>
            <div class="details-grid">
                <div class="grid-item">
                    <span class="grid-label">Full Name</span>
                    <span class="grid-value"><?= esc($displayName) ?></span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Medical Registration No</span>
                    <span class="grid-value"><?= esc($fac['medical_reg_no'] ?: 'N/A') ?></span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Date of Birth / Age</span>
                    <span class="grid-value">
                        <?= !empty($fac['dob']) ? date('d/m/Y', strtotime($fac['dob'])) : 'N/A' ?>
                        <?= $fac['age'] ? ' (' . $fac['age'] . ' Years)' : '' ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Gender / Nationality</span>
                    <span class="grid-value"><?= esc($fac['gender'] ?: 'N/A') ?> | <?= esc($fac['nationality'] ?: 'Indian') ?></span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Contact Number</span>
                    <span class="grid-value"><?= esc($facMobile ?: 'N/A') ?></span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Email Address</span>
                    <span class="grid-value"><?= esc($fac['email'] ?: 'N/A') ?></span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Date of Joining (DOJ)</span>
                    <span class="grid-value"><?= !empty($fac['doj']) ? date('d/m/Y', strtotime($fac['doj'])) : 'N/A' ?></span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">Employment Status / NMC Code</span>
                    <span class="grid-value"><?= esc($fac['employment_status'] ?: 'Regular') ?> <?= !empty($facNmcCode) ? ' (NMC: ' . esc($facNmcCode) . ')' : '' ?></span>
                </div>
                <?php if (!empty($fac['address'])): ?>
                    <div class="grid-item style="grid-column: span 2;">
                        <span class="grid-label">Residential / Communication Address</span>
                        <span class="grid-value"><?= esc($fac['address']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 1. EDUCATIONAL QUALIFICATIONS -->
        <div class="cv-section">
            <div class="cv-section-title">
                <i class="ph ph-graduation-cap fs-5"></i> 1. Educational Qualifications
            </div>
            <?php if (empty($quals)): ?>
                <div class="p-3 bg-light rounded text-muted small italic text-center border">
                    No formal educational qualifications listed on record.
                </div>
            <?php else: ?>
                <table class="cv-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">S.NO</th>
                            <th>QUALIFICATION / DEGREE</th>
                            <th>SPECIALITY / SUBJECT</th>
                            <th>UNIVERSITY / INSTITUTION</th>
                            <th style="width: 130px; text-align: center;">YEAR OF PASSING</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quals as $idx => $q): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--cv-muted);"><?= $idx + 1 ?></td>
                                <td style="font-weight: 700; color: var(--cv-primary-dark);"><?= esc($q['qualification']) ?></td>
                                <td><?= esc($q['speciality'] ?: '-') ?></td>
                                <td><?= esc($q['university'] ?: '-') ?></td>
                                <td style="text-align: center; font-weight: 700;"><?= esc($q['passing_year'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- 2. TEACHING EXPERIENCE -->
        <div class="cv-section">
            <div class="cv-section-title">
                <i class="ph ph-briefcase fs-5"></i> 2. Teaching & Clinical Experience
            </div>
            <?php if (empty($exps)): ?>
                <div class="p-3 bg-light rounded text-muted small italic text-center border">
                    No teaching experience entries recorded.
                </div>
            <?php else: ?>
                <table class="cv-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">S.NO</th>
                            <th>DESIGNATION</th>
                            <th>DEPARTMENT</th>
                            <th>INSTITUTION / HOSPITAL</th>
                            <th style="width: 170px;">PERIOD</th>
                            <th style="width: 110px; text-align: center;">DURATION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exps as $idx => $exp): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--cv-muted);"><?= $idx + 1 ?></td>
                                <td style="font-weight: 700; color: var(--cv-primary-dark);"><?= esc($exp['designation']) ?></td>
                                <td><?= esc($exp['department'] ?: '-') ?></td>
                                <td><?= esc($exp['institution'] ?: '-') ?></td>
                                <td style="font-size: 11.5px;">
                                    <?= date('d M Y', strtotime($exp['start_date'])) ?> – 
                                    <?= !empty($exp['currently_working']) ? 'Present' : (!empty($exp['end_date']) ? date('d M Y', strtotime($exp['end_date'])) : 'N/A') ?>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: var(--cv-accent);"><?= esc($exp['duration']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Total Experience Summary Bar -->
            <div class="exp-highlight-bar">
                <span>Total Calculated Teaching Experience (Non-Overlapping):</span>
                <span class="fs-6"><?= $totalExp ?></span>
            </div>
        </div>

        <!-- 3. OVERVIEW & SUMMARY -->
        <?php if (!empty($facOverview)): ?>
            <div class="cv-section">
                <div class="cv-section-title">
                    <i class="ph ph-article fs-5"></i> 3. Professional Profile & Overview
                </div>
                <p style="font-size: 13px; line-height: 1.6; color: var(--cv-text); margin: 0;">
                    <?= nl2br(esc($facOverview)) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- 4. SPECIALITIES & CLINICAL EXPERTISE -->
        <?php if (!empty($facSpecialties)): 
            $specs = array_filter(array_map('trim', explode(',', $facSpecialties)));
            if (!empty($specs)):
        ?>
            <div class="cv-section">
                <div class="cv-section-title">
                    <i class="ph ph-first-aid fs-5"></i> 4. Specialities & Clinical Expertise
                </div>
                <div>
                    <?php foreach ($specs as $s): ?>
                        <span class="specialty-tag"><i class="ph ph-check-circle text-primary me-1"></i><?= esc($s) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; endif; ?>

        <!-- 5. KEY ACHIEVEMENTS & HONORS -->
        <?php if (!empty($fac['achievements'])): ?>
            <div class="cv-section">
                <div class="cv-section-title">
                    <i class="ph ph-trophy fs-5"></i> 5. Key Achievements & Honors
                </div>
                <p style="font-size: 13px; line-height: 1.6; color: var(--cv-text); margin: 0;">
                    <?= nl2br(esc($fac['achievements'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- 6. VERIFIED DOCUMENTS RECORD -->
        <?php if (!empty($docs)): ?>
            <div class="cv-section">
                <div class="cv-section-title">
                    <i class="ph ph-file-text fs-5"></i> 6. Supporting Documents & Certificates on File
                </div>
                <table class="cv-table">
                    <thead>
                        <tr>
                            <th style="width: 55px; text-align: center;">S.NO</th>
                            <th>DOCUMENT TITLE</th>
                            <th>CATEGORY</th>
                            <th>ORIGINAL FILENAME</th>
                            <th style="width: 140px; text-align: center;">UPLOAD DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($docs as $idx => $doc): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: var(--cv-muted);"><?= $idx + 1 ?></td>
                                <td style="font-weight: 700; color: var(--cv-text);"><?= esc($doc['title']) ?></td>
                                <td><?= esc($doc['category'] ?: '-') ?></td>
                                <td style="font-family: monospace; font-size: 11px;"><?= esc($doc['original_name']) ?></td>
                                <td style="text-align: center; font-size: 11.5px;"><?= date('d/m/Y', strtotime($doc['uploaded_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- DECLARATION & SIGNATURE BLOCK -->
        <div class="declaration-box">
            "I hereby declare that all the information and particulars provided in this Curriculum Vitae are true, complete, and accurate to the best of my knowledge and belief."
        </div>

        <div class="signature-section">
            <div>
                <div class="small fw-semibold text-muted">Date: <strong><?= date('d/m/Y') ?></strong></div>
                <div class="small fw-semibold text-muted">Place: <strong>Government Medical College</strong></div>
            </div>
            <div class="text-center">
                <div style="height: 35px;"></div>
                <div class="sig-line">
                    <?= esc($displayName) ?>
                </div>
                <div class="small text-muted fw-semibold mt-1"><?= esc($fac['designation']) ?></div>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT FOR PDF GENERATION -->
    <script>
        function downloadPDF() {
            const btn = document.getElementById('dlPdfBtn');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating PDF...';

            // Create a hidden 800px wide iframe to isolate desktop A4 rendering
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.top = '0';
            iframe.style.left = '0';
            iframe.style.width = '800px';
            iframe.style.height = '1130px';
            iframe.style.border = 'none';
            iframe.style.opacity = '0';
            iframe.style.pointerEvents = 'none';
            iframe.style.zIndex = '-99999';

            document.body.appendChild(iframe);

            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            // Collect all stylesheets from the main document
            let stylesHtml = '';
            document.querySelectorAll('style, link[rel="stylesheet"]').forEach(el => {
                stylesHtml += el.outerHTML;
            });

            // Extra CSS rules to guarantee a 100% full-width, centered A4 canvas inside the iframe
            const pdfFixStyle = `
                <style>
                    html, body {
                        background: #ffffff !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 800px !important;
                        box-sizing: border-box !important;
                    }
                    .cv-container {
                        width: 800px !important;
                        max-width: 800px !important;
                        margin: 0 auto !important;
                        padding: 30px 40px !important;
                        box-shadow: none !important;
                        border-radius: 0 !important;
                        box-sizing: border-box !important;
                    }
                    .details-grid {
                        display: grid !important;
                        grid-template-columns: repeat(2, 1fr) !important;
                        gap: 10px 20px !important;
                    }
                    .doctor-photo-container, .doctor-photo-placeholder {
                        width: 140px !important;
                        height: 170px !important;
                    }
                    .doctor-photo {
                        width: 100% !important;
                        height: 100% !important;
                        object-fit: cover !important;
                        object-position: top center !important;
                    }
                    .doctor-name {
                        font-size: 26px !important;
                    }
                    .institution-title {
                        font-size: 20px !important;
                    }
                    .no-print, .cv-action-bar {
                        display: none !important;
                    }
                </style>
            `;

            const cvHtml = document.getElementById('cv-document').outerHTML;

            iframeDoc.open();
            iframeDoc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    ${stylesHtml}
                    ${pdfFixStyle}
                </head>
                <body>
                    ${cvHtml}
                </body>
                </html>
            `);
            iframeDoc.close();

            // Allow iframe DOM, styles, and cached images to settle
            setTimeout(() => {
                const targetElement = iframeDoc.getElementById('cv-document') || iframeDoc.body;

                const opt = {
                    margin:       [8, 8, 8, 8],
                    filename:     'Curriculum_Vitae_Dr_<?= preg_replace("/[^A-Za-z0-9_]/", "_", $fac["full_name"]) ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, logging: false, scrollX: 0, scrollY: 0, width: 800, windowWidth: 800 },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
                };

                html2pdf().set(opt).from(targetElement).save().then(() => {
                    if (iframe && iframe.parentNode) {
                        iframe.parentNode.removeChild(iframe);
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }).catch(err => {
                    console.error('PDF generation error:', err);
                    if (iframe && iframe.parentNode) {
                        iframe.parentNode.removeChild(iframe);
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    alert('PDF generation encountered an issue. Opening print view.');
                    window.print();
                });
            }, 300);
        }
    </script>
</body>
</html>
