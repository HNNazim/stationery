<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);
    $approve = isset($_POST['approve']) ? (int)$_POST['approve'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        exit('Invalid ID');
    }

    // 🔒 Check accountant approval (STRICT check)
    $check = $conn->prepare(
        "SELECT acc_approve FROM form_data WHERE id = ?"
    );
    $check->bind_param("i", $id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    // ❌ Lock if accountant already approved
    if (isset($row['acc_approve']) && (int)$row['acc_approve'] === 1) {
        http_response_code(403);
        exit('Locked by accountant approval');
    }

    // ✅ Allow approve = 1 OR approve = 0 when acc_approve = 0
    $stmt = $conn->prepare(
        "UPDATE form_data SET approve = ? WHERE id = ?"
    );
    $stmt->bind_param("ii", $approve, $id);

    if ($stmt->execute()) {
        echo "Branch approval updated";
    } else {
        http_response_code(500);
        echo "Database error";
    }

    $stmt->close();
}
?>
