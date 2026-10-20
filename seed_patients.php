<?php
// seed_patients.php — Inserts 50 realistic ENT (Otorhinolaryngology) patients
// Access via browser: http://localhost/ENT/seed_patients.php
// REMOVE this file after use!

require_once __DIR__ . '/config/db.php';

// ── helpers ─────────────────────────────────────────────────────────────────
function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}
function rnd($arr) { return $arr[array_rand($arr)]; }
function rndFloat($min,$max,$dec=1) { return round($min + mt_rand()/mt_getrandmax()*($max-$min),$dec); }
function rndInt($min,$max) { return mt_rand($min,$max); }
function rndDate($start,$end) {
    return date('Y-m-d', mt_rand(strtotime($start), strtotime($end)));
}

// ── data pools ───────────────────────────────────────────────────────────────
$firstNames = ['Ramesh','Suresh','Priya','Kavitha','Arun','Mani','Lakshmi','Vijay','Deepa','Kumar',
               'Anand','Meena','Ravi','Saranya','Sathish','Divya','Murugan','Geetha','Senthil','Nithya',
               'Babu','Shanthi','Rajesh','Bharathi','Manoj','Rekha','Karthik','Jothika','Venkat','Ammu',
               'Pandian','Selvi','Dinesh','Vani','Aravind','Padma','Gopal','Suganya','Prabhu','Nandha',
               'Mohan','Sumathi','Krishnan','Revathi','Balaji','Hema','Selvam','Lalitha','Prakash','Rani'];
$lastNames  = ['Kumar','Murugan','Rajan','Krishnan','Selvam','Pandian','Natarajan','Arumugam',
               'Subramanian','Kannan','Vel','Durai','Perumal','Samy','Raj','Das','Iyer','Nair','Pillai','Reddy'];
$places = ['Chennai','Coimbatore','Madurai','Tiruchirappalli','Salem','Vellore','Tirunelveli',
           'Thoothukudi','Erode','Tiruppur','Kanchipuram','Cuddalore','Nagapattinam','Thanjavur',
           'Dindigul','Dharmapuri','Krishnagiri','Villupuram','Pudukkottai','Namakkal',
           'Ramanathapuram','Sivagangai','Virudhunagar','Ariyalur','Perambalur','Karur','Tiruvarur'];
$diagnoses = [
    'Chronic Suppurative Otitis Media (CSOM)',
    'Deviated Nasal Septum (DNS) with Sinusitis',
    'Chronic Sinusitis / Nasal Polyposis',
    'Tonsillitis & Adenoid Hypertrophy',
    'Allergic Rhinitis',
    'Otitis Media with Effusion (OME)',
    'Otosclerosis & Conductive Hearing Loss',
    'Sensorineural Hearing Loss (SNHL)',
    'Vocal Cord Polyp / Vocal Nodule',
    'Laryngopharyngeal Reflux (LPR) & GERD',
    'Peritonsillar Abscess (Quinsy)',
    'Epistaxis',
    'Foreign Body (Ear / Nose / Throat)',
    'Head & Neck Neoplasm (Oral/Laryngeal CA)',
    'General ENT Evaluation',
];
$statuses     = ['Admission','Discharged','Discharged','Discharged','AMA','Death','Discharge/Referal'];
$blood_groups = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
$proc_pool    = [
    ['name'=>'Tympanoplasty Type 1','details'=>'Under GA, post-aural approach, temporalis fascia graft placed, neo-tympanum intact'],
    ['name'=>'Cortical Mastoidectomy','details'=>'Clearance of diseased mastoid air cells, antrum patent, aditus widened'],
    ['name'=>'Septoplasty','details'=>'Cottle approach, spurs excised, cartilaginous septum repositioned and splinted'],
    ['name'=>'Functional Endoscopic Sinus Surgery (FESS)','details'=>'Bilateral uncinectomy, maxillary antrostomy and anterior ethmoidectomy'],
    ['name'=>'Coblation Tonsillectomy','details'=>'Palatine tonsils dissected with coblation, haemostasis secured'],
    ['name'=>'Microlaryngeal Surgery (MLS)','details'=>'Suspension laryngoscopy, vocal nodule micro-dissection, clear vocal margins'],
    ['name'=>'Myringotomy with Grommet','details'=>'Anteroinferior quadrant radial incision, Shepard grommet inserted in situ'],
    ['name'=>'Diagnostic Nasal Endoscopy (DNE)','details'=>'3-pass nasal endoscopy, purulent middle meatal discharge documented'],
    ['name'=>'Direct Laryngoscopy & Biopsy','details'=>'Direct laryngoscopy under GA, punch biopsy taken from pyriform sinus mass'],
    ['name'=>'Emergency Tracheostomy','details'=>'Vertical midline incision, 7.5mm cuffed tracheostomy tube secured'],
];
$sm_keys = ['high_risk_surgery','emergency_airway','tracheostomy_care','foreign_body_protocol','cancer_multidisciplinary','biopsy_sent'];
$treatments_pool = [
    'Antibiotic ear drops (Ciprofloxacin + Dexamethasone), Oral Amox-Clav 625mg BD, Analgesics, Keep ear dry',
    'Normal saline nasal douching TDS, Fluticasone nasal spray 2 puffs BD, Oral Levocetirizine 5mg OD, Steam inhalation',
    'Oral Cefuroxime axetil 500mg BD, Metronidazole 400mg TDS, Betadine gargle QID, Analgesics, Vocal rest',
    'Broad-spectrum IV Ceftriaxone 1g BD, IV Hydrocortisone, Warm saline gargles, Antihistamines',
    'Post-operative dressing care, Mastoid cavity toilet, Topical ciprofloxacin-polymyxin, Water precautions',
    'Pre-op steroid cover, Antihistamines, Proton pump inhibitor (Pantoprazole 40mg OD), Speech therapy referral',
    'Anterior nasal packing with Merocel, Tranexamic acid 500mg TDS, Cold compresses, Strict BP monitoring',
    'Oral Prednisolone 1mg/kg taper, Methylcobalamin 1500mcg OD, Ginkgo biloba, Hearing aid trial advised',
];
$xray_notes_pool = [
    'X-Ray PNS (Waters view): Hazy right maxillary sinus with mucosal thickening, frontal sinuses clear',
    'X-Ray Mastoids (Townes/Schullers): Sclerotic right mastoid bone with clouding of antrum, ossicular chain intact',
    'X-Ray Soft Tissue Neck (Lateral view): Adenoid hypertrophy indenting nasopharyngeal air column',
    'X-Ray Soft Tissue Neck: Epiglottic thickening — thumb sign noted, airway column patent',
    'X-Ray PNS: Straight nasal septum, clear paranasal sinuses bilaterally',
    'X-Ray Neck (AP/Lateral): Radio-opaque foreign body noted at C6 level, no prevertebral widening',
    'Normal paranasal sinus view — all sinuses well pneumatised',
    'Soft tissue shadow obliterating left vallecula, normal laryngeal air column',
];
$ct_notes_pool = [
    'HRCT Temporal Bone: Soft tissue density in middle ear cavity, scutum intact, intact tegmen tympani',
    'CT PNS: Right Ostiomeatal complex (OMC) block with mucosal thickening in maxillary & ethmoid sinuses',
    'HRCT Temporal Bone: Erosion of incus long process, mastoid tegmen intact, no labyrinthine fistula',
    'CT Neck with Contrast: Well-defined cystic lesion in right submandibular space, deep cervical fascia intact',
    'CT PNS: Deviated nasal septum to left with spur abutting inferior turbinate, concha bullosa right',
    'CECT Head & Neck: Enhancing soft tissue mass in pyriform sinus with ipsilateral level II lymphadenopathy',
    'HRCT Temporal Bone: Bilateral otosclerotic foci around fissula ante fenestram, ossicular chain intact',
    'CT PNS (Coronal): Extranasal extension ruled out, pan-sinus mucosal thickening with allergic mucin pattern',
];
$spirometry_pool = [
    'PTA: Right Moderate Conductive Hearing Loss (AC: 45 dB, BC: 15 dB, AB Gap: 30 dB HL)',
    'PTA: Left Mild Sensorineural Hearing Loss (AC: 32 dB, BC: 30 dB), High-frequency sloping notch at 4 kHz',
    'PTA: Bilateral Normal Hearing Sensitivity (<20 dB HL across all octaves 250 Hz - 8 kHz)',
    'Impedance Audiometry: Type B flat tympanogram right ear, Type A normal left ear, Absent stapedial reflexes',
    'PTA: Right Severe Mixed Hearing Loss (AC: 75 dB, BC: 45 dB), speech discrimination score 68%',
    'Impedance Audiometry: Type C negative pressure tympanogram bilaterally — Eustachian tube dysfunction',
    'PTA: Left profound hearing loss (>90 dB HL) — sudden SNHL presentation, urgent steroid protocol initiated',
    'Pure Tone Audiometry: Bilateral symmetrical sensory loss consistent with presbycusis',
];
$echo_pool = [
    'Diagnostic Nasal Endoscopy (DNE): Deviated nasal septum to right, hypertrophied left inferior turbinate, clear OMC',
    'Video Laryngoscopy (VLS): Smooth, sessile nodule on junction of anterior and middle third of right vocal cord',
    'DNE: Polypoidal mucosa emerging from middle meatus bilaterally — Grade II ethmoidal polyposis',
    'VLS: Normal mobile vocal cords bilaterally, glottic closure complete, mild posterior laryngeal erythema (LPR)',
    'Otomicroscopy: Central perforation of pars tensa in antero-inferior quadrant, dry middle ear mucosa',
    'DNE: Pus streaming from right sphenoethmoidal recess, mucopurulent discharge in nasopharynx',
    'VLS: Exophytic ulcerative mass involving left true vocal cord, anterior commissure free, cord mobility preserved',
    'Flexible Nasopharyngolaryngoscopy: Clear hypopharynx, normal epiglottis, no vallecular pooling',
];

// ── investigation chart generator ────────────────────────────────────────────
function makeInvestigations($blood_group) {
    $rF = fn($a,$b) => (string)round($a + mt_rand()/mt_getrandmax()*($b-$a),2);
    $rI = fn($a,$b) => (string)mt_rand($a,$b);
    $date = fn($m) => date('Y-m-d', strtotime("-{$m} months"));

    $d = [
        'height'      => $rI(150,185),
        'weight'      => $rI(45,90),
        'bmi'         => (string)round(mt_rand(1700,3200)/100,1),
        'blood_group' => $blood_group,
        'scheme_card'    => (bool)mt_rand(0,1),
        'scheme_preauth' => (bool)mt_rand(0,1),
    ];

    // 3 CBCs
    for ($n=1; $n<=3; $n++) {
        $m = ($n-1)*2;
        $d["cbc{$n}_date"] = $date($m+mt_rand(0,1));
        $d["cbc{$n}_wbc"]  = $rF(4.0,14.0);
        $d["cbc{$n}_lym"]  = $rF(1.0,4.0);
        $d["cbc{$n}_mxd"]  = $rF(0.2,1.2);
        $d["cbc{$n}_neut"] = $rF(2.0,9.0);
        $d["cbc{$n}_rbc"]  = $rF(3.5,5.5);
        $d["cbc{$n}_hgb"]  = $rF(8.0,16.0);
        $d["cbc{$n}_hct"]  = $rF(25.0,50.0);
        $d["cbc{$n}_mcv"]  = $rF(70,100);
        $d["cbc{$n}_mch"]  = $rF(22,32);
        $d["cbc{$n}_mchc"] = $rF(30,36);
        $d["cbc{$n}_rdw_sd"] = $rF(35,55);
        $d["cbc{$n}_plt"]  = $rI(80,400);
    }
    // 3 LFTs
    for ($n=1; $n<=3; $n++) {
        $m = ($n-1)*2;
        $d["lft{$n}_date"]    = $date($m+mt_rand(0,1));
        $d["lft{$n}_sgot"]    = $rI(15,120);
        $d["lft{$n}_sgpt"]    = $rI(10,90);
        $d["lft{$n}_sbt"]     = $rF(0.3,2.5);
        $d["lft{$n}_conju"]   = $rF(0.1,1.0);
        $d["lft{$n}_unconju"] = $rF(0.1,1.5);
        $d["lft{$n}_sap"]     = $rI(60,300);
        $d["lft{$n}_sr_prot"] = $rF(5.5,8.5);
        $d["lft{$n}_albu"]    = $rF(2.5,5.0);
        $d["lft{$n}_globu"]   = $rF(2.0,4.0);
        $d["lft{$n}_na"]      = $rI(130,148);
        $d["lft{$n}_k"]       = $rF(3.0,5.5);
        $d["lft{$n}_cl"]      = $rI(96,108);
        $d["lft{$n}_ca"]      = $rF(8.0,10.5);
    }
    // 3 RFTs
    for ($n=1; $n<=3; $n++) {
        $m = ($n-1)*2;
        $d["rft{$n}_date"]   = $date($m+mt_rand(0,1));
        $d["rft{$n}_rbs"]    = $rI(80,250);
        $d["rft{$n}_fbs"]    = $rI(70,180);
        $d["rft{$n}_ppbs"]   = $rI(100,280);
        $d["rft{$n}_urea"]   = $rI(15,80);
        $d["rft{$n}_crea"]   = $rF(0.6,2.5);
        $d["rft{$n}_pt_inr"] = $rF(0.9,3.5);
        $d["rft{$n}_hbsag"]  = rnd(['Negative','Positive']);
        $d["rft{$n}_hcv"]    = rnd(['Negative','Positive','Reactive']);
        $d["rft{$n}_vctc"]   = rnd(['Negative','Positive','Not done']);
    }
    // Sputum
    foreach (['afb','cbnaat','mgit','cs'] as $t) {
        $d["sputum_{$t}_date"]   = $date(mt_rand(0,3));
        $d["sputum_{$t}_num"]    = rnd(['S1','S2','S1 & S2','3 samples']);
        $d["sputum_{$t}_report"] = rnd(['Positive','Negative','Not done','Scanty','1+','2+','3+']);
    }
    // Echo / Radiological
    $d['echo_report']       = rnd(['Normal','Mild PAH','Moderate PAH','Severe PAH','LV dysfunction','Pericardial effusion']);
    $d['radiological_xray'] = rnd(['Bilateral infiltrates','Right pleural effusion','Left pleural effusion','Normal','Cardiomegaly','Consolidation']);
    $d['radiological_ct']   = rnd(['Ground glass opacities','Mass lesion','Pleural thickening','Mediastinal LN','Normal','Emphysema']);
    $d['radiological_usg']  = rnd(['Pleural effusion','Normal','Hepatomegaly','Splenomegaly','Not done']);
    // PFA
    $d['pfa_colour']              = rnd(['Straw','Yellow','Haemorrhagic','Turbid','Clear']);
    $d['pfa_ada']                 = (string)mt_rand(10,90);
    $d['pfa_protein']             = (string)rnd(['2.5','3.1','4.0','1.8','5.2']);
    $d['pfa_sugar']               = (string)mt_rand(30,120);
    $d['pfa_pf_ldh']              = (string)mt_rand(200,1800);
    $d['pfa_serum_ldh']           = (string)mt_rand(180,600);
    $d['pfa_cbnaat']              = rnd(['Negative','Positive MTB detected','Not done']);
    $d['pfa_culture_sensitivity'] = rnd(['No growth','E.coli sensitive to Carbapenems','Klebsiella — pan-sensitive','Not done']);
    $d['pfa_gram_stain']          = rnd(['No organisms seen','Gram positive cocci','Gram negative bacilli','Not done']);
    for ($s=1;$s<=3;$s++) {
        $d["pfa_cell_count_s{$s}"]  = mt_rand(50,10000) . ' cells/mm³';
        $d["pfa_cytology_s{$s}"]    = rnd(['Lymphocytic exudate','Neutrophilic exudate','Mesothelial cells','Malignant cells','Not done']);
        $d["pfa_cell_block_s{$s}"]  = rnd(['Adenocarcinoma cells','Reactive mesothelial cells','No malignancy','Not done']);
    }
    // Bronchoscopy
    $d['broncho_bal_cytology']      = rnd(['Alveolar macrophages predominant','Neutrophilia','Lymphocytosis','Not done']);
    $d['broncho_cbnaat']            = rnd(['Negative','Positive MTB detected','Not done']);
    $d['broncho_cs']                = rnd(['No growth','Pseudomonas aeruginosa','Klebsiella','Not done']);
    $d['broncho_gs']                = rnd(['No organisms','Gram positive cocci','Not done']);
    $d['broncho_brush_cytology']    = rnd(['Normal bronchial epithelium','Squamous cell carcinoma','Adenocarcinoma','Not done']);
    $d['broncho_biopsy_hpe']        = rnd(['Squamous cell carcinoma','Adenocarcinoma','Granulomas — TB likely','Non-specific inflammation','Not done']);

    return json_encode($d);
}

// ── procedures generator ──────────────────────────────────────────────────────
function makeProcs($pool) {
    $count = mt_rand(1,3);
    $selected = array_rand($pool, min($count, count($pool)));
    if (!is_array($selected)) $selected = [$selected];
    $result = [];
    foreach ($selected as $i) $result[] = $pool[$i];
    return json_encode($result);
}

// ── special management generator ─────────────────────────────────────────────
function makeSM($keys) {
    $sm = [];
    foreach ($keys as $k) {
        $yes = mt_rand(0,3) === 0; // ~25% chance yes
        $sm[$k] = [
            'value'   => $yes ? 'Yes' : 'No',
            'details' => $yes ? 'Details as per clinical note' : '',
        ];
    }
    return json_encode($sm);
}

// ── INSERT ────────────────────────────────────────────────────────────────────
$inserted = 0;
$errors   = [];

$sql = "INSERT INTO patients 
    (id, name, age_sex, ip_number, abha_number, doa, transfer_in_date, dod, place, mobile_number,
     diagnosis, procedures, special_management, main_diagnosis, patient_status, cause_of_death,
     treatment, investigations,
     xray_notes, ct_notes, spirometry_notes, echo_notes,
     xray_image, ct_image, spirometry_image, echo_image, investigations_image,
     created_by)
    VALUES
    (:id,:name,:age_sex,:ip_number,:abha_number,:doa,:transfer_in_date,:dod,:place,:mobile_number,
     :diagnosis,:procedures,:special_management,:main_diagnosis,:patient_status,:cause_of_death,
     :treatment,:investigations,
     :xray_notes,:ct_notes,:spirometry_notes,:echo_notes,
     :xray_image,:ct_image,:spirometry_image,:echo_image,:investigations_image,
     :created_by)";

$stmt = $pdo->prepare($sql);

// Get a real admin user id
$adminRow = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$admin_id = $adminRow ? $adminRow['id'] : null;

// Start from IP number 50001 to avoid conflicts
$base_ip = 50001;
// Check the max existing IP to avoid conflicts
$maxIpRow = $pdo->query("SELECT MAX(CAST(ip_number AS UNSIGNED)) as max_ip FROM patients WHERE ip_number REGEXP '^[0-9]+$'")->fetch();
if ($maxIpRow && $maxIpRow['max_ip'] > $base_ip) {
    $base_ip = (int)$maxIpRow['max_ip'] + 1;
}

for ($i = 0; $i < 50; $i++) {
    $id          = uuid();
    $sex         = rnd(['M','F']);
    $age         = rndInt(18,85);
    $fname       = rnd($firstNames);
    $lname       = rnd($lastNames);
    $name        = $fname . ' ' . $lname;
    $age_sex     = $age . '/' . $sex;
    $ip_number   = (string)($base_ip + $i);
    $abha_number = rndInt(10,99) . '-' . rndInt(1000,9999) . '-' . rndInt(1000,9999) . '-' . rndInt(1000,9999);
    $doa         = rndDate('2024-01-01','2026-06-30');
    $status      = rnd($statuses);
    $dod         = null;
    $cause_of_death = '';
    if (in_array($status, ['Discharged','AMA','Death','Discharge/Referal'])) {
        $dod = date('Y-m-d', strtotime($doa) + rndInt(3,30)*86400);
        if ($dod > date('Y-m-d')) $dod = date('Y-m-d');
        if ($status === 'Death') {
            $cause_of_death = rnd([
                'Airway compromise & acute respiratory arrest',
                'Severe deep neck space infection with descending mediastinitis & septic shock',
                'Advanced Head & Neck carcinoma with carotid blowout hemorrhage',
                'Septic shock secondary to intracranial complication of CSOM',
                'Cardiopulmonary arrest post-emergency tracheostomy',
            ]);
        }
    }
    $place         = rnd($places);
    $mobile_number = '9' . rndInt(100000000, 999999999);
    $diagnosis     = rnd($diagnoses);
    $blood_group   = rnd($blood_groups);

    $investigations      = makeInvestigations($blood_group);
    $procedures_json     = makeProcs($proc_pool);
    $sm_json             = makeSM($sm_keys);
    $treatment           = rnd($treatments_pool);
    $xray_notes          = rnd($xray_notes_pool);
    $ct_notes            = rnd($ct_notes_pool);
    $spirometry_notes    = rnd($spirometry_pool);
    $echo_notes          = rnd($echo_pool);

    try {
        $stmt->execute([
            ':id'                  => $id,
            ':name'                => $name,
            ':age_sex'             => $age_sex,
            ':ip_number'           => $ip_number,
            ':abha_number'         => $abha_number,
            ':doa'                 => $doa,
            ':transfer_in_date'     => null,
            ':dod'                 => $dod,
            ':place'               => $place,
            ':mobile_number'       => $mobile_number,
            ':diagnosis'           => $diagnosis,
            ':procedures'          => $procedures_json,
            ':special_management'  => $sm_json,
            ':main_diagnosis'      => $diagnosis,
            ':patient_status'      => $status,
            ':cause_of_death'      => $cause_of_death,
            ':treatment'           => $treatment,
            ':investigations'      => $investigations,
            ':xray_notes'          => $xray_notes,
            ':ct_notes'            => $ct_notes,
            ':spirometry_notes'    => $spirometry_notes,
            ':echo_notes'          => $echo_notes,
            ':xray_image'          => json_encode([]),
            ':ct_image'            => json_encode([]),
            ':spirometry_image'    => json_encode([]),
            ':echo_image'          => json_encode([]),
            ':investigations_image'=> '',
            ':created_by'          => $admin_id,
        ]);
        $inserted++;
    } catch (PDOException $e) {
        $errors[] = "Patient #{$i} ({$name}): " . $e->getMessage();
    }
}

echo "<style>body{font-family:Arial;padding:30px;} .ok{color:green;} .err{color:red;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#0d6efd;color:#fff;}</style>";
echo "<h2>🏥 Patient Seeder</h2>";
echo "<p class='ok'>✅ <strong>{$inserted} patients inserted successfully!</strong></p>";
if ($errors) {
    echo "<p class='err'>⚠️ " . count($errors) . " errors:</p><ul>";
    foreach ($errors as $e) echo "<li class='err'>{$e}</li>";
    echo "</ul>";
}
echo "<br><a href='index.php' style='background:#0d6efd;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;'>🏠 Go to Dashboard</a>";
echo "<p style='color:#dc3545;margin-top:20px;'><strong>⚠️ DELETE this file now:</strong> <code>c:\\xampp\\htdocs\\fine\\seed_patients.php</code></p>";
