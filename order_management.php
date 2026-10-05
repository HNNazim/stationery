<?php
session_start();
require 'db.php';

// Map product names (form_data columns) to product IDs
$productMap = [];
$prodRes = $conn->query("SELECT product_id, product_name FROM products_table");
while($p = $prodRes->fetch_assoc()){
    $productMap[$p['product_name']] = $p['product_id'];
}

$itemCols = [];
$result = $conn->query("SHOW COLUMNS FROM form_data");
while ($row = $result->fetch_assoc()) {
    $colName = $row['Field'];
    // Exclude meta columns like id, branch, month, year, approve, name, subject
    if (!in_array($colName, ['id','branch','month','year','approve','acc_approve','distributed','name','subject','submitted_at'])) {
    $itemCols[] = $colName;
    }

}

function label($col) {
    $labels = [
        'approve'       => 'Branch Approval',
        'acc_approve'   => 'Accountant Approval',
        'distributed'   => 'Distribution Status',
    ];

    return $labels[$col] ?? ucwords(str_replace('_', ' ', $col));
}

// --- Handle Filters ---
$filterYear  = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$filterMonth = isset($_GET['month']) ? $_GET['month'] : '';

// Fetch orders (filtered if year/month selected)
$sql = "SELECT * FROM form_data";
$where = [];

if ($filterYear) {
    $where[] = "year = $filterYear";
}
if ($filterMonth) {
    $where[] = "month = '".$conn->real_escape_string($filterMonth)."'";
}
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY branch, id DESC";

$res = $conn->query($sql);
if (!$res) die('DB error: ' . $conn->error);

$branches = [];
while ($row = $res->fetch_assoc()) {
    $branches[$row['branch']][] = $row;
}

// If no filter selected, auto-detect latest month+year
if (!$filterYear || !$filterMonth) {
    $latestYear = 0;
    $latestMonth = 0;
    foreach ($branches as $branchOrders) {
        foreach ($branchOrders as $order) {
            $y = (int)$order['year'];
            $m = date('n', strtotime($order['month']));
            if ($y > $latestYear || ($y === $latestYear && $m > $latestMonth)) {
                $latestYear = $y;
                $latestMonth = $m;
            }
        }
    }
    if (!$filterYear) $filterYear = $latestYear;
    if (!$filterMonth && $latestMonth) {
        $filterMonth = DateTime::createFromFormat('!m', $latestMonth)->format('F');
    }
}

$grandTotals = array_fill_keys($itemCols, 0);
$usedGlobalCols = [];

// ================= LOAD ADJUSTMENTS =================
$adjusted = [];

// table assumed: adjustments
// columns: branch, year, month, item, qty
$adjSql = $conn->prepare("
    SELECT scope, product_col, quantity 
    FROM adjusted_totals 
    WHERE year = ? AND month = ?
");
$adjSql->bind_param("is", $filterYear, $filterMonth);
$adjSql->execute();
$adjRes = $adjSql->get_result();

while ($r = $adjRes->fetch_assoc()) {
    $branch = strtolower($r['scope']); // account / development / establishment / all
    $adjusted[$branch][$r['product_col']] = (int)$r['quantity'];
}

$adjSql->close();

// ================= ACCOUNT APPROVED TOTALS =================
$accountApprovedTotals = array_fill_keys($itemCols, 0);

foreach ($branches as $branchOrders) {
    foreach ($branchOrders as $order) {

        // Only same month & year
        if ((int)$order['year'] !== $filterYear || $order['month'] !== $filterMonth) {
            continue;
        }

        // Only ACCOUNT approved orders
        if ((int)$order['acc_approve'] !== 1) {
            continue;
        }

        foreach ($itemCols as $col) {
            $qty = (int)$order[$col];
            if ($qty > 0) {
                $accountApprovedTotals[$col] += $qty;
            }
        }
    }
}

$branchApprovedTotals = [];

foreach ($branches as $bName => $orders) {
    foreach ($orders as $order) {
        if (
            (int)$order['year'] === $filterYear &&
            $order['month'] === $filterMonth &&
            (int)$order['acc_approve'] === 1
        ) {
            foreach ($itemCols as $col) {
                $branchApprovedTotals[$bName][$col] =
                    ($branchApprovedTotals[$bName][$col] ?? 0) + (int)$order[$col];
            }
        }
    }
}

$finalDistributedMap = [];

$q = $conn->prepare("
    SELECT scope, year, month, distributed
    FROM final_distribution_status
    WHERE year = ? AND month = ?
");
$q->bind_param("is", $filterYear, $filterMonth);
$q->execute();
$res = $q->get_result();

while ($r = $res->fetch_assoc()) {
    $key = strtolower($r['scope']) . '|' . $r['year'] . '|' . $r['month'];
    $finalDistributedMap[$key] = (int)$r['distributed'];
}
$q->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order View – Per Branch + Grand Totals</title>
    <style>
        body {
            font-family: 'Calibri', sans-serif;
            margin: 0;
            padding: 0;
            background: url('img1.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: #333;
        }
        header { background: transparent; color: white; padding: 20px; text-align: center; }
        .main-content { flex: 1; padding: 20px; background: rgba(255, 255, 255, 0.9);
            border-radius: 10px; margin: 20px auto; width: 90%; max-width: 1100px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; background: #fff; }
        th, td { padding: 6px 10px; border: 1px solid #ccc; font-size: 13px; text-align: center; }
        th { background: #e1bee7; }
        .totrow { font-weight: bold; background: #f3e5f5; }
        .scroll-x { overflow-x: auto; }
        .filter-form { text-align: center; margin-bottom: 20px; }
        .filter-form select, .filter-form button {
            padding: 6px 10px; margin: 5px; border-radius: 6px; border: 1px solid #aaa;
        }
        .editable_input, .approval_checkbox {
            width: 50px;
        }
        .gradient-text {
            font-weight: bold;
            font-size: 2.5rem;
            background: linear-gradient(to bottom, black, #6a1b9a, #ffffff, #6a1b9a, #f3e5f5, #6a1b9a, black);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }
        .logo {
            width: 80px;
            margin-bottom: 10px;
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
            position: absolute; 
            top: 10px;
            left: 10px; 
            z-index: 10; 
        }

        .backbtn:hover {
            background-color: #5e35b1;
        }

        .filter-select, .filter-search {
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #aaa;
        font-size: 14px;
        outline: none;
        transition: 0.2s;
        }

        .filter-select:focus, .filter-search:focus {
            border-color: #6a1b9a;
            box-shadow: 0 0 5px rgba(106, 27, 154, 0.4);
        }

        .filter-btn, .reset-btn {
            padding: 6px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
            text-decoration: none;
            color: white;
        }

        .filter-btn {
            background-color: #6a1b9a;
        }
        .filter-btn:hover {
            background-color: #4a148c;
        }

        .reset-btn {
            background-color: #aaa;
        }
        .reset-btn:hover {
            background-color: #888;
        }

        .extra-filters .filter-item label {
            margin-right: 5px;
            font-size: 14px;
        }

        @media(max-width: 600px){
            .filter-container, .extra-filters {
                flex-direction: column;
                align-items: center;
            }
        }

        @media print {
        .filter-container, .extra-filters, .filter-actions, .backbtn { display: none; }
        body { background: none; color: black; }
        }

        /* Whole adjusted row */
        .adjusted-row td {
            background: #f4f0ff;   /* light purple */
            font-weight: 600;
        }

        /* ONLY cells that were actually adjusted */
        .adjusted-cell {
            background: #ffe6e6 !important;  /* soft red */
            color: #b71c1c;
            font-weight: 700;
            border: 2px solid #ef9a9a;
        }

        /* Normal approved (not adjusted) */
        .approved-cell {
            background: #fffde7;
            font-weight: 600;
        }

        .final-distributed-cell {
            cursor: not-allowed;
            background: #f2f2f2;
        }

        .final-distributed-cell.unlocked-cell {
            cursor: pointer;
            background: #e8f5e9;
            box-shadow: inset 0 0 0 2px #4caf50;
        }

    </style>
</head>
<body>
    <a href="dashboard.php" class="backbtn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </a>
<header>
    <img src="logo1.png" alt="Stationery Management Logo" class="logo">
    <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Orders Management</h1>
</header>

<div class="main-content">

<?php
// Get all distinct branches from DB
$branchRes = $conn->query("SELECT DISTINCT branch FROM form_data ORDER BY branch ASC");
$branchesList = [];
while ($b = $branchRes->fetch_assoc()) {
    $branchesList[] = $b['branch'];
}
?>

<form method="GET" class="filter-form">
    <!-- Filter Section -->
<div class="filter-container" style="text-align:center; margin:20px 0; display:flex; flex-wrap:wrap; justify-content:center; gap:10px;">

    <!-- Year Selector -->
    <div class="filter-item">
        <label for="yearSelect" style="font-weight:bold;">Year:</label>
        <select id="yearSelect" name="year" class="filter-select">
            <option value="">Select Year</option>
            <?php
            $years = range(date('Y'), date('Y') - 5);
            foreach ($years as $y) {
                $sel = ($y == $filterYear) ? 'selected' : '';
                echo "<option value='$y' $sel>$y</option>";
            }
            ?>
        </select>
    </div>

    <!-- Month Selector -->
    <div class="filter-item">
        <label for="monthSelect" style="font-weight:bold;">Month:</label>
        <select id="monthSelect" name="month" class="filter-select">
            <option value="">Select Month</option>
            <?php
            foreach (range(1, 12) as $m) {
                $monthName = DateTime::createFromFormat('!m', $m)->format('F');
                $sel = ($monthName === $filterMonth) ? 'selected' : '';
                echo "<option value='$monthName' $sel>$monthName</option>";
            }
            ?>
        </select>
    </div>

    <!-- Filter Button -->
    <div class="filter-item" style="display:flex; gap:5px; align-items:center;">
        <button type="submit" class="filter-btn">Filter</button>
        <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="reset-btn">Reset</a>
    </div>
</div>

<!-- Extra Filters -->
<div class="extra-filters" style="text-align:center; margin:15px 0; display:flex; flex-wrap:wrap; justify-content:center; gap:10px; align-items:center;">

    <div class="filter-item">
        <label for="branchFilter" style="font-weight:bold;">Branch:</label>
        <select id="branchFilter" class="filter-select">
            <option value="">All Branches</option>
            <?php foreach ($branchesList as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-item">
        <label for="approvalFilter" style="font-weight:bold;">Status:</label>
        <select id="approvalFilter" class="filter-select">
            <option value="">All Status</option>
            <option value="approve">Branch Approved</option>
            <option value="acc_approve">Account Approved</option>
            <option value="distributed">Distributed</option>
            <option value="pending">Pending</option>
        </select>
    </div>

    <div class="filter-item">
        <input type="text" id="searchBox" placeholder="Search Name / Subject" class="filter-search">
    </div>

</div>

 <div style="gap:5px; align-items:center;">
    <button id="printBtn" class="filter-btn" >🖨️ Print</button>
    <button id="exportExcel" class="filter-btn">📊 Export Excel</button>
</div>

<h2 style='color:#6a1b9a; text-align:center;'>Orders for: <?= htmlspecialchars("$filterMonth $filterYear") ?></h2>

<?php foreach ($branches as $branchName => $orders): 
    $finalKey = strtolower($branchName) . '|' . $filterYear . '|' . $filterMonth;
    $isFinalDistributed = !empty($finalDistributedMap[$finalKey]);

?>   
    <?php
    // only include orders that match selected month & year
    $latestOrders = array_filter($orders, function($order) use ($filterYear, $filterMonth) {
        return ((int)$order['year'] === $filterYear) && ($order['month'] === $filterMonth);
    });
    if (empty($latestOrders)) continue;

    $usedCols = [];
    foreach ($latestOrders as $order) {
        foreach ($itemCols as $col) {
            if ((int)$order[$col] > 0) {
                $usedCols[$col] = true;
                $usedGlobalCols[$col] = true;
            }
        }
    }
    $usedCols = array_keys($usedCols);
    $usedCols[] = 'approve';
    $usedCols[] = 'acc_approve';
    if (empty($usedCols)) continue;

    $totals = array_fill_keys($usedCols, 0);

    ?>

    <h3><?= htmlspecialchars($branchName) ?></h3>

    <?php
    $approvalCols = ['approve','acc_approve'];
    $numericApprovalCols = ['distributed'];
    $numericCols = array_filter($usedCols, fn($c) => !in_array($c, $approvalCols));

    // Reset totals
    $totals = array_fill_keys($numericCols, 0);
    ?>

    <div class="scroll-x">
        <table>
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Name</th>
                        <th>Subject</th>
                        <?php foreach ($numericCols as $col): ?>
                            <th><?= label($col) ?></th>
                        <?php endforeach; ?>

                        <?php foreach ($approvalCols as $col): ?>
                            <th><?= label($col) ?></th>
                        <?php endforeach; ?>

                        <?php foreach ($numericApprovalCols as $col): ?>
                            <th>
                                <?= label($col) ?><br>
                                <input type="checkbox" class="select-all" data-col="<?= $col ?>">
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($latestOrders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['name']) ?></td>
                            <td><?= htmlspecialchars($order['subject']) ?></td>

                           <?php foreach ($numericCols as $col): ?>
                                <td>
                                    <?php if($col === 'submitted_at'): ?>
                                        <?= htmlspecialchars($order[$col]) ?> <!-- plain text, not editable -->
                                    <?php else: ?>
                                        <input type="number" class="editable_input" 
                                            data-id="<?= $order['id'] ?>" data-col="<?= $col ?>" 
                                            value="<?= (int)$order[$col] ?>" min="0"
                                            disabled>
                                    <?php endif; ?>
                                </td>
                                <?php if($col !== 'submitted_at') { 
                                    $totals[$col] += (int)$order[$col]; 
                                    $grandTotals[$col] += (int)$order[$col]; 
                                } ?>
                            <?php endforeach; ?>


                            <?php foreach ($approvalCols as $col): ?>
                                <td>
                                    <input type="checkbox" class="approval_checkbox"
                                        data-id="<?= $order['id'] ?>" data-col="<?= $col ?>"
                                        <?= ($order[$col]==1 ? 'checked' : '') ?>
                                        disabled>
                                </td>
                            <?php endforeach; ?>

                            <?php foreach ($numericApprovalCols as $col): ?>
                                <td>
                                    <input type="checkbox" class="approval_checkbox distributed_checkbox"
                                        data-id="<?= $order['id'] ?>" data-col="<?= $col ?>"
                                        <?= ($order[$col]==1 ? 'checked' : '') ?>
                                        <?= ($order['approve']==1 && $order['acc_approve']==1) ? '' : 'disabled' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="totrow">
                        <td colspan="3">TOTAL</td>
                        <?php foreach ($numericCols as $col): ?><td><?= $totals[$col] ?></td><?php endforeach; ?>
                        <?php foreach ($approvalCols as $col): ?><td>—</td><?php endforeach; ?>
                        <td>—</td>
                    </tr>
                    <tr class="adjusted-row">
                        <td colspan="3"><strong>FINAL APPROVED</strong></td>

                        <?php foreach ($numericCols as $col): ?>
                                <?php
                                    $branchKey = strtolower($branchName);

                        if (
                            isset($adjusted[$branchKey]) &&
                            array_key_exists($col, $adjusted[$branchKey])
                        ) {
                            // ✅ adjusted exists EVEN IF 0
                            $finalQty  = (int)$adjusted[$branchKey][$col];
                            $isAdjusted = true;
                        } else {
                            // fallback only when NO adjustment row
                            $finalQty  = (int)($branchApprovedTotals[$branchName][$col] ?? 0);
                            $isAdjusted = false;
                        }

                        ?>
                        <td class="<?= $isAdjusted ? 'adjusted-cell' : 'approved-cell' ?>">
                            <?= ($finalQty === 0 || $finalQty > 0) ? $finalQty : '-' ?>
                        </td>
                        <?php endforeach; ?>

                       <?php foreach ($approvalCols as $col): ?>
                        <td>—</td>
                        <?php endforeach; ?>
                        <td class="final-distributed-cell">
                            <input type="checkbox"
                                class="final-distributed"
                                data-branch="<?= htmlspecialchars($branchName) ?>"
                                data-year="<?= $filterYear ?>"
                                data-month="<?= $filterMonth ?>"
                                <?= $isFinalDistributed ? 'checked' : '' ?>
                                disabled
                            >
                        </td>
                    </tr>
                </tbody>
           </table>
        </div>

    <?php endforeach; ?>

    <h3 style="color:darkgreen">Overall Summary (All Branches)</h3>
    <div class="scroll-x">
        <table>
            <thead>
            <tr>
                <th></th>
                <?php foreach ($usedGlobalCols as $col => $_): ?>
                    <?php if ($grandTotals[$col] > 0): ?><th><?= label($col) ?></th><?php endif; ?>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>TOTAL (REQUESTED)</strong></td>
                <?php foreach ($usedGlobalCols as $col => $_): ?>
                    <?php if ($grandTotals[$col] > 0): ?>
                        <td><?= (int)$grandTotals[$col] ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td><strong>TOTAL (APPROVED)</strong></td>
                <?php foreach ($usedGlobalCols as $col => $_): ?>
                    <?php if ($grandTotals[$col] > 0): ?>
                        <td><?= (int)$accountApprovedTotals[$col] ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>

                <!-- 3️⃣ FINAL DISTRIBUTED (✓ OR ADJUSTED) -->
            <tr class="adjusted-row">
                <td><strong>TOTAL (FINAL APPROVED)</strong></td>

                <?php foreach ($usedGlobalCols as $col => $_): ?>
                    <?php if ($grandTotals[$col] > 0): ?>

                        <?php
                            if (isset($adjusted['all'][$col])) {
                                $finalQty  = (int)$adjusted['all'][$col];
                                $isAdjusted = true;
                            } else {
                                $finalQty  = (int)$accountApprovedTotals[$col];
                                $isAdjusted = false;
                            }
                        ?>

                        <td class="<?= $isAdjusted ? 'adjusted-cell' : 'approved-cell' ?>">
                            <?= $finalQty ?>
                        </td>

                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>

            </tbody>
    </table>
</div>
</div>

<script> var productMap = <?= json_encode($productMap) ?>; </script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

    $(document).ready(function(){
    $('.distributed_checkbox').on('change', function(){
        if ($(this).is(':disabled')) return;

        updateDistributed($(this));
    });

    $('.select-all').on('change', function () {
        let isChecked = $(this).is(':checked');
        let table = $(this).closest('table');

        table.find('tbody tr:not(.adjusted-row) .distributed_checkbox').each(function () {
            if (!$(this).is(':disabled')) {
                $(this).prop('checked', isChecked);
                updateDistributed($(this));
            }
        });
    });

    function updateDistributed(checkbox){
        let id  = checkbox.data('id');
        let val = checkbox.is(':checked') ? 1 : 0;

        $.post('update_order.php', {
            id: id,
            col: 'distributed',
            val: val
        }, function(resp){
            console.log('Distributed updated:', resp);
        }).fail(function(xhr){
            alert('Update failed: ' + xhr.responseText);
        });
    }

});

$(document).on('dblclick', '.final-distributed-cell', function (e) {
    e.preventDefault();
    e.stopPropagation();

    let cell     = $(this);
    let checkbox = cell.find('.final-distributed');

    // Already unlocked or finalized → do nothing
    if (!checkbox.prop('disabled')) return;

    // 🔍 FIND THE CORRECT TABLE
    let wrapper = cell.closest('.scroll-x');
    let table   = wrapper.find('table');

    // 🔍 CHECK IF ANY ROW IS DISTRIBUTED
    let distributedCount = table.find('.distributed_checkbox:checked').length;

    if (distributedCount === 0) {
        alert('❌ Mark at least ONE order as DISTRIBUTED first.');
        return;
    }

    // ✅ UNLOCK FINAL CHECKBOX
    checkbox.prop('disabled', false);
    cell.addClass('unlocked-cell');

    console.log('Final distribution unlocked');
});

$(document).on('change', '.final-distributed', function () {

    let checkbox = $(this);
    let branch = checkbox.data('branch');
    let year   = checkbox.data('year');
    let month  = checkbox.data('month');

    let wrapper = checkbox.closest('.scroll-x');
    let table   = wrapper.find('table');

    /* ===============================
       UNCHECK → UNDO FINAL
    =============================== */
    if (!checkbox.is(':checked')) {

        if (!confirm('Undo FINAL DISTRIBUTION for this branch?')) {
            checkbox.prop('checked', true);
            return;
        }

        $.post('undo_final_distribution.php', {
            branch: branch,
            year: year,
            month: month
        }, function () {

            checkbox.prop('disabled', true);
            checkbox.closest('.final-distributed-cell')
                    .removeClass('unlocked-cell');

            console.log('Final distribution reverted');

        }).fail(function (xhr) {
            alert(xhr.responseText);
            checkbox.prop('checked', true);
        });

        return;
    }

    /* ===============================
       CHECK → SAVE FINAL
    =============================== */

    let distributedCount = table.find('.distributed_checkbox:checked').length;

    if (distributedCount === 0) {
        alert('❌ Please mark at least ONE order as DISTRIBUTED before final distribution.');
        checkbox.prop('checked', false);
        return;
    }

    $.post('save_final_distribution.php', {
        branch: branch,
        year: year,
        month: month
    }, function (resp) {

        checkbox.prop('disabled', true);
        checkbox.closest('.final-distributed-cell')
                .removeClass('unlocked-cell');

        console.log('Final distribution saved:', resp);

    }).fail(function (xhr) {
        alert('Final distribution failed: ' + xhr.responseText);
        checkbox.prop('checked', false);
    });
});


$('#branchFilter, #approvalFilter, #searchBox').on('input change', function() {
    var selectedBranch = $('#branchFilter').val().toLowerCase();
    var approvalStatus = $('#approvalFilter').val();
    var searchText = $('#searchBox').val().toLowerCase();

    $('div.scroll-x').each(function() {
        var table = $(this).find('table');
        var anyVisible = false;

        table.find('tbody tr').each(function() {
            var row = $(this);
            var branch = $(this).closest('div.scroll-x').prev('h3').text().toLowerCase();
            var name = row.find('td:nth-child(2)').text().toLowerCase();
            var subject = row.find('td:nth-child(3)').text().toLowerCase();

            var branchMatch = !selectedBranch || branch.includes(selectedBranch);
            var searchMatch = !searchText || name.includes(searchText) || subject.includes(searchText);

            var approvalMatch = true;
            if (approvalStatus === 'approve') {
                approvalMatch = row.find('input[data-col="approve"]').is(':checked');
            } else if (approvalStatus === 'acc_approve') {
                approvalMatch = row.find('input[data-col="acc_approve"]').is(':checked');
            } else if (approvalStatus === 'distributed') {
                approvalMatch = row.find('input[data-col="distributed"]').is(':checked');
            } else if (approvalStatus === 'pending') {
                approvalMatch = !row.find('input[data-col="approve"]').is(':checked') ||
                                !row.find('input[data-col="acc_approve"]').is(':checked');
            }

            if (branchMatch && searchMatch && approvalMatch) {
                row.show();
                anyVisible = true;
            } else {
                row.hide();
            }
        });

        // Show/hide the heading and table wrapper
        if(anyVisible){
            $(this).prev('h3').show();
            $(this).show();
        } else {
            $(this).prev('h3').hide();
            $(this).hide();
        }
    });
});

document.getElementById('printBtn').addEventListener('click', function(){
    // Only grab tables and headings inside main-content
    let printContent = '';
    document.querySelectorAll('.main-content h3, .main-content .scroll-x').forEach(el => {
        printContent += el.outerHTML;
    });

    let win = window.open('', '', 'width=900,height=700');
    win.document.write('<html><head><title>Print Orders</title>');
    win.document.write('<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ccc;padding:5px;text-align:center;} th{background:#e1bee7;}</style>');
    win.document.write('</head><body>');
    win.document.write(printContent);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
});

document.getElementById('exportExcel').addEventListener('click', function(){
    let wb = XLSX.utils.book_new();

    // Loop through each branch table
    document.querySelectorAll('.main-content .scroll-x').forEach((wrapper, idx) => {
        let table = wrapper.querySelector('table');
        if (!table) return;

        let ws_data = [];

        // Table headers
        let headers = [];
        table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.innerText.trim());
        });
        ws_data.push(headers);

        // Table body rows
        table.querySelectorAll('tbody tr').forEach(tr => {
            if (!tr.offsetParent) return; // skip hidden rows
            let row = [];
            tr.querySelectorAll('td').forEach(td => {
                let input = td.querySelector('input');
                if(input){
                    if(input.type === 'checkbox'){
                        row.push(input.checked ? 1 : 0);
                    } else {
                        row.push(input.value);
                    }
                } else {
                    row.push(td.innerText.trim());
                }
            });
            ws_data.push(row);
        });

        let ws = XLSX.utils.aoa_to_sheet(ws_data);

        // Use actual branch name as sheet name (h3 before table)
        let branchName = wrapper.previousElementSibling ? wrapper.previousElementSibling.innerText.trim() : 'Branch' + (idx+1);
        // Excel sheet names max 31 chars
        XLSX.utils.book_append_sheet(wb, ws, branchName.substring(0, 31));
    });

    XLSX.writeFile(wb, 'Orders_<?= $filterMonth ?>_<?= $filterYear ?>.xlsx');
});

</script>

</body>
</html>
