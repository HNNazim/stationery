<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);
    $acc_approve = (int)($_POST['acc_approve'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        exit('Invalid ID');
    }

    /* ---------------------------------------
       Get order info
    --------------------------------------- */
    $q = $conn->prepare("
        SELECT branch, year, month, distributed
        FROM form_data
        WHERE id = ?
    ");
    $q->bind_param("i", $id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();

    /* ---------------------------------------
   🔒 ORDER DISTRIBUTED LOCK
--------------------------------------- */
    if ((int)$row['distributed'] === 1) {
        http_response_code(403);
        exit('Order already distributed');
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
       Update
    --------------------------------------- */
    $stmt = $conn->prepare(
        "UPDATE form_data SET acc_approve = ? WHERE id = ?"
    );
    $stmt->bind_param("ii", $acc_approve, $id);
    $stmt->execute();
    $stmt->close();

    echo "Account approval updated";
}
