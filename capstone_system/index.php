<?php
/**
 * BayanTap – Main Dashboard (Treasurer Portal)
 */
require_once 'auth.php';

$pdo = getDB();

// ── Billing Month Filter ───────────────────────────────────────
$selectedMonth = $_GET['month'] ?? date('Y-m');
$billingMonth  = $selectedMonth . '-01';

// Build list of available months (last 12 months)
$months = [];
for ($i = 0; $i < 12; $i++) {
    $ts       = mktime(0, 0, 0, date('m') - $i, 1, date('Y'));
    $months[] = [
        'value' => date('Y-m', $ts),
        'label' => date('F Y', $ts),
    ];
}

// ── Status Filter ─────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'paid', 'unpaid', 'overdue'];
if (!in_array($statusFilter, $allowedStatuses)) $statusFilter = 'all';

// ── Search Query ──────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');

// ── Pagination ────────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset  = ($page - 1) * $perPage;

// ── Dashboard Summary Cards ───────────────────────────────────
// Total active households
$totalHouseholds = $pdo->query("SELECT COUNT(*) FROM households WHERE is_active = 1")->fetchColumn();

// Stats for current billing month
$stmt = $pdo->prepare("
    SELECT
        COUNT(*)                                        AS total_bills,
        SUM(status = 'paid')                            AS paid_count,
        SUM(status = 'unpaid')                          AS pending_count,
        SUM(status = 'overdue')                         AS overdue_count,
        COALESCE(SUM(amount), 0)                        AS total_amount,
        COALESCE(SUM(CASE WHEN status='paid' THEN amount END), 0) AS paid_amount
    FROM bills
    WHERE billing_month = :bm
");
$stmt->execute([':bm' => $billingMonth]);
$summary = $stmt->fetch();

$paidCount    = (int)$summary['paid_count'];
$pendingCount = (int)$summary['pending_count'];
$overdueCount = (int)$summary['overdue_count'];
$totalBills   = (int)$summary['total_bills'];
$paidPct      = $totalBills > 0 ? round(($paidCount / $totalBills) * 100) : 0;

// Paid vs last month comparison
$lastMonth = date('Y-m-01', strtotime('-1 month', strtotime($billingMonth)));
$lastPaid  = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE billing_month = :lm AND status = 'paid'");
$lastPaid->execute([':lm' => $lastMonth]);
$lastPaidCount = (int)$lastPaid->fetchColumn();
$paidChange    = $lastPaidCount > 0
    ? round((($paidCount - $lastPaidCount) / $lastPaidCount) * 100)
    : 0;

// ── Build Resident Bills Query ─────────────────────────────────
$conditions = ['b.billing_month = :bm'];
$params     = [':bm' => $billingMonth];

if ($statusFilter !== 'all') {
    $conditions[] = 'b.status = :status';
    $params[':status'] = $statusFilter;
}

if ($search !== '') {
    $conditions[] = '(r.full_name LIKE :search OR h.household_no LIKE :search)';
    $params[':search'] = "%$search%";
}

$where = 'WHERE ' . implode(' AND ', $conditions);

// Total count for pagination
$countSql  = "SELECT COUNT(*) FROM bills b
              JOIN households h ON h.id = b.household_id
              JOIN residents  r ON r.household_id = h.id AND r.is_primary = 1
              $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Fetch bills
$billsSql = "
    SELECT
        b.id            AS bill_id,
        b.status,
        b.prev_reading,
        b.curr_reading,
        b.consumption,
        b.amount,
        b.due_date,
        h.id            AS household_id,
        h.household_no,
        r.full_name,
        p.receipt_no,
        p.payment_date,
        p.received_by,
        p.resident_signature
    FROM bills b
    JOIN households h ON h.id = b.household_id
    JOIN residents  r ON r.household_id = h.id AND r.is_primary = 1
    LEFT JOIN payments p ON p.bill_id = b.id
    $where
    ORDER BY b.id ASC
    LIMIT :limit OFFSET :offset
";
$billsStmt = $pdo->prepare($billsSql);
foreach ($params as $k => $v) {
    $billsStmt->bindValue($k, $v);
}
$billsStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$billsStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$billsStmt->execute();
$bills = $billsStmt->fetchAll();

// ── Receipt Preview (first bill or selected) ──────────────────
$previewBillId = (int)($_GET['receipt'] ?? 0);
$receiptData   = null;

if ($previewBillId) {
    $rStmt = $pdo->prepare("
        SELECT b.*, h.household_no, r.full_name, r.resident_signature AS res_sig,
               p.receipt_no, p.payment_date, p.received_by, p.resident_signature,
               p.amount_paid
        FROM bills b
        JOIN households h ON h.id = b.household_id
        JOIN residents  r ON r.household_id = h.id AND r.is_primary = 1
        LEFT JOIN payments p ON p.bill_id = b.id
        WHERE b.id = :id LIMIT 1
    ");
    $rStmt->execute([':id' => $previewBillId]);
    $receiptData = $rStmt->fetch();
} elseif (!empty($bills)) {
    // Auto-preview first paid bill
    foreach ($bills as $b) {
        if ($b['status'] === 'paid' && $b['receipt_no']) {
            $receiptData = $b;
            $receiptData['amount_paid'] = $b['amount'];
            break;
        }
    }
}

// Formatted billing month label
$billingLabel = date('F Y', strtotime($billingMonth));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BayanTap – Treasurer Portal</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

<!-- ═══════════════════════════════════════════════════════════
     TOP NAVIGATION BAR
══════════════════════════════════════════════════════════════ -->
<nav class="topbar">
    <div class="topbar-brand">
        <div class="brand-logo">
            <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M25 3C25 3 8 17.5 8 29C8 38.941 15.611 47 25 47C34.389 47 42 38.941 42 29C42 17.5 25 3 25 3Z" fill="#1a7fd4"/>
                <path d="M25 14C25 14 15 22.5 15 29C15 33.971 19.477 38 25 38C30.523 38 35 33.971 35 29C35 22.5 25 14 25 14Z" fill="white" fill-opacity="0.4"/>
            </svg>
        </div>
        <div class="brand-text">
            <span class="brand-name"><?= APP_NAME ?></span>
            <span class="brand-sub"><?= APP_TAGLINE ?></span>
        </div>
    </div>

    <div class="topbar-center">
        <span class="portal-label">Treasurer Portal</span>
        <span class="portal-month"><?= $billingLabel ?></span>
    </div>

    <div class="topbar-actions">
        <div class="topbar-badge">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode(TREASURER) ?>&background=1a7fd4&color=fff&size=36&bold=true" alt="Avatar" class="user-avatar">
            <div class="user-info">
                <span class="user-name"><?= esc($currentUser['full_name']) ?></span>
                <span class="user-role"><?= ucfirst($currentUser['role']) ?></span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Log Out</a>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════════════
     HERO BANNER WITH SUMMARY CARDS
══════════════════════════════════════════════════════════════ -->
<section class="hero-banner">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="summary-cards">

            <!-- Card: Total Households -->
            <div class="summary-card">
                <div class="card-header">
                    <div class="card-icon">🏘️</div>
                    <span class="card-badge badge-blue">Total</span>
                </div>
                <div class="card-title">Total Households</div>
                <div class="card-value"><?= number_format($totalHouseholds) ?></div>
                <div class="card-sub">
                    <span class="trend-up">↗</span> Active Accounts
                </div>
            </div>

            <!-- Card: Paid This Month -->
            <div class="summary-card">
                <div class="card-header">
                    <div class="card-icon">✅</div>
                    <span class="card-badge badge-green"><?= $paidPct ?>%</span>
                </div>
                <div class="card-title">Paid This Month</div>
                <div class="card-value"><?= number_format($paidCount) ?></div>
                <div class="card-sub">
                    <?php if ($paidChange >= 0): ?>
                        <span class="trend-up">↗</span>
                        <span class="trend-green"><?= $paidChange ?>% from last month</span>
                    <?php else: ?>
                        <span class="trend-dn">↘</span>
                        <span class="trend-red"><?= abs($paidChange) ?>% from last month</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card: Pending -->
            <div class="summary-card">
                <div class="card-header">
                    <div class="card-icon">⏳</div>
                    <span class="card-badge badge-orange"><?= $totalBills > 0 ? round(($pendingCount/$totalBills)*100) : 0 ?>%</span>
                </div>
                <div class="card-title">Pending</div>
                <div class="card-value"><?= number_format($pendingCount) ?></div>
                <div class="card-sub">
                    <span class="trend-orange">Awaiting Collection</span>
                </div>
            </div>

            <!-- Card: Overdue -->
            <div class="summary-card">
                <div class="card-header">
                    <div class="card-icon">🚨</div>
                    <span class="card-badge badge-red"><?= $totalBills > 0 ? round(($overdueCount/$totalBills)*100) : 0 ?>%</span>
                </div>
                <div class="card-title">Overdue</div>
                <div class="card-value"><?= number_format($overdueCount) ?></div>
                <div class="card-sub">
                    <span class="trend-red">Requires follow-up</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     SEARCH & FILTER BAR
══════════════════════════════════════════════════════════════ -->
<section class="filter-bar">
    <form method="GET" action="index.php" class="filter-form" id="filterForm">
        <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                name="q"
                id="searchInput"
                class="search-input"
                placeholder="Search by name or household number..."
                value="<?= esc($search) ?>"
                autocomplete="off"
            >
            <?php if ($search): ?>
                <a href="index.php?month=<?= esc($selectedMonth) ?>&status=<?= esc($statusFilter) ?>" class="search-clear">✕</a>
            <?php endif; ?>
        </div>

        <div class="filter-dropdowns">
            <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"    <?= $statusFilter==='all'    ? 'selected':'' ?>>All Status</option>
                <option value="paid"   <?= $statusFilter==='paid'   ? 'selected':'' ?>>Paid</option>
                <option value="unpaid" <?= $statusFilter==='unpaid' ? 'selected':'' ?>>Unpaid</option>
                <option value="overdue"<?= $statusFilter==='overdue'? 'selected':'' ?>>Overdue</option>
            </select>

            <select name="month" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <?php foreach ($months as $m): ?>
                    <option value="<?= esc($m['value']) ?>" <?= $m['value']===$selectedMonth?'selected':'' ?>>
                        <?= esc($m['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</section>

<!-- ═══════════════════════════════════════════════════════════
     MAIN CONTENT: TABLE + RECEIPT PANEL
══════════════════════════════════════════════════════════════ -->
<main class="main-content">

    <!-- ── Resident Bills Table ───────────────────────────── -->
    <section class="table-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">Resident Bills</h2>
                <p class="section-sub"><?= $billingLabel ?> &middot; Marcos Village</p>
            </div>
            <span class="result-count"><?= $totalRows ?> record<?= $totalRows !== 1 ? 's' : '' ?></span>
        </div>

        <div class="table-wrapper">
            <table class="bills-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>HOUSEHOLD</th>
                        <th>NAME</th>
                        <th>USAGE</th>
                        <th>AMOUNT</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bills)): ?>
                    <tr>
                        <td colspan="7" class="empty-row">
                            <div class="empty-state">
                                <span>📂</span>
                                <p>No records found<?= $search ? " for \"" . esc($search) . "\"" : '' ?>.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bills as $i => $bill): ?>
                        <tr class="bill-row <?= $bill['bill_id'] == ($receiptData['bill_id'] ?? 0) ? 'row-active' : '' ?>"
                            data-bill-id="<?= $bill['bill_id'] ?>">
                            <td class="td-num">
                                <span class="row-num"><?= $offset + $i + 1 ?></span>
                            </td>
                            <td class="td-household">
                                <strong><?= esc($bill['household_no']) ?></strong>
                            </td>
                            <td class="td-name">
                                <span class="name-primary"><?= esc($bill['full_name']) ?></span>
                                <span class="name-sub">
                                    <?php if ($bill['payment_date']): ?>
                                        Paid: <?= date('M j, Y', strtotime($bill['payment_date'])) ?>
                                    <?php else: ?>
                                        Pending
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="td-usage">
                                <strong><?= number_format($bill['consumption']) ?> m³</strong>
                                <span class="usage-reading"><?= number_format($bill['prev_reading']) ?> → <?= number_format($bill['curr_reading']) ?></span>
                            </td>
                            <td class="td-amount">
                                <?= peso($bill['amount']) ?>
                            </td>
                            <td class="td-status">
                                <?php
                                $statusClass = match($bill['status']) {
                                    'paid'    => 'badge-status-paid',
                                    'overdue' => 'badge-status-overdue',
                                    default   => 'badge-status-unpaid',
                                };
                                $statusLabel = match($bill['status']) {
                                    'paid'    => 'PAID',
                                    'overdue' => 'OVERDUE',
                                    default   => 'UNPAID',
                                };
                                ?>
                                <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="td-action">
                                <a href="?month=<?= esc($selectedMonth) ?>&status=<?= esc($statusFilter) ?>&q=<?= urlencode($search) ?>&receipt=<?= $bill['bill_id'] ?>#receipt-panel"
                                   class="btn-view <?= $bill['status'] === 'paid' ? '' : 'btn-view-unpaid' ?>">
                                    <?= $bill['status'] === 'paid' ? '🧾 View' : '📋 Details' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $baseUrl = "index.php?month=" . urlencode($selectedMonth)
                     . "&status=" . urlencode($statusFilter)
                     . "&q=" . urlencode($search);
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?>&page=<?= $page-1 ?>" class="page-btn">‹ Prev</a>
            <?php endif; ?>

            <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                <a href="<?= $baseUrl ?>&page=<?= $p ?>"
                   class="page-btn <?= $p === $page ? 'page-active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>&page=<?= $page+1 ?>" class="page-btn">Next ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Receipt Preview Panel ──────────────────────────── -->
    <aside class="receipt-panel" id="receipt-panel">
        <div class="receipt-panel-header">
            <div>
                <div class="receipt-panel-title">RECEIPT REVIEW</div>
                <div class="receipt-panel-sub">Official payment receipt</div>
            </div>
            <?php if ($receiptData): ?>
                <a href="print_receipt.php?bill_id=<?= $receiptData['bill_id'] ?>" target="_blank" class="btn-print">
                    🖨 PRINT
                </a>
            <?php endif; ?>
        </div>

        <?php if ($receiptData): ?>
        <div class="receipt-card">
            <!-- Receipt Header -->
            <div class="receipt-header">
                <div class="receipt-brand-name">BAYANTAP</div>
                <div class="receipt-brand-sub">Marcos Village Water District</div>
                <div class="receipt-brand-tagline">Official Payment Receipt</div>
                <div class="receipt-dots">· · · · · · · · · · · · · · · · · · · ·</div>
            </div>

            <!-- Receipt Meta -->
            <div class="receipt-meta">
                <div class="receipt-row">
                    <span class="receipt-label">Receipt No:</span>
                    <span class="receipt-value receipt-mono"><?= esc($receiptData['receipt_no'] ?? 'PENDING') ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Date:</span>
                    <span class="receipt-value">
                        <?= $receiptData['payment_date']
                            ? date('F j, Y', strtotime($receiptData['payment_date']))
                            : '—' ?>
                    </span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Household No:</span>
                    <span class="receipt-value"><?= esc($receiptData['household_no']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Name:</span>
                    <span class="receipt-value receipt-bold"><?= esc($receiptData['full_name']) ?></span>
                </div>
            </div>

            <!-- Water Consumption Box -->
            <div class="receipt-consumption">
                <div class="consumption-title">Water Consumption</div>
                <div class="consumption-row">
                    <span>Previous Reading:</span>
                    <span class="receipt-mono"><?= number_format($receiptData['prev_reading']) ?> m³</span>
                </div>
                <div class="consumption-row">
                    <span>Current Reading:</span>
                    <span class="receipt-mono"><?= number_format($receiptData['curr_reading']) ?> m³</span>
                </div>
                <div class="consumption-total">
                    <span>Total:</span>
                    <span class="receipt-mono"><?= number_format($receiptData['consumption']) ?> m³</span>
                </div>
            </div>

            <!-- Amount -->
            <div class="receipt-amount-row">
                <span class="amount-label">Total Amount:</span>
                <span class="amount-value"><?= peso($receiptData['amount']) ?></span>
            </div>

            <!-- Payment Status -->
            <?php if ($receiptData['status'] === 'paid'): ?>
                <div class="receipt-status paid-status">Payment Received</div>
            <?php elseif ($receiptData['status'] === 'overdue'): ?>
                <div class="receipt-status overdue-status">⚠ Overdue – Please Pay Now</div>
            <?php else: ?>
                <div class="receipt-status unpaid-status">Payment Pending</div>
            <?php endif; ?>

            <!-- Signature Line -->
            <div class="receipt-dots" style="margin-top:1rem">· · · · · · · · · · · · · · · · · · · ·</div>
            <div class="receipt-signatures">
                <div class="sig-block">
                    <span class="sig-label">Resident Signature</span>
                    <span class="sig-name"><?= esc($receiptData['resident_signature'] ?? $receiptData['full_name']) ?></span>
                </div>
                <div class="sig-block sig-right">
                    <span class="sig-label">Treasurer Signature</span>
                    <span class="sig-name"><?= esc($receiptData['received_by'] ?? TREASURER) ?></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
                <p>Official Receipt – BayanTap Water District</p>
                <p>For inquiries: Barangay Hall, Marcos Village</p>
            </div>
        </div>

        <?php else: ?>
        <div class="receipt-empty">
            <div class="receipt-empty-icon">🧾</div>
            <p>Select a bill to preview its receipt</p>
        </div>
        <?php endif; ?>
    </aside>

</main>

<!-- ═══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
    <p>BayanTap &copy; <?= date('Y') ?> &nbsp;·&nbsp; Marcos Village Water District &nbsp;·&nbsp; Dagupan City, Pangasinan</p>
</footer>

<script src="dashboard.js"></script>
</body>
</html>
