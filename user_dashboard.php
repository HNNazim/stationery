<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username'], $_SESSION['password'])) {
    header('Location: login.php');
    exit;
}

$branch = strtolower($_SESSION['username']);
$role = strtolower($_SESSION['password']);

// Fetch the latest month & year from the database for this branch
$latestQuery = "SELECT month, year 
                FROM form_data 
                WHERE branch = ? 
                ORDER BY year DESC, 
                FIELD(month,'December','November','October','September','August','July','June','May','April','March','February','January') 
                LIMIT 1";
$stmt = $conn->prepare($latestQuery);
$stmt->bind_param("s", $branch);
$stmt->execute();
$result = $stmt->get_result();
$latest = $result->fetch_assoc();

$month = $_POST['month'] ?? $latest['month'] ?? date('F');
$year  = $_POST['year'] ?? $latest['year'] ?? date('Y');

// Fetch item columns
$itemCols = [];
$resultCols = $conn->query("SHOW COLUMNS FROM form_data");
while ($row = $resultCols->fetch_assoc()) {
    $colName = $row['Field'];
    if (!in_array($colName, ['id','branch','month','year','approve','name','subject','created_at'])) {
        $itemCols[] = $colName;
    }
}

// Fetch orders for selected month & year
$query = "SELECT * FROM form_data WHERE branch = ? AND month = ? AND year = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $branch, $month, $year);
$stmt->execute();
$resultOrders = $stmt->get_result();
$orders = $resultOrders->fetch_all(MYSQLI_ASSOC);

// Separate approved and pending orders
$approvedOrders = [];
$pendingOrders = [];
foreach ($orders as $order) {
    if (!empty($order['approve'])) {
        $approvedOrders[] = $order;
    } else {
        $pendingOrders[] = $order;
    }
}

// Determine used columns (with values > 0)
$usedCols = [];
foreach ($orders as $order) {
    foreach ($itemCols as $col) {
        if (!empty($order[$col]) && (int)$order[$col] > 0) {
            $usedCols[$col] = true;
        }
    }
}
$usedCols = array_keys($usedCols);

// Now your renderTable function and HTML remain the same


// Initialize totals
$totals = array_fill_keys($usedCols, 0);

function label($col) {
    return ucfirst(str_replace('_', ' ', $col));
}

$skipTotalCols = [
    'submitted_at',
    'approve',
    'acc_approve',
    'distributed'
];

function renderTable($orders, $usedCols, $title, $skipTotalCols) {
    if (empty($orders)) {
        echo "<div class='no-data'>No $title orders found.</div>";
        return [];
    }

    echo "<h2>$title Orders</h2>";
    echo "<div class='table-wrapper'><table><thead><tr>
        <th>Order No</th><th>Name</th><th>Subject</th>";
    foreach ($usedCols as $col) {
        echo "<th>" . label($col) . "</th>";
    }
    echo '<th>Approve <br><input type="checkbox" id="select-all" title="Select/Deselect All"></th></tr></thead><tbody>';

    $totals = array_fill_keys($usedCols, 0);

    foreach ($orders as $order) {
        echo "<tr>
        <td>{$order['id']}</td>
        <td>" . htmlspecialchars($order['name']) . "</td>
        <td>" . htmlspecialchars($order['subject']) . "</td>";

        foreach ($usedCols as $col) {
            $qty = (int)$order[$col];
            echo "<td>{$qty}</td>";
            $totals[$col] += $qty;
        }

        // Add status cell
        $statusText = $order['approve'] ? 'Approved' : 'Pending';
        $statusColor = $order['approve'] ? '#27ae60' : '#e67e22';
        echo "<td class='status-cell' data-id='{$order['id']}' style='color:{$statusColor};'>{$statusText} <input type='checkbox' class='approve-box' data-id='{$order['id']}' " . ($order['approve'] ? 'checked' : '') . "></td>";

        echo "</tr>";
    }

           echo "<tr class='totrow'><td colspan='3'>TOTAL</td>";

            foreach ($usedCols as $col) {

                if (in_array($col, $skipTotalCols)) {
                    echo "<td></td>";
                    continue;
                }

                echo "<td>{$totals[$col]}</td>";
            }

            echo "<td></td></tr></tbody></table></div>";

            return $totals;

}

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



    </style>
</head>
<body>

<header>
    <img src="logo1.png" class="logo" alt="Logo">
    <h1>Ministry of Agriculture<br>Stationary Requests - <?= htmlspecialchars($month) ?> <?= htmlspecialchars($year) ?><br>Branch: <?= strtoupper(htmlspecialchars($branch)) ?></h1>

</header>

<div class="container">

        <a href="login.html" class="backbtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>

    <form method="POST" novalidate>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px;">
        <label for="month">Month:</label>
        <select name="month" id="month" required>
            <?php
            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            foreach ($months as $m): ?>
                <option value="<?= $m ?>" <?= ($m == $month ? 'selected' : '') ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>

        <label for="year">Year:</label>
        <select name="year" id="year" required>
            <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                <option value="<?= $y ?>" <?= ($y == $year ? 'selected' : '') ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit"><i class="fas fa-eye"></i> View</button>
    </div>
    </form>

    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px;">
        <a class="button" href="view_order.php">View Orders</a>
        <a class="button" href="place_order.php">📦 Place Order</a>
    </div>

    <div class="table-wrapper">
    <?php if (!empty($usedCols)): ?>
    <?php renderTable($pendingOrders, $usedCols, "Pending", $skipTotalCols); ?>
    <br>
    <?php renderTable($approvedOrders, $usedCols, "Approved", $skipTotalCols); ?>
<?php else: ?>
    <div class="no-data">No orders found for <?= htmlspecialchars("$month $year") ?>.</div>
<?php endif; ?>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // Handle individual approve checkbox changes
    $('.approve-box').change(function () {
        const checkbox = $(this);
        const orderId = checkbox.data('id');
        const isChecked = checkbox.is(':checked');

        if (!isChecked && !confirm("Are you sure you want to undo approval?")) {
            checkbox.prop('checked', true);
            return;
        }

        $.ajax({
            url: 'update_approval.php',
            method: 'POST',
            data: { id: orderId, approve: isChecked ? 1 : 0 },
            success: function (response) {
                // Update the status text and color dynamically
                const statusCell = $(".status-cell[data-id='" + orderId + "']");
                statusCell.css('color', isChecked ? '#27ae60' : '#e67e22');
                statusCell.contents().first()[0].textContent = isChecked ? 'Approved ' : 'Pending ';
            },
            error: function () {
                alert("Error updating approval status.");
                checkbox.prop('checked', !isChecked);
            }
        });
    });

    // Handle "Select All" checkbox
    $('#select-all').change(function () {
        const isChecked = $(this).is(':checked');
        $('.approve-box').each(function () {
            const checkbox = $(this);
            if (checkbox.prop('checked') !== isChecked) {
                checkbox.prop('checked', isChecked).trigger('change');
            }
        });
    });
});
</script>

</body>
</html>