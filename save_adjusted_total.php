<?php
session_start();
require 'db.php';

if ($_SESSION['password'] !== 'acc') {
    http_response_code(403);
    exit('Forbidden');
}

$month = $_POST['month'];
$year = (int)$_POST['year'];
$scope = $_POST['scope']; // account | development | establishment | all
$col   = $_POST['product_col'];
$qty   = (int)$_POST['quantity'];

$stmt = $conn->prepare("
    INSERT INTO adjusted_totals (month, year, scope, product_col, quantity)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
");
$stmt->bind_param("sissi", $month, $year, $scope, $col, $qty);
$stmt->execute();
