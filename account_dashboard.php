<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username'], $_SESSION['password'])) {
    header('Location: login.php');
    exit;
}

$branch = strtolower($_SESSION['username']);
$role = strtolower($_SESSION['password']);

// Fetch latest month & year from form_data
$latestQuery = "SELECT month, year 
                FROM form_data 
                ORDER BY year DESC, 
                FIELD(month,'December','November','October','September','August','July','June','May','April','March','February','January') 
                LIMIT 1";
$resultLatest = $conn->query($latestQuery);
$latest = $resultLatest->fetch_assoc();

$month = $_POST['month'] ?? $latest['month'] ?? date('F');
$year  = $_POST['year']  ?? $latest['year']  ?? date('Y');

// Get item columns
$itemCols = [];
$resultCols = $conn->query("SHOW COLUMNS FROM form_data");
while ($row = $resultCols->fetch_assoc()) {
    $colName = $row['Field'];
    if (!in_array($colName, ['id','branch','month','year','approve','name','subject','created_at','acc_approve','submitted_at','distributed'])) {
        $itemCols[] = $colName;
    }
}

// Fetch orders for this branch
$query = "SELECT * FROM form_data 
          WHERE branch = ? AND month = ? AND year = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $branch, $month, $year);


$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);


// For Account branch: fetch EST and DEV approved orders
$estApproved = $devApproved = [];
if ($branch === 'account' && $role === 'acc') {
    // EST orders
    $stmtEst = $conn->prepare("SELECT * FROM form_data WHERE branch = 'establishment' AND approve != 0 AND month = ? AND year = ? ORDER BY acc_approve ASC, id DESC");
    $stmtEst->bind_param("si", $month, $year);
    $stmtEst->execute();
    $estApproved = $stmtEst->get_result()->fetch_all(MYSQLI_ASSOC);

    // DEV orders
    $stmtDev = $conn->prepare("SELECT * FROM form_data WHERE branch = 'development' AND approve != 0 AND month = ? AND year = ? ORDER BY acc_approve ASC, id DESC");
    $stmtDev->bind_param("si", $month, $year);
    $stmtDev->execute();
    $devApproved = $stmtDev->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Separate pending and approved for this branch (BASED ON acc_approve)
$approvedOrders = $pendingOrders = [];
foreach ($orders as $order) {

    // ❌ accountant should NOT see unapproved by branch head
    if (empty($order['approve'])) {
        continue;
    }

    if (!empty($order['acc_approve'])) {
        $approvedOrders[] = $order;
    } else {
        $pendingOrders[] = $order;
    }
}


// Determine used columns (values > 0) across all orders
$allOrders = array_merge($orders, $estApproved, $devApproved);
$usedColsAssoc = [];
foreach ($allOrders as $order) {
    foreach ($itemCols as $col) {
        if (!empty($order[$col]) && (int)$order[$col] > 0) $usedColsAssoc[$col] = true;
    }
}
$usedCols = array_keys($usedColsAssoc);


function renderTable($orders, $usedCols, $title, $showAccountApproval = false, $allowEdit = false) {
    if (empty($orders)) {
        echo "<div class='no-data'>No $title orders found.</div>";
        return [];
    }

    echo "<h2>$title Orders</h2>";
    echo "<div class='table-wrapper'><table><thead><tr>
        <th>Order No</th><th>Name</th><th>Subject</th>";

    foreach ($usedCols as $col) {
        echo "<th>" . ucfirst(str_replace('_',' ', $col)) . "</th>";
    }

    // ✅ ALWAYS show Approve column
    echo "<th>Approve</th>";

    // ✅ Show Accountant Approval when required
    if ($showAccountApproval) {
        echo "<th>Accountant Approval<br>
              <input type='checkbox' class='select-acc-all' title='Select/Deselect All'>
              </th>";
    }

    echo "<th>Submitted At</th>";
    echo "<th>Distributed</th>";

    echo "</tr></thead><tbody>";

    $totals = array_fill_keys($usedCols, 0);

    foreach ($orders as $order) {
        echo "<tr>
                <td>{$order['id']}</td>
                <td>" . htmlspecialchars($order['name']) . "</td>
                <td>" . htmlspecialchars($order['subject']) . "</td>";

        foreach ($usedCols as $col) {
            $qty = (int)$order[$col];
            $isEditable = $allowEdit; // editable ONLY when explicitly allowed

                if ($isEditable) {
                    echo "<td>
                        <input type='number'
                            class='item-input'
                            min='0'
                            value='$qty'
                            data-id='{$order['id']}'
                            data-col='$col'>
                    </td>";
                } else {
                    echo "<td>$qty</td>";
                }

            $totals[$col] += $qty;
        }

        // ✅ Approve column (always disabled)
        $approveChecked = !empty($order['approve']) ? 'checked' : '';
        echo "<td>
                <input type='checkbox' class='approve-box'
                       checked='$approveChecked'
                       disabled>
              </td>";

        // ✅ Accountant approval (editable for account)
        if ($showAccountApproval) {
            $accChecked = !empty($order['acc_approve']) ? 'checked' : '';
            echo "<td>
                    <input type='checkbox' class='acc-approve-box'
                           data-id='{$order['id']}'
                           data-branch='{$order['branch']}'
                           $accChecked>
                  </td>";
        }

       // Submitted At (read-only)
        $submittedAt = !empty($order['submitted_at']) ? $order['submitted_at'] : '-';
        echo "<td>$submittedAt</td>";

        // Distributed (read-only)
        $distributed = !empty($order['distributed']) ? $order['distributed'] : '-';
        echo "<td>$distributed</td>";

        echo "</tr>";
    }

    // Totals row
    echo "<tr class='totrow'><td colspan='3'>TOTAL</td>";
    foreach ($usedCols as $col) echo "<td>{$totals[$col]}</td>";
    echo "<td></td>";
    if ($showAccountApproval) echo "<td></td>";
    echo "<td></td><td></td>";
    echo "</tr></tbody></table></div>";

    return $totals;
}

$productMap = [];

// map: form_data column name → product_id
$res = $conn->query("SELECT product_id, product_name FROM products_table");
while ($r = $res->fetch_assoc()) {
    $colName = strtolower(str_replace(' ', '_', $r['product_name']));
    $productMap[$colName] = (int)$r['product_id'];
}

$availableQty = array_fill_keys($usedCols, 0);

foreach ($usedCols as $col) {

    // map form_data column → product_id
    if (!isset($productMap[$col])) continue;

    $pid = $productMap[$col];

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(remaining_qty), 0) AS qty
        FROM supply_table
        WHERE product_id = ? AND year = ?
    ");
    $stmt->bind_param("ii", $pid, $year);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $availableQty[$col] = (int)$row['qty'];
    }

    $stmt->close();
}

$adjusted = [];

$stmt = $conn->prepare("
    SELECT scope, product_col, quantity
    FROM adjusted_totals
    WHERE month = ? AND year = ?
");
$stmt->bind_param("si", $month, $year);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $adjusted[$r['scope']][$r['product_col']] = (int)$r['quantity'];
}
$stmt->close();

$hasAdjustment = false;

$chk = $conn->prepare("
    SELECT 1 
    FROM adjusted_totals 
    WHERE month = ? AND year = ?
    LIMIT 1
");
$chk->bind_param("si", $month, $year);
$chk->execute();
$chk->store_result();

if ($chk->num_rows > 0) {
    $hasAdjustment = true;
}
$chk->close();

function isMonthFinalDistributed($conn, $branch, $year, $month) {
    $stmt = $conn->prepare("
        SELECT distributed
        FROM final_distribution_status
        WHERE scope = ? AND year = ? AND month = ?
        LIMIT 1
    ");
    $stmt->bind_param("sis", strtolower($branch), $year, $month);
    $stmt->execute();
    $stmt->bind_result($d);
    $stmt->fetch();
    $stmt->close();

    return ((int)$d === 1);
}

$isAccountFinal = isMonthFinalDistributed($conn, 'account', $year, $month);
$isDevFinal     = isMonthFinalDistributed($conn, 'development', $year, $month);
$isEstFinal     = isMonthFinalDistributed($conn, 'establishment', $year, $month);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stationary Requests - <?= htmlspecialchars($branch) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Add your CSS & Font Awesome links here -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Calibri', sans-serif;
            background: linear-gradient(to bottom right, #ece9f0, #e4dcfa);
            background-image: url('img1.jpg');
            display: flex;
            flex-direction: column;
            font-family: Calibri, sans-serif;
            background-size: cover;
            background-color: #f7f3fc;
            margin: 0;
            height: 100%;
            justify-content: center;
            align-items: center;
            color: #333;
        }

        header {
            text-align: center;
            color: white;
            padding: 30px 20px;
        }

        .logo {
            width: 80px;
            margin-bottom: 10px;
        }

        .container {
            flex: 1;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin: 20px auto;
            width: 90%;
            max-width: 1100px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #ffffff;
            font-size: 28px;
            margin-bottom: 10px;
        }

        form {
           flex: 1;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin: 20px auto;
            width: 90%;
            max-width: 1100px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        select, button {
            padding: 10px 15px;
            font-size: 16px;
            margin: 0 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button {
            background: #5e35b1;
            color: white;
            cursor: pointer;
            transition: 0.3s ease;
        }

        button:hover {
            background: #311b92;
        }

        .button {
            background-color: #5e35b1;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #311b92;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: center;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
        }

        th {
            background: #7e57c2;
            color: white;
        }

        .no-data {
            text-align: center;
            color: #b71c1c;
            font-weight: bold;
            padding: 15px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .backbtn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 10px 15px;
            background: transparent;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
            position: absolute; /* Position the button absolutely */
            top: 10px; /* Optional, adjust for spacing from top */
            left: 10px; /* Align to the right */
            z-index: 10; /* Ensure the button is on top of other elements */
        }

        .backbtn:hover {
            background-color: #5e35b1;
        }

        #select-all {
        transform: scale(1.2);
        cursor: pointer;
        }

        .table-wrapper {
        width: 100%;
        overflow-x: auto;
        margin-top: 20px;
        border-radius: 8px;
        border: 1px solid #ddd;
        }

        .table-wrapper table {
        width: max-content;
        min-width: 100%;
        }

        .section-title {
            color: black;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 30px;
            font-weight: bold;          /* make it bold */
            border-bottom: 2px solid #7e57c2;  /* optional underline for emphasis */
        }

        .total-input {
            width: 70px;
            padding: 5px;
            text-align: center;
            border-radius: 6px;
            border: 1px solid #aaa;
        }

        .total-input:focus {
            outline: none;
            border-color: #5e35b1;
            box-shadow: 0 0 5px rgba(94,53,177,0.5);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .tab-btn {
            padding: 10px 16px;
            border: none;
            background: #7e57c2;
            color: #fff;
            cursor: pointer;
            border-radius: 6px;
        }
        .tab-btn.active {
            background: #311b92;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        .adjusted-row td {
            background: #fff8e1;
            font-weight: bold;
        }

        .adjusted-row td.changed {
            background: #fff3cd;   /* light yellow */
            color: #d32f2f;
            font-weight: 600;
            border: 2px solid #ffc107;
        }

       .changed input {
            color: #d32f2f;   /* red */
            font-weight: 600;
        }
        
    </style>
</head>
<body>

<header>
    <img src="logo1.png" class="logo" alt="Logo">
    <h1>Ministry of Agriculture<br>Stationery Requests - <?= htmlspecialchars($month) ?> <?= htmlspecialchars($year) ?><br>Branch: <?= strtoupper(htmlspecialchars($branch)) ?></h1>

</header>

<div class="container">
    <a href="login.html" class="backbtn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </a>

    <form method="POST" novalidate>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:25px;">
        <label for="month">Month:</label>
        <select name="month" id="month" required>
            <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $m): ?>
            <option value="<?= $m ?>" <?= ($m==$month?'selected':'') ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>

        <label for="year">Year:</label>
        <select name="year" id="year" required>
            <?php for ($y=date('Y');$y>=2022;$y--): ?>
            <option value="<?= $y ?>" <?= ($y==$year?'selected':'') ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit"><i class="fas fa-eye"></i> View</button>
        </div>
    </form>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:25px;">
        <a class="button" href="view_order.php">View Orders</a>
        <a class="button" href="place_order.php">📦 Place Order</a>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="account">Account</button>
        <button class="tab-btn" data-tab="dev">Development</button>
        <button class="tab-btn" data-tab="est">Establishment</button>
        <button class="tab-btn" data-tab="total">Total</button>
    </div>

    <div class="table-wrapper">
    <?php if ($branch==='account' && $role==='acc'): ?>

    <!-- ACCOUNT TAB -->
    <div class="tab-content active" id="account">
        <div class="section-title">Account Branch</div>
        <?php
        if (!empty($pendingOrders)) renderTable($pendingOrders, $usedCols, "Pending", true, false);
        if (!empty($approvedOrders)) renderTable($approvedOrders, $usedCols, "Approved", true, false);;
        ?>
    </div>

    <!-- DEV TAB -->
    <div class="tab-content" id="dev">
        <div class="section-title">Development Branch</div>
        <?php
        if (!empty($devApproved)) {
            $devPending = array_filter($devApproved, fn($o)=>empty($o['acc_approve']));
            $devApprovedAcc = array_filter($devApproved, fn($o)=>!empty($o['acc_approve']));
            if ($devPending) renderTable($devPending, $usedCols, "Pending", true, false);
            if ($devApprovedAcc) renderTable($devApprovedAcc, $usedCols, "Approved", true, false);
        }
        ?>
    </div>

    <!-- EST TAB -->
    <div class="tab-content" id="est">
        <div class="section-title">Establishment Branch</div>
        <?php
        if (!empty($estApproved)) {
            $estPending = array_filter($estApproved, fn($o)=>empty($o['acc_approve']));
            $estApprovedAcc = array_filter($estApproved, fn($o)=>!empty($o['acc_approve']));
            if ($estPending) renderTable($estPending, $usedCols, "Pending", true, false);
            if ($estApprovedAcc) renderTable($estApprovedAcc, $usedCols, "Approved", true, false);
        }
        ?>
    </div>

    
    <!-- TOTAL TAB -->
        <?php
        function adjustedOrBase($scope, $col, $base, $adjusted) {
            return isset($adjusted[$scope][$col])
                ? (int)$adjusted[$scope][$col]
                : (int)$base;
        }

        function calculateTotals($orders, $usedCols) {
            $totals = array_fill_keys($usedCols, 0);
            foreach ($orders as $o) {
                if (empty($o['acc_approve'])) continue;
                foreach ($usedCols as $col) {
                    $totals[$col] += (int)$o[$col];
                }
            }
            return $totals;
        }

        $accTotals = calculateTotals($approvedOrders, $usedCols);
        $devTotals = calculateTotals($devApproved, $usedCols);
        $estTotals = calculateTotals($estApproved, $usedCols);

        // BASE GRAND TOTAL (NEVER CHANGES)
        $grandTotals = [];
        foreach ($usedCols as $col) {
            $grandTotals[$col] =
                $accTotals[$col] +
                $devTotals[$col] +
                $estTotals[$col];
        }
        ?>

        <div class="tab-content" id="total">
        <div class="section-title">Total Summary</div>

        <table>
        <thead>
        <tr>
            <th>Branch</th>
            <?php foreach ($usedCols as $col): ?>
                <th><?= ucfirst(str_replace('_',' ', $col)) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>

        <!-- ================= ACCOUNT ================= -->
        <tr>
        <td><strong>Account</strong></td>
        <?php foreach ($usedCols as $col): ?>
            <td><?= $accTotals[$col] ?></td>
        <?php endforeach; ?>
        </tr>

        <tr class="adjusted-row">
        <td>Adjusted (Account)</td>
        <?php foreach ($usedCols as $col):
            $base = $accTotals[$col];
            $val  = adjustedOrBase('account', $col, $base, $adjusted);
        ?>
        <td class="<?= $val != $base ? 'changed' : '' ?>">
        <input type="number" class="total-input save-total"
            data-scope="account"
            data-col="<?= $col ?>"
            value="<?= $val ?>"
            <?= $isAccountFinal ? 'disabled title="Final distribution locked"' : '' ?>>
        </td>
        <?php endforeach; ?>
        </tr>

        <!-- ================= DEVELOPMENT ================= -->
        <tr>
        <td><strong>Development</strong></td>
        <?php foreach ($usedCols as $col): ?>
            <td><?= $devTotals[$col] ?></td>
        <?php endforeach; ?>
        </tr>

        <tr class="adjusted-row">
        <td>Adjusted (Development)</td>
        <?php foreach ($usedCols as $col):
            $base = $devTotals[$col];
            $val  = adjustedOrBase('development', $col, $base, $adjusted);
        ?>
        <td class="<?= $val != $base ? 'changed' : '' ?>">
        <input type="number" class="total-input save-total"
            data-scope="development"
            data-col="<?= $col ?>"
            value="<?= $val ?>"
            <?= $isDevFinal ? 'disabled title="Final distribution locked"' : '' ?>>
        </td>
        <?php endforeach; ?>
        </tr>

        <!-- ================= ESTABLISHMENT ================= -->
        <tr>
        <td><strong>Establishment</strong></td>
        <?php foreach ($usedCols as $col): ?>
            <td><?= $estTotals[$col] ?></td>
        <?php endforeach; ?>
        </tr>

        <tr class="adjusted-row">
        <td>Adjusted (Establishment)</td>
        <?php foreach ($usedCols as $col):
            $base = $estTotals[$col];
            $val  = adjustedOrBase('establishment', $col, $base, $adjusted);
        ?>
        <td class="<?= $val != $base ? 'changed' : '' ?>">
        <input type="number" class="total-input save-total"
            data-scope="establishment"
            data-col="<?= $col ?>"
            value="<?= $val ?>"
            <?= $isEstFinal ? 'disabled title="Final distribution locked"' : '' ?>>
        </td>
        <?php endforeach; ?>
        </tr>

        <!-- ================= GRAND TOTAL (BASE) ================= -->
        <tr>
        <td><strong>GRAND TOTAL</strong></td>
        <?php foreach ($usedCols as $col): ?>
            <td><?= $grandTotals[$col] ?></td>
        <?php endforeach; ?>
        </tr>

        <!-- ================= FINAL APPROVED (READ ONLY) ================= -->
        <tr class="adjusted-row">
        <td><strong>FINAL APPROVED</strong></td>
        <?php foreach ($usedCols as $col):

            $final =
                adjustedOrBase('account', $col, $accTotals[$col], $adjusted)
            + adjustedOrBase('development', $col, $devTotals[$col], $adjusted)
            + adjustedOrBase('establishment', $col, $estTotals[$col], $adjusted);

            $changed = ($final != $grandTotals[$col]);
        ?>
        <td class="<?= $changed ? 'changed' : '' ?>">
            <?= $final ?>
        </td>
        <?php endforeach; ?>
        </tr>

        </tbody>
        </table>
        <hr style="margin:40px 0">

        <div class="section-title">Currently Available Quantities</div>

        <div class="table-wrapper">
        <table>
            <tbody>
                <!-- ROW 1: Item names -->
                <tr>
                    <?php foreach ($usedCols as $col): ?>
                        <th><?= ucfirst(str_replace('_',' ', $col)) ?></th>
                    <?php endforeach; ?>
                </tr>

                <!-- ROW 2: Available quantities (EDITABLE) -->
                <tr>
                    <?php foreach ($usedCols as $col): ?>
                        <td>
                            <input type="number"
                            class="total-input"
                            value="<?= (int)$availableQty[$col] ?>"
                            disabled>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
        </div>

        </div>        

    <?php else: ?>

        <div class="no-data">No orders found for <?= "$month $year" ?></div>
    <?php endif; ?>
    </div>
</div>
        </div>

        

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let bulkChanging = false;

    // ===== Account branch: Only allow editing pending acc_approve =====
    $('.acc-approve-box').on('change', function() {
        const cb = $(this);
        const id = cb.data('id');
        const branch = cb.data('branch'); // actual order branch
        const acc_approve = cb.is(':checked') ? 1 : 0;

        $.post('update_acc_approval.php', { id: id, acc_approve: acc_approve, branch: branch }, function() {
            alert('Approval updated.');
        }).fail(function() {
            alert('Failed to update approval.');
            cb.prop('checked', !acc_approve);
        });
    });

    // ===== Account branch cannot change any other checkboxes =====
    $('.approve-box').on('change', function() {
        const cb = $(this);
        if(cb.closest('table').find('.acc-approve-box').length) {
            alert("Account branch cannot edit this field.");
            cb.prop('checked', !cb.prop('checked')); // revert
        }
    });

    // ===== Bulk select for Account branch =====
$('.select-acc-all').on('change', function() {
    const isChecked = $(this).is(':checked');

    // Find all checkboxes in the SAME table body (skip header)
    const table = $(this).closest('table');
    table.find('tbody .acc-approve-box').each(function() {
        const cb = $(this);
        const id = cb.data('id');
        const branch = cb.data('branch');

        // Only update if visually different
        if(cb.prop('checked') !== isChecked) {
            cb.prop('checked', isChecked); // update visually

            // Send AJAX request to update DB
            $.post('update_acc_approval.php', { 
                id: id,
                acc_approve: isChecked ? 1 : 0,
                branch: branch
            })
            .done(function() {
                console.log(`Order ${id} updated`);
            })
            .fail(function() {
                alert(`Failed to update order ${id}`);
                cb.prop('checked', !isChecked); // revert on fail
            });
        }
    });
});

    // ===== Disable bulk select for other approvals for account =====
    $('.select-all').on('change', function() {
        const tableWrapper = $(this).closest('.table-wrapper');
        // Only block if the table contains checkboxes that are NOT acc-approve
        if(tableWrapper.find('.approve-box').length > 0 && tableWrapper.find('.acc-approve-box').length === 0) {
            alert("Account branch cannot approve these orders.");
            $(this).prop('checked', false);
        }
    });

});

$('.save-total').on('change', function () {
    const i = $(this);

    $.post('save_adjusted_total.php', {
        month: '<?= $month ?>',
        year: <?= (int)$year ?>,
        scope: i.data('scope'),
        product_col: i.data('col'),
        quantity: i.val()
    }).fail(() => alert('Save failed'));
});

$('.tab-btn').on('click', function () {
    const tab = $(this).data('tab');

    $('.tab-btn').removeClass('active');
    $(this).addClass('active');

    $('.tab-content').removeClass('active');
    $('#' + tab).addClass('active');
});


</script>

</body>
</html>