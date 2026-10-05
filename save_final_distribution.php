<?php
session_start();
require 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =====================================================
   1️⃣ VALIDATION
===================================================== */
if (empty($_POST['branch']) || empty($_POST['year']) || empty($_POST['month'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$rawBranch = strtolower(trim($_POST['branch']));
$year  = (int)$_POST['year'];
$month = trim($_POST['month']);

/* =====================================================
   2️⃣ NORMALIZE BRANCH → SCOPE
===================================================== */
if (str_contains($rawBranch, 'acc')) {
    $scope  = 'account';
    $branch = 'ACCOUNT';
} elseif (str_contains($rawBranch, 'est')) {
    $scope  = 'establishment';
    $branch = 'ESTABLISHMENT';
} elseif (str_contains($rawBranch, 'dev')) {
    $scope  = 'development';
    $branch = 'DEVELOPMENT';
} else {
    http_response_code(400);
    exit('Invalid branch');
}

/* =====================================================
   3️⃣ BACKEND SAFETY CHECK
   (At least ONE distributed row must exist)
===================================================== */
$chk = $conn->prepare("
    SELECT COUNT(*) c
    FROM form_data
    WHERE branch = ?
      AND year = ?
      AND month = ?
      AND distributed = 1
");
$chk->bind_param("sis", $branch, $year, $month);
$chk->execute();
$count = $chk->get_result()->fetch_assoc()['c'];
$chk->close();

if ($count == 0) {
    http_response_code(400);
    exit('No distributed rows');
}

/* =====================================================
   4️⃣ GET PRODUCT COLUMNS FROM form_data
===================================================== */
$itemCols = [];
$res = $conn->query("SHOW COLUMNS FROM form_data");

while ($r = $res->fetch_assoc()) {
    $f = $r['Field'];
    if (!in_array($f, [
        'id','branch','month','year',
        'approve','acc_approve','distributed',
        'name','subject','submitted_at'
    ])) {
        $itemCols[] = $f;
    }
}

/* =====================================================
   5️⃣ LOAD PRODUCT COLUMN → PRODUCT ID MAP
===================================================== */
$productMap = [];
$p = $conn->query("SELECT product_id, product_name FROM products_table");

while ($row = $p->fetch_assoc()) {
    $col = strtolower(str_replace(' ', '_', $row['product_name']));
    $productMap[$col] = (int)$row['product_id'];
}

/* =====================================================
   6️⃣ LOAD ADJUSTED TOTALS (OPTIONAL OVERRIDE)
===================================================== */
$adjusted = [];

$adj = $conn->prepare("
    SELECT product_col, quantity
    FROM adjusted_totals
    WHERE scope = ? AND year = ? AND month = ?
");
$adj->bind_param("sis", $scope, $year, $month);
$adj->execute();
$ar = $adj->get_result();

while ($r = $ar->fetch_assoc()) {
    $adjusted[$r['product_col']] = (int)$r['quantity'];
}
$adj->close();

/* =====================================================
   7️⃣ CALCULATE ACCOUNT APPROVED TOTALS
===================================================== */
$approvedTotals = array_fill_keys($itemCols, 0);

$q = $conn->prepare("
    SELECT *
    FROM form_data
    WHERE branch = ?
      AND year = ?
      AND month = ?
      AND acc_approve = 1
");
$q->bind_param("sis", $branch, $year, $month);
$q->execute();
$rs = $q->get_result();

while ($row = $rs->fetch_assoc()) {
    foreach ($itemCols as $col) {
        $approvedTotals[$col] += (int)$row[$col];
    }
}
$q->close();

/* =====================================================
   8️⃣ FINAL TOTALS (ADJUSTED > APPROVED)
===================================================== */
$finalTotals = [];

foreach ($itemCols as $col) {
    if (array_key_exists($col, $adjusted)) {
        $finalTotals[$col] = $adjusted[$col];   // override (even zero)
    } else {
        $finalTotals[$col] = $approvedTotals[$col];
    }
}

/* =====================================================
   9️⃣ SAVE INTO distribution_table
===================================================== */
$ins = $conn->prepare("
    INSERT INTO distribution_table
        (product_id, year, month, quantity, branch, order_id, distributed_date, created_at)
    VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        quantity = VALUES(quantity),
        distributed_date = NOW()
");

foreach ($finalTotals as $col => $qty) {

    if ($qty <= 0) continue;
    if (!isset($productMap[$col])) continue;

    $productId = $productMap[$col];

    $ins->bind_param(
        "iisis",
        $productId,
        $year,
        $month,
        $qty,
        $branch
    );

    $ins->execute();
}
$ins->close();

/* =====================================================
   9️⃣A DEDUCT STOCK FROM supply_table (FIFO + lowest price)
===================================================== */
$conn->begin_transaction();

try {

    foreach ($finalTotals as $col => $qty) {

        if ($qty <= 0) continue;
        if (!isset($productMap[$col])) continue;

        $productId = $productMap[$col];
        $remainingToDeduct = $qty;

        $fifo = $conn->prepare("
            SELECT supply_id, remaining_qty
            FROM supply_table
            WHERE product_id = ?
              AND remaining_qty > 0
            ORDER BY supply_date ASC,
                     IFNULL(unit_price, 999999) ASC,
                     supply_id ASC
        ");
        $fifo->bind_param("i", $productId);
        $fifo->execute();
        $res = $fifo->get_result();

        while ($row = $res->fetch_assoc()) {

            if ($remainingToDeduct <= 0) break;

            $useQty = min($row['remaining_qty'], $remainingToDeduct);

            $upd = $conn->prepare("
                UPDATE supply_table
                SET remaining_qty = remaining_qty - ?
                WHERE supply_id = ?
            ");
            $upd->bind_param("ii", $useQty, $row['supply_id']);
            $upd->execute();
            $upd->close();

            $remainingToDeduct -= $useQty;
        }
        $fifo->close();

        if ($remainingToDeduct > 0) {
            throw new Exception("Insufficient stock for product ID $productId");
        }
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    exit($e->getMessage());
}

/* =====================================================
   🔟 UPDATE FINAL DISTRIBUTION STATUS
===================================================== */
$st = $conn->prepare("
    INSERT INTO final_distribution_status
        (scope, year, month, distributed, distributed_date, created_at)
    VALUES (?, ?, ?, 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        distributed = 1,
        distributed_date = NOW()
");
$st->bind_param("sis", $scope, $year, $month);
$st->execute();
$st->close();

echo 'OK';
