<?php
/**
 * ============================================================================
 * confirmation.php - Registration Confirmation & Printable Receipt Page
 * ============================================================================
 * Displays the actual submitted student registration details stored in session
 * by process.php after validation and database insertion.
 */

require_once __DIR__ . '/config.php';

$confirmation = $_SESSION['registration_confirmation'] ?? null;

// Prevent direct access without a genuine completed submission
if (!is_array($confirmation) || empty($confirmation['usn'])) {
    $_SESSION['flash_notice'] = 'No active registration found. Please complete the form to register.';
    header('Location: index.php', true, 303);
    exit;
}

// Format date and time cleanly for display
$timestamp = strtotime((string) ($confirmation['created_at'] ?? 'now'));
$formattedDateTime = $timestamp !== false
    ? date('d M Y, h:i:s A', $timestamp)
    : (string) ($confirmation['created_at'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation | <?php echo e(PORTAL_TITLE); ?></title>

    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Portal Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Top Utility Bar -->
    <div class="top-utility-bar">
        <div class="utility-inner">
            <span><?php echo e(COLLEGE_NAME); ?> • Official Registration Receipt</span>
            <span class="utility-badge">Verified Submission</span>
        </div>
    </div>

    <!-- Header -->
    <header class="portal-header">
        <div class="header-inner">
            <div class="brand-group">
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
                    <p class="portal-subtitle">Student Registration Confirmation &amp; Summary</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Confirmation Card -->
    <main class="portal-main">
        <div class="confirmation-wrapper">
            <section class="card" aria-labelledby="confirmationHeading">

                <!-- Green Success Icon & Heading -->
                <div class="success-banner">
                    <div class="success-icon-circle" aria-hidden="true">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h2 id="confirmationHeading">Registration Successful!</h2>
                    <p>
                        Thank you, <strong><?php echo e($confirmation['full_name']); ?></strong>. Your student registration
                        details have been verified and recorded<?php echo !empty($confirmation['db_stored']) ? ' in the university database' : ''; ?>.
                    </p>

                    <?php if (!empty($confirmation['registration_id'])): ?>
                        <div class="reg-id-badge">
                            <span>Unique Registration ID:</span>
                            <span><?php echo e($confirmation['registration_id']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Submitted Details Summary -->
                <div class="card-body">
                    <div class="summary-grid">

                        <?php if (!empty($confirmation['registration_id'])): ?>
                            <div class="summary-item">
                                <span class="summary-label">Unique Registration ID</span>
                                <span class="summary-value usn-code"><?php echo e($confirmation['registration_id']); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-item">
                            <span class="summary-label">Registration Date &amp; Time</span>
                            <span class="summary-value"><?php echo e($formattedDateTime); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Student Full Name</span>
                            <span class="summary-value"><?php echo e($confirmation['full_name']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">USN (University Seat Number)</span>
                            <span class="summary-value usn-code"><?php echo e($confirmation['usn']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Email Address</span>
                            <span class="summary-value"><?php echo e($confirmation['email']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Phone Number</span>
                            <span class="summary-value"><?php echo e($confirmation['phone']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Department</span>
                            <span class="summary-value"><?php echo e($confirmation['department_label'] ?? $confirmation['department']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Semester</span>
                            <span class="summary-value"><?php echo e($confirmation['semester']); ?></span>
                        </div>

                        <div class="summary-item full-width">
                            <span class="summary-label">Complete Address</span>
                            <span class="summary-value"><?php echo nl2br(e($confirmation['address'])); ?></span>
                        </div>

                    </div>

                    <!-- Navigation & Print Actions -->
                    <div class="confirmation-actions no-print">
                        <a href="index.php" class="btn btn-secondary">
                            &larr; Back to Home
                        </a>

                        <button type="button" id="printBtn" class="btn btn-primary" onclick="window.print()">
                            Print Registration
                        </button>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-inner">
            <span>&copy; <?php echo e(COPYRIGHT_YEAR); ?> <?php echo e(COLLEGE_NAME); ?>. All Rights Reserved.</span>
            <span>Online Student Registration Portal • Official Confirmation</span>
        </div>
    </footer>

</body>
</html>
