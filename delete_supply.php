<?php
require 'db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid ID");

/* Check distribution */
$stmt = $conn->prepare("
    SELECT quantity, remaining_qty
    FROM supply_table
    WHERE supply_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) die("Record not found");

$distributed = $row['quantity'] - $row['remaining_qty'];
if ($distributed > 0) {
    die("Cannot delete. This supply has already been distributed.");
}

/* Delete */
$stmt = $conn->prepare("DELETE FROM supply_table WHERE supply_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: view_supply.php?deleted=1");
exit;
