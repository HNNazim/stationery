<?php
session_start();
require 'db.php';

if (!isset($_POST['id'], $_POST['val'])) {
    http_response_code(400);
    exit('Invalid request');
}

$id  = (int)$_POST['id'];
$val = ($_POST['val'] == 1) ? 1 : 0;

/* ---------------------------------------
   Get branch + month + year
--------------------------------------- */
$q = $conn->prepare("
    SELECT branch, year, month
    FROM form_data
    WHERE id = ?
");
$q->bind_param("i", $id);
$q->execute();
$row = $q->get_result()->fetch_assoc();
$q->close();

if (!$row) {
    http_response_code(404);
    exit('Order not found');
}

/* ---------------------------------------
   🔒 FINAL DISTRIBUTION LOCK
--------------------------------------- */
$lock = $conn->prepare("
    SELECT 1
    FROM final_distribution_status
    WHERE scope = ?
      AND year = ?
      AND month = ?
      AND distributed = 1
");
$lock->bind_param(
    "sis",
    $row['branch'],
    $row['year'],
    $row['month']
);
$lock->execute();

if ($lock->get_result()->num_rows > 0) {
    http_response_code(403);
    exit('Final distribution locked');
}
$lock->close();

/* ---------------------------------------
   Update allowed
--------------------------------------- */
$stmt = $conn->prepare(
    "UPDATE form_data SET distributed = ? WHERE id = ?"
);
$stmt->bind_param('ii', $val, $id);

if ($stmt->execute()) {
    echo 'success';
} else {
    http_response_code(500);
    echo 'error';
}
$stmt->close();
