# Online Student Registration System

A responsive, full-stack **Online Student Registration Portal** built with **HTML5, CSS3, JavaScript, jQuery, PHP (PDO), and MySQL**.

---

## Project Structure

```text
student-registration/
├── index.php          # Main registration form UI (HTML5 + PHP flash/CSRF integration)
├── process.php        # Server-side validation, sanitization & MySQL PDO insertion
├── confirmation.php   # Registration confirmation summary & printable receipt
├── database.php       # PDO database connection, auto-setup & prepared statement helpers
├── config.php         # Portal branding, configurable USN format, DB credentials & XSS/CSRF helpers
├── schema.sql         # Standalone MySQL DDL script for manual / phpMyAdmin import
├── style.css          # Responsive university-themed UI (Blue / White / Light Grey) & print CSS
├── validation.js      # Pure JavaScript validation rules (synchronized with config.php)
├── script.js          # jQuery real-time validation, feedback messages & reset handler
└── README.md          # Setup, testing, and cloud deployment guide
```

---

## Features Implemented

- **7-Field Registration Form**: Full Name, USN, Email Address, Phone Number, Department (`CSE`, `ISE`, `AIML`, `ECE`, `EEE`, `ME`, `CIVIL`), Semester (`1st` to `8th Semester`), and Complete Address.
- **Triple-Layer Validation (JS + jQuery + PHP)**:
  - **Full Name**: Valid characters only, minimum 3 letters.
  - **USN**: Non-empty, matches configurable college USN regex (`USN_REGEX` & `USN_JS_PATTERN` in `config.php`, default e.g. `1RV23CS045`).
  - **Email**: Valid email format.
  - **Phone**: Exactly 10 numeric digits.
  - **Department & Semester**: Mandatory valid dropdown selections.
  - **Address**: Minimum 10 characters.
- **Security**:
  - Output escaping with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (`e()` helper) to prevent XSS.
  - Session-based CSRF token verification in `process.php`.
  - Parameterized PDO prepared statements to prevent SQL injection.
  - Duplicate USN prevention via pre-insert query check and `UNIQUE KEY uq_students_usn`.
- **Confirmation Receipt**:
  - Green success badge, Unique Registration ID (`REG-YYYY-00001`), exact submission timestamp, all submitted details, **Back to Home**, and **Print Registration** (`@media print` styling).

---

## Configurable USN Format

To change the accepted college USN format, edit `config.php`:

```php
define('USN_REGEX', '/^[1-4][A-Z]{2}[0-9]{2}[A-Z]{2,3}[0-9]{3}$/');
define('USN_JS_PATTERN', '^[1-4][A-Z]{2}[0-9]{2}[A-Z]{2,3}[0-9]{3}$');
define('USN_FORMAT_HINT', 'Format: 1 digit (1-4) + 2 letters + 2 digits + 2-3 letters + 3 digits (e.g., 1RV23CS045)');
define('USN_EXAMPLE', '1RV23CS045');
```

`index.php` automatically passes `USN_JS_PATTERN` and `USN_FORMAT_HINT` to `validation.js`, so both client-side and server-side validation stay synchronized.

---

## Local Setup & Testing (XAMPP / PHP + MySQL)

1. **Start MySQL** (via XAMPP Control Panel or local MySQL 8.0 service).
2. **Create Database (optional)**: `database.php` automatically creates `student_registration` and the `students` table when `DB_AUTO_SETUP` is `true` and your MySQL user has `CREATE` privileges. Alternatively, import `schema.sql` via phpMyAdmin or MySQL CLI.
3. **Configure Credentials**: Update `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `config.php` (or set environment variables).
4. **Run the Server**:
   - **PHP Built-in Server**:
     ```bash
     cd student-registration
     php -S localhost:8000
     ```
     Open `http://localhost:8000` in your browser.
   - **XAMPP**: Copy the `student-registration` folder to `C:\xampp\htdocs\student-registration\` and open `http://localhost/student-registration/`.

---

## Cloud Deployment Instructions (InfinityFree / Free PHP Hosting)

This project is ready for free PHP + MySQL shared hosting such as **InfinityFree** (`infinityfree.com`).

### Step 1: Create Hosting Account & Website
1. Sign up at [https://www.infinityfree.com](https://www.infinityfree.com) and create a free hosting account with a free subdomain (e.g., `yourname-portal.infinityfreeapp.com`).
2. Open the **Control Panel (VistaPanel)** from your InfinityFree client area.

### Step 2: Create the MySQL Database
1. In VistaPanel, open **MySQL Databases**.
2. Create a new database (e.g., `epiz_12345678_student_registration`).
3. Note down the **exact values** shown on the MySQL Databases screen:
   - **MySQL Hostname** (e.g., `sql302.infinityfree.com` — *never* `localhost` on InfinityFree)
   - **MySQL Database Name** (e.g., `epiz_12345678_student_registration`)
   - **MySQL Username** (e.g., `epiz_12345678`)
   - **MySQL Password** (your hosting account/vPanel password)
   - **Port**: `3306`

### Step 3: Import the Database Table
1. In VistaPanel, open **phpMyAdmin** and click **Connect Now** next to your newly created database.
2. Click the **SQL** tab (or **Import** tab), paste only the `CREATE TABLE` block from `schema.sql`, and click **Go**:
   ```sql
   CREATE TABLE IF NOT EXISTS `students` (
       `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
       `full_name` VARCHAR(100) NOT NULL,
       `usn` VARCHAR(20) NOT NULL,
       `email` VARCHAR(120) NOT NULL,
       `phone` VARCHAR(15) NOT NULL,
       `department` VARCHAR(20) NOT NULL,
       `semester` VARCHAR(25) NOT NULL,
       `address` TEXT NOT NULL,
       `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (`id`),
       UNIQUE KEY `uq_students_usn` (`usn`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

### Step 4: Update `config.php` for Production
Edit `config.php` with your InfinityFree MySQL details and disable `DB_AUTO_SETUP` (shared hosts restrict `CREATE DATABASE`):

```php
define('DB_ENABLED', true);
define('DB_HOST', 'sql302.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'epiz_12345678_student_registration');
define('DB_USER', 'epiz_12345678');
define('DB_PASS', 'YOUR_VPANEL_PASSWORD');
define('DB_AUTO_SETUP', false);
```

### Step 5: Upload Project Files
1. Open **Online File Manager** in InfinityFree (or connect via FTP like FileZilla using your FTP credentials).
2. Navigate inside the `htdocs/` directory.
3. Upload all files from `student-registration/` (`index.php`, `confirmation.php`, `process.php`, `database.php`, `config.php`, `style.css`, `script.js`, `validation.js`, and optional `assets/` if using local jQuery).

### Step 6: Test the Live Application
1. Visit your live URL (e.g., `https://yourname-portal.infinityfreeapp.com/`).
2. Test empty submission, invalid email/phone/USN/address, and missing dropdowns to verify client + server validation.
3. Submit a valid student registration and confirm redirection to `confirmation.php` with a `REG-YYYY-00001` ID.
4. Check phpMyAdmin -> `students` table -> **Browse** to confirm the row was stored.
5. Submit the exact same USN again and confirm the duplicate USN error blocks it.

> **Deployment Status Note:** This repository is prepared and configured for deployment, but has not been uploaded to a live InfinityFree account automatically because hosting credentials are user-owned.
