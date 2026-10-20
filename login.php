<?php
// login.php
// Government of Tamil Nadu - Department of ENT (Otorhinolaryngology)
// Secure Authentication Entry Point - CMCHIS Style

require_once __DIR__ . '/config/db.php';

// Redirect to dashboard if already authenticated
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error_msg = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_msg = 'Please enter both your Username/Employee ID and Password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, d.name AS department_name 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.username = :username
            LIMIT 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'inactive') {
                $error_msg = 'Your account has been deactivated. Please contact the department administrator.';
            } else {
                session_regenerate_id(true);
                
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];
                $_SESSION['department_name'] = $user['department_name'] ?? 'ENT';
                
                $_SESSION['flash_message'] = [
                    'type' => 'success', 
                    'text' => 'Welcome back, ' . esc($user['username']) . '!'
                ];
                
                header("Location: index.php");
                exit;
            }
        } else {
            $error_msg = 'Invalid username or password. Please check your credentials and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENT Department - Clinical Portal Login | Government of Tamil Nadu</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ENT Department Theme CSS -->
    <link rel="stylesheet" href="static/css/ent-theme.css?v=<?= time() ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="static/css/style.css?v=<?= time() ?>">
    <!-- CMCHIS Government Theme CSS -->
    <link rel="stylesheet" href="static/css/cmchis-theme.css?v=<?= time() ?>">
    <style>
        :root {
            --cmchis-navy: #0F3562;
            --cmchis-navy-dark: #0A2240;
            --cmchis-navy-light: #164780;
            --cmchis-tn-green: #007A3D;
            --cmchis-gold: #E5A823;
            --cmchis-bg: #F4F6FA;
            --cmchis-border: #DDE3ED;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--cmchis-bg);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1E293B;
            overflow-x: hidden;
        }

        .login-page-layout {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: radial-gradient(circle at 10% 15%, rgba(15, 53, 98, 0.04) 0%, rgba(244, 246, 250, 1) 70%), #F4F6FA;
        }

        /* -------------------------------------------------------------
           1. TOP HEADER (GOVERNMENT OF TAMIL NADU & ENT DEPARTMENT)
           ------------------------------------------------------------- */
        .login-header-bar {
            background: #FFFFFF;
            border-bottom: 3px solid var(--cmchis-navy);
            box-shadow: 0 2px 10px rgba(10, 34, 64, 0.06);
            padding: 0.65rem 1.25rem;
            position: relative;
            z-index: 10;
        }

        .header-inner {
            max-width: 1320px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .header-emblem-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .header-emblem-group img {
            height: 48px;
            width: 48px;
            object-fit: contain;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.08));
        }

        .header-center-titles {
            text-align: center;
            flex-grow: 1;
            padding: 0 0.5rem;
        }

        .hdr-tn-gov {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--cmchis-tn-green);
            line-height: 1.2;
        }

        .hdr-dept-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--cmchis-navy);
            line-height: 1.25;
            margin: 0.1rem 0;
        }

        .hdr-college-sub {
            font-size: 0.8rem;
            color: #475569;
            font-weight: 500;
            line-height: 1.2;
        }

        /* -------------------------------------------------------------
           2. MAIN CENTER SECTION (LEFT: SLIDESHOW, RIGHT: LOGIN FORM)
           ------------------------------------------------------------- */
        .login-main-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem 1rem;
            width: 100%;
        }

        .login-grid-container {
            width: 100%;
            max-width: 1260px;
            margin: 0 auto;
        }

        /* Left Side: Header & Text */
        .showcase-heading-wrap {
            margin-bottom: 0.85rem;
        }

        .dept-tag-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.75rem;
            background: rgba(15, 53, 98, 0.08);
            border: 1px solid rgba(15, 53, 98, 0.18);
            border-radius: 9999px;
            color: var(--cmchis-navy);
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .showcase-main-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--cmchis-navy);
            margin-top: 0.35rem;
            margin-bottom: 0.2rem;
            line-height: 1.25;
        }

        .showcase-sub-desc {
            font-size: 0.82rem;
            color: #475569;
            margin-bottom: 0;
            line-height: 1.45;
        }

        /* -------------------------------------------------------------
           3. SLIDESHOW CONTAINER (CONSTRAINED TO ORIGINAL RECTANGULAR AREA)
              - Pure photos only
              - No black or dark navy background
              - No outer card frame or padding
              - No numbering (1/4), captions, dots, or arrows
              - Images fill container with 100% width, 100% height, object-fit: cover
           ------------------------------------------------------------- */
        .clean-slideshow-container {
            position: relative;
            width: 100%;
            height: 340px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(10, 34, 64, 0.08);
            border: 1px solid #CBD5E1;
            background: transparent;
            margin-bottom: 0.75rem;
        }

        @media (max-width: 991.98px) {
            .clean-slideshow-container {
                height: 280px;
            }
        }

        @media (max-width: 575.98px) {
            .clean-slideshow-container {
                height: 220px;
            }
        }

        .slideshow-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            visibility: hidden;
            transition: opacity 1.2s ease-in-out, visibility 1.2s ease-in-out;
            z-index: 1;
        }

        .slideshow-slide.active {
            opacity: 1;
            visibility: visible;
            z-index: 2;
        }

        .slideshow-slide.prev-active {
            opacity: 1;
            visibility: visible;
            z-index: 1;
        }

        .slideshow-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* Compact Feature Chips below Slideshow */
        .compact-chips-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 0.65rem;
        }

        @media (max-width: 575.98px) {
            .compact-chips-row {
                grid-template-columns: 1fr;
            }
        }

        .compact-chip-item {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 0.45rem 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .compact-chip-icon {
            font-size: 1rem;
            color: var(--cmchis-navy);
            flex-shrink: 0;
        }

        .compact-chip-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #1E293B;
            line-height: 1.1;
        }

        .compact-chip-sub {
            font-size: 0.65rem;
            color: #64748B;
            line-height: 1.1;
        }

        /* -------------------------------------------------------------
           4. RIGHT-SIDE LOGIN CARD
           ------------------------------------------------------------- */
        .cmchis-login-box {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-top: 4px solid var(--cmchis-navy);
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(10, 34, 64, 0.08), 0 1px 4px rgba(0, 0, 0, 0.03);
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
            padding: 1.6rem 1.6rem;
            position: relative;
        }

        .login-card-header {
            text-align: center;
            margin-bottom: 1.1rem;
        }

        .login-portal-tag {
            display: inline-block;
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--cmchis-navy);
            background: #EEF2F7;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .login-title-text {
            color: var(--cmchis-navy);
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            line-height: 1.2;
            margin: 0 0 0.25rem 0;
        }

        .login-sub-text {
            color: #64748B;
            font-size: 0.8rem;
            line-height: 1.3;
            margin: 0;
        }

        /* Form Controls */
        .input-box-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            font-size: 1.15rem;
            pointer-events: none;
            z-index: 4;
            display: flex;
            align-items: center;
        }

        .input-control-styled {
            height: 44px;
            padding-left: 2.6rem;
            padding-right: 2.6rem;
            font-size: 0.9rem;
            border-radius: 8px;
            border: 1.5px solid #CBD5E1;
            color: #1E293B;
            background-color: #FFFFFF;
            width: 100%;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .input-control-styled:focus {
            border-color: var(--cmchis-navy);
            box-shadow: 0 0 0 3px rgba(15, 53, 98, 0.14);
            outline: none;
        }

        .input-control-styled::placeholder {
            color: #94A3B8;
            font-size: 0.84rem;
        }

        .pwd-toggle-btn {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748B;
            font-size: 1.15rem;
            cursor: pointer;
            padding: 0.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            z-index: 4;
        }

        .pwd-toggle-btn:hover {
            color: var(--cmchis-navy);
            background: #F1F5F9;
        }

        .login-action-btn {
            background: linear-gradient(180deg, #124074 0%, #0c2d54 100%);
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            height: 44px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(12, 45, 84, 0.22);
            cursor: pointer;
        }

        .login-action-btn:hover {
            background: linear-gradient(180deg, #185292 0%, #0e3768 100%);
            box-shadow: 0 6px 16px rgba(12, 45, 84, 0.32);
            color: #FFFFFF;
            transform: translateY(-1px);
        }

        .login-action-btn:active {
            transform: translateY(0);
        }

        .login-card-foot-note {
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #E2E8F0;
            text-align: center;
        }

        .ssl-seal-text {
            color: #334155;
            font-size: 0.74rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
        }

        .gov-sub-note {
            font-size: 0.68rem;
            color: #64748B;
            margin-top: 0.2rem;
        }

        /* -------------------------------------------------------------
           5. MINIMAL 1-LINE FOOTER
           ------------------------------------------------------------- */
        .login-bottom-strip {
            background: #081629;
            color: #94A3B8;
            font-size: 0.74rem;
            padding: 0.55rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            flex-shrink: 0;
        }

        .login-bottom-strip strong {
            color: #FFFFFF;
        }
    </style>
</head>
<body>

    <div class="login-page-layout">

        <!-- =========================================================
             1. OFFICIAL BRANDING HEADER (AT THE TOP)
             ========================================================= -->
        <header class="login-header-bar">
            <div class="header-inner">
                <!-- Left: TN Govt & TMC Emblems -->
                <div class="header-emblem-group">
                    <img src="static/images/tn_govt_logo.png" alt="TN Govt" title="Government of Tamil Nadu">
                    <img src="static/images/tmc_logo.png" alt="TMC Logo" title="Thanjavur Medical College &amp; Hospital">
                </div>

                <!-- Center Titles -->
                <div class="header-center-titles">
                    <div class="hdr-tn-gov">Government of Tamil Nadu</div>
                    <div class="hdr-dept-name">Department of ENT (Otorhinolaryngology)</div>
                    <div class="hdr-college-sub">Thanjavur Medical College &amp; Hospital &bull; Clinical &amp; Academic MedPortal</div>
                </div>

                <!-- Right: Department Seal & CMCHIS -->
                <div class="header-emblem-group">
                    <img src="static/images/ent_logo.png" alt="ENT Seal" title="Department of ENT">
                    <img src="static/images/cmchis_pmjay_logo.png" alt="CMCHIS PM-JAY" title="Chief Minister's Comprehensive Health Insurance Scheme" class="d-none d-sm-block">
                </div>
            </div>
        </header>

        <!-- =========================================================
             2. MAIN CONTENT (TWO-COLUMN: LEFT SLIDESHOW, RIGHT LOGIN)
             ========================================================= -->
        <main class="login-main-section">
            <div class="login-grid-container">
                <div class="row g-3 g-xl-4 align-items-center">
                    
                    <!-- -------------------------------------------------
                         LEFT SIDE: Department Highlights & Clean Photos Slideshow
                         ------------------------------------------------- -->
                    <div class="col-12 col-lg-7 col-xl-7">
                        
                        <!-- Header Text -->
                        <div class="showcase-heading-wrap">
                            <div class="dept-tag-badge">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <span>Academic Excellence &bull; Surgical Milestones</span>
                            </div>
                            <h2 class="showcase-main-title">Department Clinical &amp; Academic Portal</h2>
                            <p class="showcase-sub-desc">
                                Official records of outpatient &amp; inpatient care, operative audit logs, postgraduate academic curriculum, and public health programs.
                            </p>
                        </div>

                        <!-- Slideshow Container: Constrained to Left Area Only, Photos Only -->
                        <div class="clean-slideshow-container" id="cleanSlideshowContainer">
                            <!-- Photo 1 -->
                            <div class="slideshow-slide active">
                                <img src="static/images/slideshow/slide1.jpg" alt="Sign Languages Day & NPPCD Event" class="slideshow-img">
                            </div>
                            <!-- Photo 2 -->
                            <div class="slideshow-slide">
                                <img src="static/images/slideshow/slide2.jpg" alt="Tan ENT State Conference" class="slideshow-img">
                            </div>
                            <!-- Photo 3 -->
                            <div class="slideshow-slide">
                                <img src="static/images/slideshow/slide3.jpg" alt="World Hearing Day 2025" class="slideshow-img">
                            </div>
                            <!-- Photo 4 -->
                            <div class="slideshow-slide">
                                <img src="static/images/slideshow/slide4.jpg" alt="AOI TPN Center Conference" class="slideshow-img">
                            </div>
                        </div>

                        <!-- Compact 3-Chip Highlights Row -->
                        <div class="compact-chips-row">
                            <div class="compact-chip-item">
                                <span class="compact-chip-icon text-primary">&#9877;</span>
                                <div>
                                    <div class="compact-chip-label">24/7 Trauma Care</div>
                                    <div class="compact-chip-sub">Emergency Otorhinolaryngology</div>
                                </div>
                            </div>

                            <div class="compact-chip-item">
                                <span class="compact-chip-icon text-success">&#10003;</span>
                                <div>
                                    <div class="compact-chip-label">MUSQAN &amp; BERA</div>
                                    <div class="compact-chip-sub">Neonatal Screening &amp; Audiology</div>
                                </div>
                            </div>

                            <div class="compact-chip-item">
                                <span class="compact-chip-icon text-warning">&#9733;</span>
                                <div>
                                    <div class="compact-chip-label">NMC Accredited</div>
                                    <div class="compact-chip-sub">MS-ENT Post-Graduate Center</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- -------------------------------------------------
                         RIGHT SIDE: Login Option Card
                         ------------------------------------------------- -->
                    <div class="col-12 col-lg-5 col-xl-5">
                        <div class="cmchis-login-box">
                            
                            <!-- Card Header -->
                            <div class="login-card-header">
                                <div class="login-portal-tag">Official Authentication</div>
                                <h3 class="login-title-text">Clinical &amp; Staff Login</h3>
                                <p class="login-sub-text">Enter credentials to access clinical records &amp; census.</p>
                            </div>

                            <!-- Error Banner -->
                            <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger py-2 px-3 rounded-2 small d-flex align-items-center gap-2 mb-3 border-danger" role="alert">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <div><?= esc($error_msg) ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Login Form -->
                            <form method="POST" action="login.php" autocomplete="on">
                                
                                <!-- Username / Employee ID -->
                                <div class="mb-3">
                                    <label for="usernameInput" class="form-label small fw-bold text-dark mb-1">
                                        Username / Employee ID <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-box-wrapper">
                                        <span class="input-box-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            class="input-control-styled" 
                                            id="usernameInput" 
                                            name="username" 
                                            placeholder="Enter your username (e.g. admin-ent or TM)" 
                                            value="<?= esc($_POST['username'] ?? '') ?>"
                                            required 
                                            autofocus
                                        >
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="passwordInput" class="form-label small fw-bold text-dark mb-0">
                                            Password <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="input-box-wrapper">
                                        <span class="input-box-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        </span>
                                        <input 
                                            type="password" 
                                            class="input-control-styled" 
                                            id="passwordInput" 
                                            name="password" 
                                            placeholder="Enter your password" 
                                            required
                                        >
                                        <button 
                                            type="button" 
                                            class="pwd-toggle-btn" 
                                            id="togglePasswordBtn" 
                                            aria-label="Toggle password visibility"
                                            onclick="togglePasswordVisibility()"
                                        >
                                            <svg id="toggleEyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Security Row -->
                                <div class="d-flex align-items-center justify-content-between mb-3 small">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMeCheck" checked>
                                        <label class="form-check-label text-muted" for="rememberMeCheck" style="font-size: 0.8rem;">
                                            Keep session active
                                        </label>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.74rem;">
                                        <span class="text-success fw-bold">&#10003;</span> Official System
                                    </span>
                                </div>

                                <!-- Sign In Button -->
                                <button type="submit" class="login-action-btn">
                                    <span>Sign In to Portal</span>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </button>
                            </form>

                            <!-- Role Notice -->
                            <div class="mt-2 text-center text-muted" style="font-size: 0.72rem;">
                                Authorized for Faculty, Residents, Audiologists &amp; Staff
                            </div>

                            <!-- Security Footnote -->
                            <div class="login-card-foot-note">
                                <div class="ssl-seal-text">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#007A3D" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    <span>256-Bit SSL Encrypted &bull; TN Health Net</span>
                                </div>
                                <div class="gov-sub-note">
                                    Government of Tamil Nadu Health &amp; Family Welfare Department
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </main>

        <!-- =========================================================
             3. MINIMAL 1-LINE FOOTER (AT THE BOTTOM)
             ========================================================= -->
        <footer class="login-bottom-strip">
            <div class="container-fluid px-3">
                &copy; <?= date('Y') ?> <strong>Department of ENT, Thanjavur Medical College &amp; Hospital</strong>. Government of Tamil Nadu. All Rights Reserved.
            </div>
        </footer>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Slideshow Rotation Script: Contained to left box only, zero flicker -->
    <script>
        // Password Visibility Toggle
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('toggleEyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }

        // Automatic slideshow rotation inside left container only
        (function() {
            const container = document.getElementById('cleanSlideshowContainer');
            if (!container) return;
            const slides = container.querySelectorAll('.slideshow-slide');
            if (!slides || slides.length === 0) return;

            let currentIndex = 0;
            const slideDuration = 4500; // 4.5 seconds per slide
            const transitionDuration = 1200; // 1.2s smooth cross-fade

            function nextSlide() {
                const prevIndex = currentIndex;
                currentIndex = (currentIndex + 1) % slides.length;

                const prevSlide = slides[prevIndex];
                const activeSlide = slides[currentIndex];

                // Keep previous slide visible in layer 1 underneath while new slide fades in
                prevSlide.classList.remove('active');
                prevSlide.classList.add('prev-active');

                // Bring current slide to layer 2 and fade it in
                activeSlide.classList.add('active');

                // After cross-fade completes, remove prev-active layer
                setTimeout(function() {
                    prevSlide.classList.remove('prev-active');
                }, transitionDuration + 50);
            }

            // Start auto slideshow
            setInterval(nextSlide, slideDuration);
        })();
    </script>
</body>
</html>
