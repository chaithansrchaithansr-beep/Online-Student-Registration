<?php
/**
 * ============================================================================
 * index.php - Online Student Registration Portal (Main Form Page)
 * ============================================================================
 * Renders the responsive university-themed registration form, displays any
 * server-side validation or database errors returned from process.php,
 * preserves previously entered inputs on error, and connects JS/jQuery rules.
 */

require_once __DIR__ . '/config.php';

// Retrieve and clear one-time flash messages from session
$errors      = $_SESSION['form_errors'] ?? [];
$oldInput    = $_SESSION['old_input'] ?? [];
$serverError = $_SESSION['server_error'] ?? '';
$flashNotice = $_SESSION['flash_notice'] ?? '';

unset(
    $_SESSION['form_errors'],
    $_SESSION['old_input'],
    $_SESSION['server_error'],
    $_SESSION['flash_notice']
);

$csrfToken = getCsrfToken();

// Export configurable validation rules safely for client-side scripts
$jsConfig = [
    'usnPattern'         => USN_JS_PATTERN,
    'usnHint'            => USN_FORMAT_HINT,
    'usnExample'         => USN_EXAMPLE,
    'allowedDepartments' => array_keys(ALLOWED_DEPARTMENTS),
    'allowedSemesters'   => ALLOWED_SEMESTERS,
];

/**
 * Helper to compute CSS class and feedback state from server-side validation.
 */
function fieldStateClass(string $field, array $errors, array $oldInput): string
{
    if (isset($errors[$field])) {
        return 'is-invalid';
    }
    if (!empty($oldInput) && isset($oldInput[$field]) && $oldInput[$field] !== '') {
        return 'is-valid';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Online Student Registration Portal for university course and academic enrollment.">
    <title><?php echo e(PORTAL_TITLE); ?> | <?php echo e(COLLEGE_SHORT); ?></title>

    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Portal Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Top Institutional Utility Strip -->
    <div class="top-utility-bar">
        <div class="utility-inner">
            <span><?php echo e(COLLEGE_NAME); ?> • Academic Admissions Office</span>
            <span class="utility-badge">
                <span>Admissions Open 2026–27</span>
            </span>
        </div>
    </div>

    <!-- Portal Header -->
    <header class="portal-header">
        <div class="header-inner">
            <div class="brand-group">
                <!-- College Logo Placeholder (Custom University Crest SVG) -->
                <div class="college-logo" aria-label="College Emblem">
                    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                        <path d="M32 4L56 14V30C56 45.2 45.8 55.6 32 60C18.2 55.6 8 45.2 8 30V14L32 4Z" fill="#0F3D7A"/>
                        <path d="M32 8.5L51.5 16.6V30C51.5 42.5 43.2 51.2 32 55.2C20.8 51.2 12.5 42.5 12.5 30V16.6L32 8.5Z" fill="#EFF6FF"/>
                        <path d="M20 25L32 19L44 25L32 31L20 25Z" fill="#1D4ED8"/>
                        <path d="M24 29V36.5C24 39.2 27.6 41.5 32 41.5C36.4 41.5 40 39.2 40 36.5V29L32 33L24 29Z" fill="#0F3D7A"/>
                        <rect x="42" y="25.5" width="2" height="10" rx="1" fill="#1D4ED8"/>
                        <circle cx="43" cy="36.5" r="2" fill="#2563EB"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <p class="college-name"><?php echo e(COLLEGE_NAME); ?></p>
                    <h1><?php echo e(PORTAL_TITLE); ?></h1>
                    <p class="portal-subtitle"><?php echo e(PORTAL_SUBTITLE); ?></p>
                </div>
            </div>

            <div class="header-pill">
                <span class="status-dot" aria-hidden="true"></span>
                <span>Student Registration Active</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="portal-main">
        <div class="portal-grid">

            <!-- Registration Form Card -->
            <section class="card" aria-labelledby="formHeading">
                <div class="card-header">
                    <h2 id="formHeading">Student Registration Application</h2>
                    <p>Please fill in all mandatory details marked with <span class="required-star">*</span> accurately before submitting.</p>
                </div>

                <div class="card-body">

                    <?php if ($flashNotice !== ''): ?>
                        <div class="alert alert-info" role="status">
                            <span class="alert-icon" aria-hidden="true">&#8505;</span>
                            <div class="alert-content">
                                <strong>Notice</strong>
                                <span><?php echo e($flashNotice); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($serverError !== '' || !empty($errors)): ?>
                        <div id="serverErrorBanner" class="alert alert-error" role="alert">
                            <span class="alert-icon" aria-hidden="true">&#9888;</span>
                            <div class="alert-content">
                                <strong><?php echo $serverError !== '' ? e($serverError) : 'Please correct the highlighted errors below:'; ?></strong>
                                <?php if (!empty($errors)): ?>
                                    <ul class="alert-list">
                                        <?php foreach ($errors as $fieldError): ?>
                                            <li><?php echo e($fieldError); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Client-side jQuery Validation Summary Banner -->
                    <div id="clientErrorBanner" class="alert alert-error hidden" role="alert">
                        <span class="alert-icon" aria-hidden="true">&#9888;</span>
                        <div class="alert-content">
                            <strong>Unable to submit registration</strong>
                            <span id="clientErrorSummary">Please review and fix the highlighted fields below.</span>
                        </div>
                    </div>

                    <form id="registrationForm" action="process.php" method="POST" novalidate autocomplete="on">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                        <div class="form-grid">

                            <!-- 1. Full Name -->
                            <div class="form-group">
                                <div class="form-label-row">
                                    <label for="full_name">Full Name <span class="required-star" aria-hidden="true">*</span></label>
                                </div>
                                <div class="input-wrapper">
                                    <input
                                        type="text"
                                        id="full_name"
                                        name="full_name"
                                        class="form-control <?php echo e(fieldStateClass('full_name', $errors, $oldInput)); ?>"
                                        placeholder="e.g., Aarav Sharma"
                                        maxlength="100"
                                        required
                                        value="<?php echo e($oldInput['full_name'] ?? ''); ?>"
                                        aria-describedby="full_name_feedback"
                                    >
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="full_name_feedback" class="field-feedback <?php echo isset($errors['full_name']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['full_name']) ? e($errors['full_name']) : ''; ?>
                                </span>
                            </div>

                            <!-- 2. USN (University Seat Number) -->
                            <div class="form-group">
                                <div class="form-label-row">
                                    <label for="usn">USN (University Seat Number) <span class="required-star" aria-hidden="true">*</span></label>
                                </div>
                                <div class="input-wrapper">
                                    <input
                                        type="text"
                                        id="usn"
                                        name="usn"
                                        class="form-control <?php echo e(fieldStateClass('usn', $errors, $oldInput)); ?>"
                                        placeholder="e.g., <?php echo e(USN_EXAMPLE); ?>"
                                        maxlength="20"
                                        required
                                        style="text-transform: uppercase;"
                                        value="<?php echo e($oldInput['usn'] ?? ''); ?>"
                                        aria-describedby="usn_hint usn_feedback"
                                    >
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="usn_hint" class="field-hint"><?php echo e(USN_FORMAT_HINT); ?></span>
                                <span id="usn_feedback" class="field-feedback <?php echo isset($errors['usn']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['usn']) ? e($errors['usn']) : ''; ?>
                                </span>
                            </div>

                            <!-- 3. Email Address -->
                            <div class="form-group">
                                <div class="form-label-row">
                                    <label for="email">Email Address <span class="required-star" aria-hidden="true">*</span></label>
                                </div>
                                <div class="input-wrapper">
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control <?php echo e(fieldStateClass('email', $errors, $oldInput)); ?>"
                                        placeholder="e.g., aarav.sharma@college.edu"
                                        maxlength="120"
                                        required
                                        value="<?php echo e($oldInput['email'] ?? ''); ?>"
                                        aria-describedby="email_feedback"
                                    >
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="email_feedback" class="field-feedback <?php echo isset($errors['email']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['email']) ? e($errors['email']) : ''; ?>
                                </span>
                            </div>

                            <!-- 4. Phone Number -->
                            <div class="form-group">
                                <div class="form-label-row">
                                    <label for="phone">Phone Number <span class="required-star" aria-hidden="true">*</span></label>
                                </div>
                                <div class="input-wrapper">
                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        class="form-control <?php echo e(fieldStateClass('phone', $errors, $oldInput)); ?>"
                                        placeholder="10-digit mobile number (e.g., 9876543210)"
                                        maxlength="15"
                                        inputmode="numeric"
                                        required
                                        value="<?php echo e($oldInput['phone'] ?? ''); ?>"
                                        aria-describedby="phone_feedback"
                                    >
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="phone_feedback" class="field-feedback <?php echo isset($errors['phone']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['phone']) ? e($errors['phone']) : ''; ?>
                                </span>
                            </div>

                            <!-- 5. Department -->
                            <div class="form-group">
                                <div class="form-label-row">
                                    <label for="department">Department <span class="required-star" aria-hidden="true">*</span></label>
                                </div>
                                <div class="input-wrapper">
                                    <select
                                        id="department"
                                        name="department"
                                        class="form-control <?php echo e(fieldStateClass('department', $errors, $oldInput)); ?>"
                                        required
                                        aria-describedby="department_feedback"
                                    >
                                        <option value="">-- Select Department --</option>
                                        <?php foreach (ALLOWED_DEPARTMENTS as $deptCode => $deptLabel): ?>
                                            <option
                                                value="<?php echo e($deptCode); ?>"
                                                <?php echo (($oldInput['department'] ?? '') === $deptCode) ? 'selected' : ''; ?>
                                            >
                                                <?php echo e($deptLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="department_feedback" class="field-feedback <?php echo isset($errors['department']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['department']) ? e($errors['department']) : ''; ?>
                                </span>
                            </div>

                            <!-- 6. Semester -->
                            <div class="form-group">
                                <div class="form-label-row">
                                    <label for="semester">Semester <span class="required-star" aria-hidden="true">*</span></label>
                                </div>
                                <div class="input-wrapper">
                                    <select
                                        id="semester"
                                        name="semester"
                                        class="form-control <?php echo e(fieldStateClass('semester', $errors, $oldInput)); ?>"
                                        required
                                        aria-describedby="semester_feedback"
                                    >
                                        <option value="">-- Select Semester --</option>
                                        <?php foreach (ALLOWED_SEMESTERS as $semOption): ?>
                                            <option
                                                value="<?php echo e($semOption); ?>"
                                                <?php echo (($oldInput['semester'] ?? '') === $semOption) ? 'selected' : ''; ?>
                                            >
                                                <?php echo e($semOption); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="semester_feedback" class="field-feedback <?php echo isset($errors['semester']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['semester']) ? e($errors['semester']) : ''; ?>
                                </span>
                            </div>

                            <!-- 7. Complete Address -->
                            <div class="form-group full-width">
                                <div class="form-label-row">
                                    <label for="address">Complete Address <span class="required-star" aria-hidden="true">*</span></label>
                                    <span id="addressCounter" class="char-counter">0 / 10 min chars</span>
                                </div>
                                <div class="input-wrapper textarea-wrapper">
                                    <textarea
                                        id="address"
                                        name="address"
                                        class="form-control <?php echo e(fieldStateClass('address', $errors, $oldInput)); ?>"
                                        placeholder="Enter House/Flat No., Street Name, Locality, City, State, and PIN Code"
                                        maxlength="500"
                                        required
                                        aria-describedby="address_feedback"
                                    ><?php echo e($oldInput['address'] ?? ''); ?></textarea>
                                    <span class="input-status-icon valid-icon" aria-hidden="true">&#10003;</span>
                                    <span class="input-status-icon invalid-icon" aria-hidden="true">!</span>
                                </div>
                                <span id="address_feedback" class="field-feedback <?php echo isset($errors['address']) ? 'error-message visible' : ''; ?>" role="status">
                                    <?php echo isset($errors['address']) ? e($errors['address']) : ''; ?>
                                </span>
                            </div>

                        </div>

                        <!-- Form Action Buttons -->
                        <div class="form-actions">
                            <span class="field-hint"><span class="required-star">*</span> Indicates required fields</span>
                            <div class="action-buttons">
                                <button type="reset" id="resetBtn" class="btn btn-secondary">
                                    Reset Form
                                </button>
                                <button type="submit" id="submitBtn" class="btn btn-primary">
                                    Submit Registration
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Right Sidebar: Instructions & Guidelines -->
            <aside class="sidebar-stack" aria-label="Registration Instructions">
                <div class="card">
                    <div class="card-header">
                        <h2>Registration Guidelines</h2>
                        <p>Verify these instructions before submitting</p>
                    </div>
                    <div class="card-body">
                        <ul class="info-list">
                            <li>
                                <span class="info-bullet">1</span>
                                <span><strong>Full Name:</strong> Enter your official name as per academic records (at least 3 letters).</span>
                            </li>
                            <li>
                                <span class="info-bullet">2</span>
                                <span><strong>Unique USN:</strong> Each University Seat Number can only be registered once. Duplicate USNs are rejected.</span>
                            </li>
                            <li>
                                <span class="info-bullet">3</span>
                                <span><strong>Contact Details:</strong> Provide an active email address and a valid 10-digit mobile number.</span>
                            </li>
                            <li>
                                <span class="info-bullet">4</span>
                                <span><strong>Address:</strong> Include street, city, and postal code (minimum 10 characters).</span>
                            </li>
                            <li>
                                <span class="info-bullet">5</span>
                                <span><strong>Confirmation Receipt:</strong> After submitting, save or print your registration confirmation receipt.</span>
                            </li>
                        </ul>

                        <div class="usn-sample-box">
                            <strong>Accepted USN Pattern:</strong><br>
                            <span><?php echo e(USN_FORMAT_HINT); ?></span><br>
                            <code><?php echo e(USN_EXAMPLE); ?></code>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 style="margin: 0 0 0.4rem; font-size: 1rem; color: var(--primary-900);">Need Help?</h3>
                        <p style="margin: 0; font-size: 0.87rem; color: var(--neutral-500);">
                            For USN correction or technical support, contact the Registrar Helpdesk at
                            <a href="mailto:<?php echo e(SUPPORT_EMAIL); ?>" style="color: var(--primary-700); font-weight: 600;">
                                <?php echo e(SUPPORT_EMAIL); ?>
                            </a>.
                        </p>
                    </div>
                </div>
            </aside>

        </div>
    </main>

    <!-- Portal Footer -->
    <footer class="portal-footer">
        <div class="footer-inner">
            <span>&copy; <?php echo e(COPYRIGHT_YEAR); ?> <?php echo e(COLLEGE_NAME); ?>. All Rights Reserved.</span>
            <span>Online Student Registration Portal • Powered by PHP &amp; MySQL</span>
        </div>
    </footer>

    <!-- Configuration Passed Safely from PHP to JavaScript -->
    <script>
        window.REGISTRATION_CONFIG = <?php echo json_encode($jsConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <!-- jQuery 3.7.1 CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Fallback to local jQuery if CDN is unavailable -->
    <script>
        if (typeof window.jQuery === 'undefined') {
            document.write('<script src="assets/jquery-3.7.1.min.js"><\/script>');
        }
    </script>

    <!-- Client-Side Validation Rules & jQuery Form Behavior -->
    <script src="validation.js"></script>
    <script src="script.js"></script>
</body>
</html>
