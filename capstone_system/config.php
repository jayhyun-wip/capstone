<?php
/**
 * BayanTap Water Utility Billing System
 * Database Configuration
 */

// ── Database Credentials ──────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_USER',     'root');         // Change to your MySQL username
define('DB_PASS',     '');             // Change to your MySQL password
define('DB_NAME',     'bayantap_db');
define('DB_CHARSET',  'utf8mb4');

// ── Application Settings ──────────────────────────────────────
define('APP_NAME',    'BayanTap');
define('APP_TAGLINE', 'Marcos Village Water District');
define('APP_VERSION', '1.0.0');
define('TREASURER',   'Carmen Santos'); // Default treasurer name

// ── Session & Security ────────────────────────────────────────
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('ITEMS_PER_PAGE',  10);   // Pagination

// ── Connect to MySQL ──────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Show friendly error; never expose credentials
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
                <h2>Database Connection Error</h2>
                <p>Could not connect to the database. Please check your <code>config.php</code> settings.</p>
                <pre>' . htmlspecialchars($e->getMessage()) . '</pre>
            </div>');
        }
    }

    return $pdo;
}

// ── Helper: current billing month (YYYY-MM-01) ────────────────
function currentBillingMonth(): string {
    return date('Y-m-01');
}

// ── Helper: sanitize output ───────────────────────────────────
function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Helper: format peso amounts ──────────────────────────────
function peso(float $amount): string {
    return '₱' . number_format($amount, 2);
}

// ── Helper: generate next receipt number ─────────────────────
function generateReceiptNo(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare(
        "SELECT receipt_no FROM payments
         WHERE receipt_no LIKE :pattern
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([':pattern' => "MV-$year-%"]);
    $last = $stmt->fetchColumn();

    if ($last) {
        $parts = explode('-', $last);
        $seq   = (int) end($parts) + 1;
    } else {
        $seq = 1;
    }

    return sprintf('MV-%s-%04d', $year, $seq);
}
