<?php
session_start();
require 'db.php';

$password = strtolower($_SESSION['password']);
$common_users = ['est0', 'dev0', 'acc0'];
$branch_heads  = ['sec','as1', 'as2', 'ao','acc','ad1','ad2'];

$is_common = in_array($password, $common_users);

if ($is_common) {
    // COMMON users logic
    $branch = '';
    if (strpos($password, 'est') === 0) {
        $branch = 'establishment';
    } elseif (strpos($password, 'dev') === 0) {
        $branch = 'development';
    } else {
        $branch = 'account';
    }

    // Fetch latest month/year for this branch
    $latestSql = "SELECT year, month FROM form_data WHERE branch = ? 
                  ORDER BY year DESC, 
                  FIELD(month, 'December','November','October','September','August','July','June','May','April','March','February','January') 
                  LIMIT 1";
    $stmt = $conn->prepare($latestSql);
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $latestResult = $stmt->get_result();
    $latest = $latestResult->fetch_assoc();

    $year = $_GET['year'] ?? $latest['year'] ?? '';
    $month = $_GET['month'] ?? $latest['month'] ?? '';

    if ($year && $month) {
        $sql = "SELECT * FROM form_data WHERE branch = ? AND year = ? AND month = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $branch, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = false;
    }

} else {
    // Branch heads & special subjects
    $special_subjects = [
        'as1' => 'ASSISTANT SECRETARY I',
        'as2' => 'ASSISTANT SECRETARY II',
        'ao'  => 'ADMINISTRATIVE OFFICER',
        'ad1' => 'ASSISTANT DIRECTOR I',
        'ad2' => 'ASSISTANT DIRECTOR II',
        'sec' => 'SECRETARY',
        'acc' => 'ACCOUNTANT',
        'to'  => 'TECHNICAL OFFICER'
    ];

    if (array_key_exists($password, $special_subjects)) {
        $subject = $special_subjects[$password];
    } else {
        // fallback EST/DEV users (like est01, dev02)
        $prefix = strtoupper(substr($password, 0, 3));
        $number = substr($password, 3);
        $number = str_pad(ltrim($number, '0'), 2, '0', STR_PAD_LEFT);
        $subject = "$prefix:$number";
    }

    // Fetch latest month/year for this subject
    $latestSql = "SELECT year, month FROM form_data WHERE subject = ? 
                  ORDER BY year DESC, 
                  FIELD(month, 'December','November','October','September','August','July','June','May','April','March','February','January') 
                  LIMIT 1";
    $stmt = $conn->prepare($latestSql);
    $stmt->bind_param("s", $subject);
    $stmt->execute();
    $latestResult = $stmt->get_result();
    $latest = $latestResult->fetch_assoc();

    $year  = $_GET['year']  ?? $latest['year'] ?? '';
    $month = $_GET['month'] ?? $latest['month'] ?? '';

    if ($year && $month) {
        $sql = "SELECT * FROM form_data WHERE subject = ? AND year = ? AND month = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $subject, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = false;
    }
}

$rows = []; 
$common_rows = [];
$other_rows = [];
$has_data = false;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['subject'] == 'COMMON') {
            $common_rows[] = $row;
        } else {
            $other_rows[] = $row;
        }
    }

    // Sort other rows by subject ascending
    usort($other_rows, function ($a, $b) {
        return strcmp($a['subject'], $b['subject']);
    });

    $rows = array_merge($common_rows, $other_rows);
    $has_data = true;
}

$adjustedMap = [];

$adjStmt = $conn->prepare("
    SELECT scope, product_col, quantity
    FROM adjusted_totals
    WHERE month = ? AND year = ?
");
$adjStmt->bind_param("si", $month, $year);
$adjStmt->execute();
$adjRes = $adjStmt->get_result();

while ($r = $adjRes->fetch_assoc()) {
    $adjustedMap[$r['scope']][$r['product_col']] = (int)$r['quantity'];
}
$adjStmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stationary Request View</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            background: linear-gradient(
                180deg,
                #ffffff 0%,
                #9c94bbff 100%
            );
            color: #333;
        }

        header {
            text-align: center;
            color: #6b4fa3;
            padding: 30px 20px;
        }

        .logo {
            width: 80px;
            margin-bottom: 10px;
        }

        .container {
            max-width: 95vw;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(125, 125, 255, 0.2);
            overflow: hidden;
            padding: 30px;
            animation: fadeIn 0.5s ease-in-out;
        }

        h2 {
            text-align: center;
            color: #5e4b8b;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #d1d1e0;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #eee;
            color: #444;
        }

        td {
            background-color: #fdfdfd;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            background-color: #6b4fa3;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s;
            text-align: center;
        }

        .back-btn:hover {
            background-color: #533b8c;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            margin-top: 40px;
            font-size: 20px;
            color: #444;
            border-left: 5px solid #6b4fa3;
            padding-left: 10px;
        }

        .table-scroll {
            max-height: 65vh;          /* BIG scroll area */
            overflow-x: auto;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: inset 0 0 0 1px #ddd;
        }

        /* Keep header visible while scrolling */
        .table-scroll thead th {
            position: sticky;
            top: 0;
            background: #eee;
            z-index: 2;
        }

        @media print {
           body {
            background: #fff; /* optional: cleaner print */
            color: #000;
            }

            .container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }

            .container table {
                width: 100% !important;
                overflow: visible !important;
            }

            /* Remove scroll for print */
            div[style*="overflow-x:auto"] {
                overflow: visible !important;
            }

            /* Hide buttons during print */
            button, .back-btn {
                display: none !important;
            }

            th.submitted_at, td.submitted_at {
                display: none;
            }

            /* Remove scroll for printing */
            div[style*="overflow-x:auto"] {
                overflow: visible !important;
            }
        }

        .total-row td {
            background: #efe9ff;
            font-weight: bold;
        }

        .approved-label {
            font-weight: 600;
            background:  #fff8e1;
            letter-spacing: 0.5px;
        }

        .approved-adjusted-row td.adjusted-cell {
            background: #fff0f0;
            font-weight: 700;
            color: #c0392b;
            box-shadow: inset 0 0 0 1px #e6b0aa;
        }

        .approved-normal {
            font-weight: 600;
            background:  #fff8e1;
        }

        .approved-adjusted-row td {
            background: #fff8e1;
        }
        
        .status-approved {
            color: #8dcb90ff;
            font-weight: 600;
        }

        .status-pending {
            color: #db9f62ff;
            font-weight: 600;
        }

    </style>
</head>
<body>
<header>
    <img src="logo1.png" class="logo" alt="Logo">
    <h1>Ministry of Agriculture<br> Stationery Management System</h1>
</header>   

<div class="container">
        <?php
    $role_back_links = [
        'as1' => 'user_dashboard.php',
        'as2' => 'user_dashboard.php',
        'ao'  => 'user_dashboard.php',
        'acc' => 'account_dashboard.php',
        'ad1' => 'user_dashboard.php',
        'ad2' => 'user_dashboard.php',
    ];

    $password = strtolower($_SESSION['password']);
    $back_link = isset($role_back_links[$password]) ? $role_back_links[$password] : 'login.html';
    ?>


    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px;">
    <a class="back-btn" href="<?= $back_link ?>">← Back</a>
    <a class="back-btn" 
   href="place_order.php?month=<?= urlencode($month) ?>&year=<?= urlencode($year) ?>">
   📦 Place Order
</a>

    </div>

    <div style="margin: 15px 0; display: flex; gap: 10px; justify-content: center;">
    <button onclick="window.print()" 
            style="background:#6b4fa3;color:#fff;border:none;padding:8px 15px;border-radius:6px;cursor:pointer;">
        🖨 Print
    </button>

     <!-- Month & Year Selection Form -->
    <form method="GET" style="margin-bottom:20px; text-align:center;">
        <label for="month">Month: </label>
        <select name="month" id="month" required>
            <?php
            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            $selectedMonth = $_GET['month'] ?? $latest['month'] ?? '';
            foreach($months as $m){
                $sel = ($m == $selectedMonth) ? 'selected' : '';
                echo "<option value='$m' $sel>$m</option>";
            }
            ?>
        </select>

        <label for="year">Year: </label>
        <select name="year" id="year" required>
            <?php
            $currentYear = date('Y');
            $years = range($currentYear-3, $currentYear+1);
            $selectedYear = $_GET['year'] ?? $latest['year'] ?? '';
            foreach($years as $y){
                $sel = ($y == $selectedYear) ? 'selected' : '';
                echo "<option value='$y' $sel>$y</option>";
            }
            ?>
        </select>

        <button type="submit" style="background:#6b4fa3;color:#fff;border:none;padding:6px 12px;border-radius:5px;cursor:pointer;">View</button>
    </form>

    <?php if ($is_common): ?>
        <!-- Show Excel button only for COMMON view -->
        <button onclick="exportTableToExcel('stationeryTable', 'Stationery_Requests')" 
                style="background:#28a745;color:#fff;border:none;padding:8px 15px;border-radius:6px;cursor:pointer;">
            📊 Excel
        </button>
    <?php else: ?>
        <!-- Show PDF button only for NON-COMMON view -->
        <button onclick="exportNonCommonPDF()" 
                style="background:#e74c3c;color:#fff;border:none;padding:8px 15px;border-radius:6px;cursor:pointer;">
            📄 PDF
        </button>
    <?php endif; ?>
    </div>

<?php if ($is_common): ?>
    <h2>📋 Stationary Requests - COMMON View - <?= htmlspecialchars($month) ?> <?= htmlspecialchars($year) ?><br>Branch: <?= strtoupper(htmlspecialchars($branch)) ?></h2>

    <?php if (!$has_data): ?>
        <p>No data found.</p>
    <?php else: ?>

        <?php
        // Columns that should always be shown
        $always_include = ['approve', 'acc_approve','distributed'];

        // Determine which columns have any non-null, non-zero values
        $columns_with_data = [];

        foreach (array_keys($rows[0]) as $col) {
            if (in_array($col, $always_include)) {
                $columns_with_data[] = $col;
                continue;
            }

            foreach ($rows as $row) {
                if (isset($row[$col]) && $row[$col] !== null && $row[$col] != 0 && $row[$col] !== '') {
                    $columns_with_data[] = $col;
                    break;
                }
            }
        }
        ?>

    <div class="table-scroll">
    <table id="stationeryTable" border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <?php foreach ($columns_with_data as $col): ?>
                    <th class="<?= $col === 'submitted_at' ? 'submitted_at' : '' ?>">
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns_with_data as $col): ?>
                        <td class="<?= $col === 'submitted_at' ? 'submitted_at' : '' ?>">
                            <?php
                            if ($col === 'approve' || $col === 'acc_approve' || $col === 'distributed') {

                                if ((int)$row[$col] === 1) {
                                    echo '<span class="status-approved">Approved</span>';
                                } else {
                                    echo '<span class="status-pending">Pending</span>';
                                }

                            } else {
                                echo ($row[$col] !== null && $row[$col] != 0 && $row[$col] !== '')
                                    ? htmlspecialchars($row[$col])
                                    : '-';
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6">
                    TOTAL
                </td>

                <?php
                $skipped = 0;
                foreach ($columns_with_data as $index => $col):
                    if ($index < 6) {
                        $skipped++;
                        continue; // Already merged these columns
                    }
                ?>
                    <td>
                        <?php
                        if ($col === 'approve' || $col === 'acc_approve' || $col === 'distributed') {
                            echo '-'; // No total for status columns
                        } else {
                            $total = 0;
                            foreach ($rows as $row) {
                                if (is_numeric($row[$col])) {
                                    $total += $row[$col];
                                }
                            }
                            echo $total > 0 ? $total : '-';
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <tr class="approved-adjusted-row">
                <td colspan="6" class="approved-label">
                    APPROVED QUANTITY
                </td>

                <?php
                foreach ($columns_with_data as $index => $col):

                    if ($index < 6) continue;

                    if (in_array($col, ['approve','acc_approve','distributed'])) {
                        echo "<td>-</td>";
                        continue;
                    }

                    //STEP 1: Accountant-approved total
                    $accApprovedTotal = 0;

                    foreach ($rows as $row) {
                        if (
                            (int)$row['acc_approve'] === 1 &&
                            isset($row[$col]) &&
                            is_numeric($row[$col])
                        ) {
                            $accApprovedTotal += (int)$row[$col];
                        }
                    }
    
                    //STEP 2: Branch-wise adjustment
                    $finalValue = $accApprovedTotal;
                    $isAdjusted = false;

                    $currentScope = strtolower($branch); // branch-scoped COMMON view
 

                    if (
                        isset($adjustedMap[$currentScope]) &&
                        array_key_exists($col, $adjustedMap[$currentScope])
                    ) {

                        $finalValue = (int)$adjustedMap[$currentScope][$col];
                        $isAdjusted = true;
                    }

                    //STEP 3: Output
                    if ($isAdjusted) {
                        echo "<td class=\"adjusted-cell\">
                                {$finalValue}
                            </td>";
                    } else {
                        echo "<td class=\"approved-normal\">
                                " . ($finalValue > 0 ? $finalValue : '-') . "
                            </td>";
                    }

                endforeach;
                ?>
            </tr>

        </tfoot>
        
    </table>
</div>

    <?php endif; ?>
    
<?php else: ?>

    <h2>📋 Stationary Request Overview</h2>

    <?php if (!$has_data): ?>
        <p>No data found.</p>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <?php
            $rowScope = strtolower($row['branch']);   // THIS row’s branch
            $branchHasAdjustment = false;

            if (isset($adjustedMap[$rowScope])) {
                foreach ($adjustedMap[$rowScope] as $qty) {
                    if ((int)$qty >= 0) {
                        $branchHasAdjustment = true;
                        break;
                    }
                }
            }
            ?>
             <table class="nonCommonTable" border="1" cellpadding="5" cellspacing="0" style="margin-bottom: 30px;">
                <tr><th>Subject</th><td><?= htmlspecialchars($row['subject']) ?></td></tr>
                <tr><th>Year</th><td><?= htmlspecialchars($row['year']) ?></td></tr>
                <tr><th>Month</th><td><?= htmlspecialchars($row['month']) ?></td></tr>
                <tr><th>Branch</th><td><?= htmlspecialchars($row['branch']) ?></td></tr>
                <tr><th>Name</th><td><?= htmlspecialchars($row['name']) ?></td></tr>
                <tr><th>Branch Approval</th>
                    <td style="color: <?= $row['approve'] == 0 ? '#e67e22' : '#27ae60' ?>;">
                        <?= $row['approve'] == 0 ? 'Approval Pending' : 'Approved' ?>
                    </td>
                </tr>
                <tr>
                    <th>Accountant Approval</th>
                    <td>
                        <div style="color: <?= $row['acc_approve'] == 0 ? '#e67e22' : '#27ae60' ?>;">
                            <?= $row['acc_approve'] == 0 ? 'Approval Pending' : 'Approved' ?>
                        </div>

                        <?php if ($row['acc_approve'] == 1 && $branchHasAdjustment): ?>
                            <div style="
                                color:#e67e22;
                                font-size:15px;
                                border-radius:6px;">
                                [⚠ Approved with adjustments – May affect order quantities!]
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr><th>Distribution Status</th>
                    <td style="color: <?= $row['distributed'] == 0 ? '#e67e22' : '#27ae60' ?>;">
                        <?= $row['distributed'] == 0 ? 'Pending' : 'Distributed' ?>
                    </td>
                </tr>
            </table>

            <h3 class="section-title">Requested Items</h3>
            <table class="nonCommonTable" border="1" cellpadding="5" cellspacing="0">
                <thead><tr><th>Item</th><th>Quantity</th></tr></thead>
                <tbody>
                <?php
                foreach ($row as $key => $value) {
                    if (!in_array($key, ['id','subject','branch','name','year','month','approve','acc_approve','distributed']) && $value > 0) {
                        echo "<tr><td>" . htmlspecialchars(ucwords(str_replace("_", " ", $key))) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
                    }
                }
                ?>
                </tbody>
            </table>
<br><br><hr><br><br>            

        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
   
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<!-- Sinhala Unicode font -->
<script src="fonts/NotoSansSinhala-Regular-normal.js"></script>

<script>
// Excel Export
function exportTableToExcel(tableID, filename = ''){
    const table = document.getElementById(tableID);
    const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet 1" });
    XLSX.writeFile(wb, filename ? filename + ".xlsx" : 'export.xlsx');
}

// PDF Export
function exportNonCommonPDF() {

    const { jsPDF } = window.jspdf;

    // Landscape A4
    const doc = new jsPDF('l', 'pt', 'a4');

    /* ===============================
       TITLE
    =============================== */
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.text(
        "Stationery Request – View Order",
        doc.internal.pageSize.getWidth() / 2,
        40,
        { align: "center" }
    );

    let startY = 65;

    /* ===============================
       LOOP ALL NON-COMMON TABLES
    =============================== */
    document.querySelectorAll(".nonCommonTable").forEach((table, index) => {

        // Skip empty tables
        if (!table.querySelector("tr")) return;

        doc.autoTable({
            html: table,
            startY: startY,
            theme: "grid",

            styles: {
                font: "NotoSansSinhala-Regular",   // Sinhala support
                fontSize: 9,
                cellPadding: 4,
                overflow: "linebreak"
            },

            headStyles: {
                fillColor: [107, 79, 163],         // theme purple
                textColor: 255,
                fontStyle: "bold"
            },

            alternateRowStyles: {
                fillColor: [245, 245, 255]
            },

            didDrawPage: function () {
                // Footer
                doc.setFontSize(9);
                doc.text(
                    "Ministry of Agriculture – Stationery Management System",
                    doc.internal.pageSize.getWidth() / 2,
                    doc.internal.pageSize.getHeight() - 20,
                    { align: "center" }
                );
            }
        });

        startY = doc.lastAutoTable.finalY + 20;

        // New page if space is low
        if (startY > doc.internal.pageSize.getHeight() - 120) {
            doc.addPage();
            startY = 40;
        }
    });

    /* ===============================
       SAVE
    =============================== */
    doc.save(
        "Stationery_View_Order_<?= htmlspecialchars($month) ?>_<?= htmlspecialchars($year) ?>.pdf"
    );
}

</script>

</body>
</html>

