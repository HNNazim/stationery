<?php
require 'db.php';

$id = isset($_POST['supply_id']) ? (int)$_POST['supply_id'] : 0;

$product_id = (int)$_POST['product_id'];
$type       = $_POST['supply_type'];
$date       = $_POST['supply_date'];
$quantities = $_POST['quantity'];      // ARRAY
$prices     = $_POST['unit_price'];    // ARRAY
$source     = $_POST['source'] ?? null;
$remarks    = $_POST['remarks'] ?? null;

$year  = (int)date('Y', strtotime($date));
$month = (int)date('n', strtotime($date));

/* ===============================
   UPDATE (single row only)
================================ */
if ($id > 0) {

    // Only allow update if not distributed (you already check earlier)
    $qty   = (int)$quantities[0];
    $price = ($prices[0] !== '' && $prices[0] !== null) ? (float)$prices[0] : null;
    $total = ($price !== null) ? $qty * $price : null;

    $stmt = $conn->prepare("
        UPDATE supply_table
        SET quantity=?, remaining_qty=?, unit_price=?, total_value=?, source=?, remarks=?
        WHERE supply_id=?
    ");

    $stmt->bind_param(
        "iiddssi",
        $qty,
        $qty,
        $price,
        $total,
        $source,
        $remarks,
        $id
    );

    $stmt->execute();
}
/* ===============================
   INSERT (MULTI PRICE ROWS)
================================ */
else {

    $stmt = $conn->prepare("
        INSERT INTO supply_table
        (product_id, supply_date, year, month, supply_type,
         quantity, remaining_qty, unit_price, total_value, source, remarks)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");

    for ($i = 0; $i < count($quantities); $i++) {

        $qty = (int)$quantities[$i];

        $price = ($prices[$i] !== '' && $prices[$i] !== null) ? (float)$prices[$i] : null;
        $total = ($price !== null) ? $qty * $price : null;

        $stmt->bind_param(
            "isiisiiddss",
            $product_id,
            $date,
            $year,
            $month,
            $type,
            $qty,
            $qty,
            $price,
            $total,
            $source,
            $remarks
        );

        $stmt->execute();
    }
}

$return = $_POST['return'] ?? 'view_supply.php';
if (strpos($return, '?') !== false) {
    header("Location: $return&success=1");
} else {
    header("Location: $return?success=1");
}
exit;

