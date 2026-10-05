<?php
session_start();
include('db.php');

function isMonthFinalDistributed($conn, $branchScope, $year, $month) {
    $stmt = $conn->prepare("
        SELECT distributed
        FROM final_distribution_status
        WHERE scope = ? AND year = ? AND month = ?
        LIMIT 1
    ");
    $stmt->bind_param("sis", $branchScope, $year, $month);
    $stmt->execute();
    $stmt->bind_result($d);
    $stmt->fetch();
    $stmt->close();

    return ((int)$d === 1);
}
$branchScope = strtolower($_SESSION['username']);

if (isMonthFinalDistributed($conn, $branchScope, $_POST['year'], $_POST['month'])) {
    die('This month is FINAL DISTRIBUTED. Submission blocked.');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $branch  = trim($_POST['branch']);
    $name    = trim($_POST['name']);
    $month   = trim($_POST['month']);
    $year    = (int)$_POST['year'];

    $itemValues = $_POST['order']; // e.g., ['Blue Pen'=>2, 'Glue'=>1]

    if(empty($itemValues)){
        die("No items submitted.");
    }

    // 1️⃣ Fetch existing columns
    $existingCols = [];
    $res = $conn->query("SHOW COLUMNS FROM form_data");
    while($row = $res->fetch_assoc()){
        $existingCols[] = $row['Field'];
    }

    // 2️⃣ Add missing columns and prepare POST-only fields
    $itemFields = [];
    $itemValsPrepared = [];

    foreach($itemValues as $product => $qty){
        $col = strtolower(trim(str_replace(' ', '_', $product)));

        // Add column if missing
        if(!in_array($col, $existingCols)){
            $alter = $conn->query("ALTER TABLE form_data ADD COLUMN `$col` INT DEFAULT 0 AFTER `year`");
            if(!$alter) die("Failed to add column `$col`: ".$conn->error);
            $existingCols[] = $col;
        }

        // Only current POST columns
        $itemFields[] = $col;
        $itemValsPrepared[] = (int)$qty;
    }

    // 3️⃣ Check for existing record
    $checkSql = "SELECT * FROM form_data WHERE subject=? AND month=? AND year=? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($checkSql);
    if(!$stmt) die("Prepare failed: ".$conn->error);
    $stmt->bind_param("ssi", $subject, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $existing = $result->fetch_assoc();

        if((int)$existing['approve'] === 1){
            // Approved → INSERT
            $fields = array_merge(['subject','branch','name','month','year'], $itemFields, ['approve','acc_approve','distributed','submitted_at']);
            $placeholders = implode(',', array_fill(0,count($fields),'?'));
            $insertSql = "INSERT INTO form_data (".implode(',', $fields).") VALUES ($placeholders)";
            $stmt = $conn->prepare($insertSql);
            if(!$stmt) die("Prepare failed: ".$conn->error);

            $values = array_merge([$subject,$branch,$name,$month,$year], $itemValsPrepared, [0,0,0,date('Y-m-d H:i:s')]);
            $types = "ssssi" . str_repeat('i', count($itemFields)) . "iiis";
            $stmt->bind_param($types,...$values);

        } else {
            // Unapproved → UPDATE
            $updateParts = [];
            $updateValues = [];

            foreach($itemFields as $i => $field){
                $updateParts[] = "$field=?";
                $updateValues[] = (int)$itemValsPrepared[$i];

            }

            $updateParts[] = "name=?";
            $updateValues[] = $name;
            $updateParts[] = "approve=0";
            $updateSql = "UPDATE form_data SET ".implode(", ", $updateParts)." WHERE id=?";
            $updateValues[] = $existing['id'];

            $stmt = $conn->prepare($updateSql);
            if(!$stmt) die("Prepare failed: ".$conn->error);

            $types = str_repeat('i', count($itemFields)) . "si";
            $stmt->bind_param($types, ...$updateValues);
        }

    } else {
        // No previous record → INSERT
        $fields = array_merge(['subject','branch','name','month','year'], $itemFields, ['approve','acc_approve','distributed','submitted_at']);
        $placeholders = implode(',', array_fill(0,count($fields),'?'));
        $insertSql = "INSERT INTO form_data (".implode(',', $fields).") VALUES ($placeholders)";
        $stmt = $conn->prepare($insertSql);
        if(!$stmt) die("Prepare failed: ".$conn->error);

        $values = array_merge([$subject,$branch,$name,$month,$year], $itemValsPrepared, [0,0,0,date('Y-m-d H:i:s')]);
        $types = "ssssi" . str_repeat('i', count($itemFields)) . "iiis";
        $stmt->bind_param($types,...$values);
    }

    // 4️⃣ Execute
    if($stmt->execute()){
        echo "<script>alert('Order submitted successfully!'); window.location.href='view_order.php';</script>";
    } else {
        die("Execution failed: ".$stmt->error);
    }
}
?>
