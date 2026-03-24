<?php
/**
 * BayanTap – Print Receipt
 * Opens in a new tab, triggers browser print dialog.
 */
require_once 'auth.php';

$pdo    = getDB();
$billId = (int)($_GET['bill_id'] ?? 0);

if (!$billId) {
    die('Invalid bill ID.');
}

$stmt = $pdo->prepare("
    SELECT b.*, h.household_no, r.full_name,
           p.receipt_no, p.payment_date, p.received_by,
           p.resident_signature, p.amount_paid
    FROM bills b
    JOIN households h ON h.id = b.household_id
    JOIN residents  r ON r.household_id = h.id AND r.is_primary = 1
    LEFT JOIN payments p ON p.bill_id = b.id
    WHERE b.id = :id LIMIT 1
");
$stmt->execute([':id' => $billId]);
$r = $stmt->fetch();

if (!$r) {
    die('Bill not found.');
}

$billingLabel = date('F Y', strtotime($r['billing_month']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= esc($r['receipt_no'] ?? 'PENDING') ?> – BayanTap</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem;
            min-height: 100vh;
        }
        .print-wrapper {
            background: white;
            width: 380px;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,.15);
        }
        .print-actions {
            display: flex;
            gap: .75rem;
            margin-bottom: 1.5rem;
            justify-content: flex-end;
        }
        .print-actions button, .print-actions a {
            padding: .5rem 1.25rem;
            border-radius: 6px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-do-print { background: #1a7fd4; color: white; }
        .btn-close { background: #f0f0f0; color: #333; }

        /* Receipt styles */
        .receipt-header { text-align: center; margin-bottom: 1.25rem; }
        .receipt-logo { font-family: 'DM Serif Display', serif; font-size: 1.5rem; color: #1a7fd4; letter-spacing: .05em; }
        .receipt-org { font-size: .75rem; color: #666; margin-top: .2rem; }
        .receipt-tag { font-size: .7rem; color: #999; }
        .dots { color: #ccc; font-size: .75rem; text-align: center; margin: .75rem 0; letter-spacing: 2px; }
        .meta-row { display: flex; justify-content: space-between; font-size: .82rem; margin: .35rem 0; }
        .meta-label { color: #777; }
        .meta-value { font-weight: 600; color: #222; }
        .meta-value.bold { font-size: .9rem; }

        .consumption-box {
            background: #f8faff;
            border: 1px solid #dde8f8;
            border-radius: 8px;
            padding: .75rem 1rem;
            margin: 1rem 0;
        }
        .consumption-box h4 { font-size: .78rem; color: #1a7fd4; margin-bottom: .5rem; font-weight: 600; letter-spacing: .04em; }
        .c-row { display: flex; justify-content: space-between; font-size: .82rem; margin: .25rem 0; color: #555; }
        .c-total { display: flex; justify-content: space-between; font-size: .87rem; font-weight: 700; margin-top: .4rem; padding-top: .4rem; border-top: 1px solid #dde8f8; color: #222; }

        .amount-row { display: flex; justify-content: space-between; align-items: center; background: #1a7fd4; color: white; padding: .75rem 1rem; border-radius: 8px; margin: .75rem 0; }
        .amount-label { font-size: .8rem; font-weight: 500; }
        .amount-value { font-size: 1.2rem; font-weight: 700; }

        .status-box {
            text-align: center;
            padding: .6rem;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .05em;
            margin: .5rem 0;
        }
        .status-paid { background: #e6f9ee; color: #1a7a47; border: 1px solid #b8e8cc; }
        .status-unpaid { background: #fff3e0; color: #c85a00; border: 1px solid #ffd09b; }
        .status-overdue { background: #ffeef0; color: #c0001a; border: 1px solid #ffb3bc; }

        .sigs { display: flex; justify-content: space-between; margin-top: 1rem; }
        .sig { text-align: center; }
        .sig-line { border-top: 1px solid #ccc; padding-top: .4rem; min-width: 120px; }
        .sig-name { font-size: .82rem; font-weight: 600; }
        .sig-label { font-size: .7rem; color: #999; display: block; margin-top: .2rem; }

        .receipt-footer { text-align: center; margin-top: 1rem; font-size: .68rem; color: #aaa; line-height: 1.6; }

        @media print {
            body { background: white; padding: 0; }
            .print-wrapper { box-shadow: none; border-radius: 0; width: 100%; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
<div class="print-wrapper">
    <div class="print-actions">
        <button class="btn-do-print" onclick="window.print()">🖨 Print</button>
        <a href="javascript:window.close()" class="btn-close">✕ Close</a>
    </div>

    <!-- RECEIPT -->
    <div class="receipt-header">
        <div class="receipt-logo">BAYANTAP</div>
        <div class="receipt-org">Marcos Village Water District</div>
        <div class="receipt-tag">Official Payment Receipt</div>
    </div>

    <div class="dots">· · · · · · · · · · · · · · · · · · · ·</div>

    <div class="receipt-meta">
        <div class="meta-row">
            <span class="meta-label">Receipt No:</span>
            <span class="meta-value"><?= esc($r['receipt_no'] ?? 'PENDING') ?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Date:</span>
            <span class="meta-value">
                <?= $r['payment_date'] ? date('F j, Y', strtotime($r['payment_date'])) : '—' ?>
            </span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Billing Period:</span>
            <span class="meta-value"><?= esc($billingLabel) ?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Household No:</span>
            <span class="meta-value"><?= esc($r['household_no']) ?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Name:</span>
            <span class="meta-value bold"><?= esc($r['full_name']) ?></span>
        </div>
    </div>

    <div class="consumption-box">
        <h4>Water Consumption</h4>
        <div class="c-row">
            <span>Previous Reading:</span>
            <span><?= number_format($r['prev_reading']) ?> m³</span>
        </div>
        <div class="c-row">
            <span>Current Reading:</span>
            <span><?= number_format($r['curr_reading']) ?> m³</span>
        </div>
        <div class="c-total">
            <span>Total:</span>
            <span><?= number_format($r['consumption']) ?> m³</span>
        </div>
    </div>

    <div class="amount-row">
        <span class="amount-label">Total Amount</span>
        <span class="amount-value"><?= peso($r['amount']) ?></span>
    </div>

    <?php $sc = $r['status'] === 'paid' ? 'status-paid' : ($r['status'] === 'overdue' ? 'status-overdue' : 'status-unpaid'); ?>
    <div class="status-box <?= $sc ?>">
        <?= strtoupper($r['status'] === 'paid' ? 'Payment Received' : ($r['status'] === 'overdue' ? 'OVERDUE – Requires Payment' : 'Payment Pending')) ?>
    </div>

    <div class="dots" style="margin-top:1rem">· · · · · · · · · · · · · · · · · · · ·</div>

    <div class="sigs">
        <div class="sig">
            <div class="sig-line">
                <div class="sig-name"><?= esc($r['resident_signature'] ?? $r['full_name']) ?></div>
                <span class="sig-label">Resident Signature</span>
            </div>
        </div>
        <div class="sig">
            <div class="sig-line">
                <div class="sig-name"><?= esc($r['received_by'] ?? TREASURER) ?></div>
                <span class="sig-label">Treasurer Signature</span>
            </div>
        </div>
    </div>

    <div class="receipt-footer">
        <p>Official Receipt – BayanTap Water District</p>
        <p>For inquiries: Barangay Hall, Marcos Village, Dagupan City</p>
    </div>
</div>

<script>
// Auto-open print dialog
window.addEventListener('load', function() {
    // Small delay so fonts load
    setTimeout(() => window.print(), 600);
});
</script>
</body>
</html>
