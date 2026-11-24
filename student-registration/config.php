<?php
/**
 * ============================================================================
 * config.php - Application, Validation & Database Configuration
 * ============================================================================
 * Central configuration file for the Online Student Registration Portal.
 * All portal settings, configurable USN format rules, dropdown options,
 * database credentials, and security helpers live here.
 */

// ----------------------------------------------------------------------------
// 1. Session & Timezone Configuration
// ----------------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters where supported
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ----------------------------------------------------------------------------
// 2. Portal & Institution Branding
// ----------------------------------------------------------------------------
define('COLLEGE_NAME', 'Visvesvaraya Institute of Technology & Engineering');
define('COLLEGE_SHORT', 'VITE');
define('PORTAL_TITLE', 'ONLINE STUDENT REGISTRATION PORTAL');
define('PORTAL_SUBTITLE', 'Academic Year 2026–2027 • Student Enrollment & Course Registration');
define('SUPPORT_EMAIL', 'admissions@vite-campus.edu');
define('COPYRIGHT_YEAR', date('Y'));

// ----------------------------------------------------------------------------
// 3. Database Configuration (MySQL via PDO)
// ----------------------------------------------------------------------------
// Set DB_ENABLED to true when MySQL is configured (local XAMPP/MySQL or cloud host).
// Set DB_ENABLED to false only if testing PHP form processing without a database.
define('DB_ENABLED', getenv('DB_ENABLED') !== false ? filter_var(getenv('DB_ENABLED'), FILTER_VALIDATE_BOOLEAN) : true);
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql'); // 'mysql' (default) or 'sqlite' (local fallback)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'student_registration');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');
define('SQLITE_DB_PATH', getenv('SQLITE_DB_PATH') ?: __DIR__ . '/data/student_registration.sqlite');

// Automatically create the database and table locally if privileges allow.
// On shared hosting (like InfinityFree), the database already exists from cPanel.
define('DB_AUTO_SETUP', true);

// ----------------------------------------------------------------------------
// 4. Configurable College USN Format
// ----------------------------------------------------------------------------
// Default format matches standard engineering USN: e.g., 1RV23CS045
//   [1-4]       -> Region / Campus code (1 digit)
//   [A-Z]{2}    -> College code (2 uppercase letters)
//   [0-9]{2}    -> Admission year (2 digits)
//   [A-Z]{2,3}  -> Branch code (2-3 uppercase letters, e.g. CS, IS, EC, AIM)
//   [0-9]{3}    -> Roll number (3 digits)
// To customize for a different college format, update both USN_REGEX and USN_JS_PATTERN.
define('USN_REGEX', '/^[1-4][A-Z]{2}[0-9]{2}[A-Z]{2,3}[0-9]{3}$/');
define('USN_JS_PATTERN', '^[1-4][A-Z]{2}[0-9]{2}[A-Z]{2,3}[0-9]{3}$');
define('USN_FORMAT_HINT', 'Format: 1 digit (1-4) + 2 letters + 2 digits + 2-3 letters + 3 digits (e.g., 1RV23CS045)');
define('USN_EXAMPLE', '1RV23CS045');

// ----------------------------------------------------------------------------
// 5. Allowed Dropdown Values
// ----------------------------------------------------------------------------
define('ALLOWED_DEPARTMENTS', [
    'CSE'   => 'CSE — Computer Science & Engineering',
    'ISE'   => 'ISE — Information Science & Engineering',
    'AIML'  => 'AIML — Artificial Intelligence & Machine Learning',
    'ECE'   => 'ECE — Electronics & Communication Engineering',
    'EEE'   => 'EEE — Electrical & Electronics Engineering',
    'ME'    => 'ME — Mechanical Engineering',
    'CIVIL' => 'CIVIL — Civil Engineering',
]);

define('ALLOWED_SEMESTERS', [
    '1st Semester',
    '2nd Semester',
    '3rd Semester',
    '4th Semester',
    '5th Semester',
    '6th Semester',
    '7th Semester',
    '8th Semester',
]);

// ----------------------------------------------------------------------------
// 6. Security & Output Escaping Helpers
// ----------------------------------------------------------------------------

/**
 * Escapes HTML special characters to prevent Cross-Site Scripting (XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generates or retrieves the session CSRF token.
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted CSRF token using timing-safe comparison.
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Cleans raw user input by trimming whitespace and stripping control characters.
 */
function sanitizeInput(?string $value): string
{
    $cleaned = trim((string) $value);
    // Remove null bytes and non-printable control characters (keep newlines/tabs for address)
    $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $cleaned) ?? '';
    return $cleaned;
}
