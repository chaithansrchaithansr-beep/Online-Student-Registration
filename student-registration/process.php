<?php
/**
 * ============================================================================
 * process.php - Server-Side Form Validation & Database Storage
 * ============================================================================
 * Processes POST submissions from index.php:
 * 1. Verifies request method and CSRF token.
 * 2. Sanitizes and strictly validates every field on the server side.
 * 3. Checks for duplicate USN and inserts data into MySQL via PDO prepared statements.
 * 4. Redirects to confirmation.php on success, or back to index.php with errors.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Helper to store errors/old input in session and redirect back to index.php.
 */
function redirectWithErrors(array $errors, array $oldInput, string $serverError = ''): void
{
    $_SESSION['form_errors']  = $errors;
    $_SESSION['old_input']    = $oldInput;
    $_SESSION['server_error'] = $serverError;

    header('Location: index.php', true, 303);
    exit;
}

// 1. Only allow POST submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_notice'] = 'Please submit the registration form below.';
    header('Location: index.php', true, 303);
    exit;
}

// 2. Verify CSRF token to protect against Cross-Site Request Forgery
$submittedToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($submittedToken)) {
    redirectWithErrors(
        [],
        [],
        'Your session expired or the security token is invalid. Please try submitting again.'
    );
}

// 3. Collect and sanitize raw inputs
$fullName   = preg_replace('/\s+/u', ' ', sanitizeInput($_POST['full_name'] ?? '')) ?? '';
$usn        = strtoupper(sanitizeInput($_POST['usn'] ?? ''));
$email      = sanitizeInput($_POST['email'] ?? '');
$phone      = sanitizeInput($_POST['phone'] ?? '');
$department = sanitizeInput($_POST['department'] ?? '');
$semester   = sanitizeInput($_POST['semester'] ?? '');
$address    = sanitizeInput($_POST['address'] ?? '');

$oldInput = [
    'full_name'  => $fullName,
    'usn'        => $usn,
    'email'      => $email,
    'phone'      => $phone,
    'department' => $department,
    'semester'   => $semester,
    'address'    => $address,
];

// 4. Perform strict Server-Side Validation (never trust client-side JS alone)
$errors = [];

// 4a. Full Name: valid characters and minimum 3 alphabetic letters
if ($fullName === '') {
    $errors['full_name'] = 'Full name is required.';
} elseif (!preg_match("/^[A-Za-z.\s'-]+$/", $fullName)) {
    $errors['full_name'] = 'Name may only contain letters, spaces, dots (.), hyphens (-), and apostrophes (\').';
} else {
    $letterCount = preg_match_all('/[A-Za-z]/', $fullName);
    if ($letterCount === false || $letterCount < 3) {
        $errors['full_name'] = 'Full name must contain at least 3 letters.';
    } elseif (mb_strlen($fullName) > 100) {
        $errors['full_name'] = 'Full name must not exceed 100 characters.';
    }
}

// 4b. USN: non-empty and matches configurable college USN format
if ($usn === '') {
    $errors['usn'] = 'USN is required.';
} elseif (!preg_match(USN_REGEX, $usn)) {
    $errors['usn'] = 'Invalid USN. ' . USN_FORMAT_HINT;
}

// 4c. Email Address: valid email format
if ($email === '') {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || str_contains($email, '..') || mb_strlen($email) > 120) {
    $errors['email'] = 'Please enter a valid email address.';
}

// 4d. Phone Number: exactly 10 digits
if ($phone === '') {
    $errors['phone'] = 'Phone number is required.';
} elseif (!preg_match('/^\d{10}$/', $phone)) {
    $errors['phone'] = 'Phone number must contain exactly 10 digits.';
}

// 4e. Department: must be selected from allowed list
if ($department === '') {
    $errors['department'] = 'Please select a department.';
} elseif (!array_key_exists($department, ALLOWED_DEPARTMENTS)) {
    $errors['department'] = 'Selected department is invalid.';
}

// 4f. Semester: must be selected from allowed list
if ($semester === '') {
    $errors['semester'] = 'Please select a semester.';
} elseif (!in_array($semester, ALLOWED_SEMESTERS, true)) {
    $errors['semester'] = 'Selected semester is invalid.';
}

// 4g. Address: non-empty and at least 10 characters
if ($address === '') {
    $errors['address'] = 'Complete address is required.';
} elseif (mb_strlen($address) < 10) {
    $errors['address'] = 'Address must contain at least 10 characters.';
} elseif (mb_strlen($address) > 500) {
    $errors['address'] = 'Address must not exceed 500 characters.';
}

// Return to form if any validation error occurred
if (!empty($errors)) {
    redirectWithErrors($errors, $oldInput);
}

// 5. Store validated record in MySQL database (if enabled)
$registrationId = null;
$createdAt      = date('Y-m-d H:i:s');
$dbStored       = false;

if (DB_ENABLED) {
    try {
        $pdo = getDatabaseConnection();

        // Prevent duplicate USN registrations
        if (isUsnRegistered($pdo, $usn)) {
            $errors['usn'] = "USN {$usn} is already registered. Duplicate USN registrations are not allowed.";
            redirectWithErrors(
                $errors,
                $oldInput,
                'Duplicate registration detected.'
            );
        }

        $insertResult = insertStudentRegistration($pdo, [
            'full_name'  => $fullName,
            'usn'        => $usn,
            'email'      => $email,
            'phone'      => $phone,
            'department' => $department,
            'semester'   => $semester,
            'address'    => $address,
        ]);

        $registrationId = $insertResult['registration_id'];
        $createdAt      = $insertResult['created_at'];
        $dbStored       = true;
    } catch (PDOException $e) {
        // SQLSTATE 23000 / MySQL 1062 handles unique constraint race conditions
        $isDuplicate = $e->getCode() === '23000' || (int) ($e->errorInfo[1] ?? 0) === 1062;
        if ($isDuplicate) {
            $errors['usn'] = "USN {$usn} is already registered. Duplicate USN registrations are not allowed.";
            redirectWithErrors(
                $errors,
                $oldInput,
                'Duplicate registration detected.'
            );
        }

        error_log('Student Registration DB Error: ' . $e->getMessage());
        redirectWithErrors(
            [],
            $oldInput,
            'Database error: Unable to save registration right now. Please verify MySQL is running and configured in config.php.'
        );
    }
}

// 6. Save confirmation payload in session and redirect to confirmation.php (PRG pattern)
$_SESSION['registration_confirmation'] = [
    'registration_id'  => $registrationId,
    'db_stored'        => $dbStored,
    'full_name'        => $fullName,
    'usn'              => $usn,
    'email'            => $email,
    'phone'            => $phone,
    'department'       => $department,
    'department_label' => ALLOWED_DEPARTMENTS[$department] ?? $department,
    'semester'         => $semester,
    'address'          => $address,
    'created_at'       => $createdAt,
];

unset($_SESSION['form_errors'], $_SESSION['old_input'], $_SESSION['server_error']);

header('Location: confirmation.php', true, 303);
exit;
