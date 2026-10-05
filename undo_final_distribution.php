<?php
session_start();
require 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);

if (!isset($_POST['branch'], $_POST['year'], $_POST['month'])) {
    http_response_code(400);
    exit('Invalid request');
}

$branch = strtoupper(trim($_POST['branch']));
$scope  = strtolower($branch);
$year   = (int)$_POST['year'];
$month  = trim($_POST['month']);

$conn->begin_transaction();

try {

    /* 1️⃣ READ DISTRIBUTED TOTALS */
    $dist = $conn->prepare("
        SELECT product_id, SUM(quantity) AS qty
        FROM distribution_table
        WHERE branch = ? AND year = ? AND month = ?
        GROUP BY product_id
    ");
    $dist->bind_param("sis", $branch, $year, $month);
    $dist->execute();
    $res = $dist->get_result();

    while ($row = $res->fetch_assoc()) {

        $productId  = (int)$row['product_id'];
        $restoreQty = (int)$row['qty'];

        if ($restoreQty <= 0) continue;

        /* 2️⃣ RESTORE STOCK (REVERSE FIFO) */
        $fifo = $conn->prepare("
            SELECT supply_id, quantity, remaining_qty
            FROM supply_table
            WHERE product_id = ?
            ORDER BY supply_date DESC,
                     IFNULL(unit_price, 999999) DESC,
                     supply_id DESC
        ");
        $fifo->bind_param("i", $productId);
        $fifo->execute();
        $rs = $fifo->get_result();

        while ($r = $rs->fetch_assoc()) {

            if ($restoreQty <= 0) break;

            $used = $r['quantity'] - $r['remaining_qty'];
            if ($used <= 0) continue;

            $add = min($used, $restoreQty);

$upd = $conn->prepare("
    UPDATE supply_table
    SET remaining_qty = LEAST(quantity, remaining_qty + ?)
    WHERE supply_id = ?
");
$upd->bind_param("ii", $add, $r['supply_id']);
$upd->execute();
$upd->close();

$restoreQty -= $add;

        }
        $fifo->close();

        if ($restoreQty > 0) {
            throw new Exception("Stock restore mismatch for product $productId");
        }
    }
    $dist->close();

    /* 3️⃣ UNLOCK FINAL DISTRIBUTION */
    $q = $conn->prepare("
        UPDATE final_distribution_status
        SET distributed = 0,
            distributed_date = NULL
        WHERE scope = ? AND year = ? AND month = ?
    ");
    $q->bind_param("sis", $scope, $year, $month);
    $q->execute();
    $q->close();

    /* 4️⃣ DELETE DISTRIBUTION RECORDS */
    $q = $conn->prepare("
        DELETE FROM distribution_table
        WHERE branch = ? AND year = ? AND month = ?
    ");
    $q->bind_param("sis", $branch, $year, $month);
    $q->execute();
    $q->close();

    $conn->commit();
    echo "FINAL DISTRIBUTION UNDO SUCCESS";

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Undo failed: " . $e->getMessage();
}
