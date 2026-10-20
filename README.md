# MedPortal - Department of ENT (Otorhinolaryngology)

### Hospital Departmental Access & Clinical Management System (PHP & MySQL / XAMPP)

Self-contained, database-driven **Department of ENT (Otorhinolaryngology)** management portal. Designed for seamless execution in XAMPP Apache & MariaDB / MySQL.

---

## 🔑 Default Credentials

All accounts can be used to log in immediately at `http://localhost/ENT/login.php`:

| Username | Password | Role | Department Access | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| **`admin`** | `password` | Administrator | Global (Full Access) | Master Administrator (Patients, Census, Faculty, Exams, NMC, Users) |
| **`admin-ent`** | `Ent@306` | Administrator | ENT Department | Head of Department (HOD) Portal Administrator |
| **`ENT`** | `Ent306` | Staff | ENT Clinical Staff | Resident Medical Officer / ENT Duty Staff (Patient Entry, Records) |
| **`admin-tm`** | `Tm@306` | Administrator | Legacy Access | Backward-compatible administrative login |
| **`TM`** | `Tm306` | Staff | Legacy Access | Backward-compatible staff login |

---

## 🗄️ Database Import Instructions for XAMPP

The database file is provided in two identical filenames for your convenience:
- [`database.sql`](./database.sql)
- [`ent_db.sql`](./ent_db.sql)

### Option A: Import via phpMyAdmin (Web Browser)
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open your web browser and navigate to:
   ```text
   http://localhost/phpmyadmin/
   ```
3. Click on the **Import** tab at the top menu bar.
4. Click **Choose File** (or "Browse") and select `database.sql` (or `ent_db.sql`) from `C:\xampp\htdocs\ENT\`.
5. Click **Import** (or "Go") at the bottom of the page.
6. The script will automatically create the `ent_db` database and all 21 tables pre-populated with:
   - ENT Academic Courses (M.S. ENT, DLO, BASLP, DHLS)
   - NMC Medical Education Programs (MBBS, M.S. ENT)
   - ENT Faculty profiles, qualifications, and teaching experience
   - Pre-loaded ENT clinical patient records and monthly census

---

### Option B: Import via XAMPP Shell / Command Prompt
Run the following single command in your Windows Command Prompt or XAMPP Shell:
```cmd
c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\ENT\database.sql
```
*(No password is required for default XAMPP MySQL root).*

---

## ⚙️ Configuration (`config/db.php`)

Database settings are configured in [`config/db.php`](./config/db.php):
```php
$host = '127.0.0.1';
$db   = 'ent_db';
$user = 'root';
$pass = '';
```
*Note: `config/db.php` includes automatic database creation and automated schema upgrading (`auto_upgrade_db`), so any missing columns are automatically provisioned at runtime.*

---

## 🏥 ENT Clinical Features & Modules

1. **Patient Master Directory & Search**:
   - Comprehensive records with 15 predefined ENT conditions (CSOM, DNS, Sinusitis, Tonsillitis, Vocal Cord Nodules, SNHL, Otosclerosis, Epistaxis, etc.).
   - Procedure documentation (Tympanoplasty, Mastoidectomy, Septoplasty, FESS, Coblation Tonsillectomy, MLS, Grommet, Tracheostomy, etc.).
   - Investigation mapping:
     - Pure Tone Audiometry (PTA) & Impedance Tympanograms
     - Diagnostic Endoscopy (DNE, VLS, Otomicroscopy)
     - PNS / Mastoid X-Rays & Temporal Bone HRCT scans
   - Word (.doc) formatted ENT Discharge Summary generator with hospital branding.

2. **Department Census & Statistical Analytics**:
   - Out-patient (OP) and In-patient (IP) monthly statistics.
   - ENT Surgical performance grid (13 specialized procedures).
   - CMCHIS (Government Health Insurance) Pre-Auth & Claims tracking.
   - Dedicated specialty clinic tabs:
     - Otology & CSOM Clinic (NPPCD)
     - Rhinology & Sinusitis Clinic
     - Head & Neck / Voice Clinic

3. **Academic Examination Portal**:
   - PG Degree M.S. ENT, DLO, BASLP, and DHLS management.
   - Examiner allocation, candidate roll numbers, multi-component marksheets, and Excel exports.

4. **NMC Inspection Management**:
   - MBBS & M.S. ENT regulatory tracking.
   - Faculty declaration document vaults and Standard Assessment Form (SAF) uploads.

5. **Faculty CV & Dossier Management**:
   - Profiles, designations (Professor & HOD, Assoc Prof, Asst Prof, Senior Resident), qualifications, teaching timelines, and printable departmental CV generation.

---

## 🌐 Accessing the Application

- **Login Page**: `http://localhost/ENT/login.php`
- **Dashboard**: `http://localhost/ENT/index.php`
- **Seed Extra Patients**: `http://localhost/ENT/seed_patients.php`
