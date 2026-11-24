<?php
/**
 * ============================================================================
 * database.php - MySQL PDO Database Connection & Query Helpers
 * ============================================================================
 * Handles connecting to the MySQL `student_registration` database using PDO
 * prepared statements, creating the schema automatically in local dev mode,
 * checking duplicate USNs, and inserting new student registrations safely.
 */

require_once __DIR__ . '/config.php';

/**
 * Returns a configured PDO connection to the MySQL database.
 *
 * @throws PDOException When the database connection or schema setup fails.
 */
function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'sqlite') {
        $sqliteDir = dirname(SQLITE_DB_PATH);
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . SQLITE_DB_PATH, null, null, $options);
        ensureStudentsTableExists($pdo);
        return $pdo;
    }

    $dsnWithDb = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Error code 1049 = Unknown database. In local dev, create it automatically.
        $isUnknownDb = (int) ($e->errorInfo[1] ?? 0) === 1049 || str_contains($e->getMessage(), 'Unknown database');

        if (DB_AUTO_SETUP && $isUnknownDb) {
            $dsnServerOnly = sprintf(
                'mysql:host=%s;port=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_CHARSET
            );
            $serverPdo = new PDO($dsnServerOnly, DB_USER, DB_PASS, $options);
            $safeDbName = str_replace('`', '``', DB_NAME);
            $serverPdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
        } else {
            throw $e;
        }
    }

    if (DB_AUTO_SETUP) {
        ensureStudentsTableExists($pdo);
    }

    return $pdo;
}

/**
 * Ensures the `students` table exists in the connected database.
 */
function ensureStudentsTableExists(PDO $pdo): void
{
    if (DB_DRIVER === 'sqlite') {
        $sql = "
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                usn TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                department TEXT NOT NULL,
                semester TEXT NOT NULL,
                address TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT (datetime('now', 'localtime'))
            )
        ";
        $pdo->exec($sql);
        return;
    }

    $sql = "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql);
}

/**
 * Checks whether a given USN is already registered in the `students` table.
 */
function isUsnRegistered(PDO $pdo, string $usn): bool
{
    $stmt = $pdo->prepare('SELECT id FROM students WHERE usn = :usn LIMIT 1');
    $stmt->execute([':usn' => $usn]);
    return $stmt->fetchColumn() !== false;
}

/**
 * Inserts a validated student registration into MySQL using a prepared statement.
 *
 * @param array{
 *   full_name: string,
 *   usn: string,
 *   email: string,
 *   phone: string,
 *   department: string,
 *   semester: string,
 *   address: string
 * } $data
 * @return array{id: int, registration_id: string, created_at: string}
 */
function insertStudentRegistration(PDO $pdo, array $data): array
{
    $sql = '
        INSERT INTO students (full_name, usn, email, phone, department, semester, address)
        VALUES (:full_name, :usn, :email, :phone, :department, :semester, :address)
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':full_name'  => $data['full_name'],
        ':usn'        => $data['usn'],
        ':email'      => $data['email'],
        ':phone'      => $data['phone'],
        ':department' => $data['department'],
        ':semester'   => $data['semester'],
        ':address'    => $data['address'],
    ]);

    $insertedId = (int) $pdo->lastInsertId();

    // Fetch exact database timestamp for the confirmation receipt
    $fetchStmt = $pdo->prepare('SELECT created_at FROM students WHERE id = :id LIMIT 1');
    $fetchStmt->execute([':id' => $insertedId]);
    $createdAt = $fetchStmt->fetchColumn() ?: date('Y-m-d H:i:s');

    return [
        'id'              => $insertedId,
        'registration_id' => sprintf('REG-%s-%05d', date('Y'), $insertedId),
        'created_at'      => (string) $createdAt,
    ];
}
