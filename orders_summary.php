<?php
session_start();
require 'db.php';

$currentYear = date('Y');
$year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// 🔹 Fetch products
$query = "SELECT 
    p.product_id,
    p.product_name,
    p.category,
    p.type,
    COALESCE(q.minimum_qty,0) AS minimum_qty
FROM products_table p
LEFT JOIN quantity_table q 
    ON p.product_id = q.product_id 
   AND q.year = ?
ORDER BY p.product_id";

$stmt = $conn->prepare($query);
$stmt->bind_param("i",$year);
$stmt->execute();
$result = $stmt->get_result();


$categories = ['stationery'=>[],'other'=>[],'hardware'=>[]];
while($row = $result->fetch_assoc()){
    $cat = strtolower($row['category']);
    if(!isset($categories[$cat])) $cat='other';
    $categories[$cat][] = $row;
}

$months = [
  1=>'January',2=>'February',3=>'March',4=>'April',
  5=>'May',6=>'June',7=>'July',8=>'August',
  9=>'September',10=>'October',11=>'November',12=>'December'
];


// 🔹 Fetch monthly received & distributed quantities
$monthlyData = [];

/* 🔹 RECEIVED (SUPPLY) */
$recStmt = $conn->prepare("SELECT product_id, month, SUM(quantity) AS rec
    FROM supply_table
    WHERE year = ?
    GROUP BY product_id, month");
$recStmt->bind_param("i", $year);
$recStmt->execute();
$recRes = $recStmt->get_result();

while ($r = $recRes->fetch_assoc()) {
    $monthlyData[$r['product_id']][$r['month']]['rec'] = (int)$r['rec'];
}

/* 🔹 DISTRIBUTED */
$disStmt = $conn->prepare("
    SELECT 
        product_id,
        FIELD(
            TRIM(LOWER(month)),
            'january','february','march','april','may','june',
            'july','august','september','october','november','december'
        ) AS month_no,
        SUM(quantity) AS dis
    FROM distribution_table
    WHERE year = ?
    GROUP BY product_id, month_no
    HAVING month_no > 0
");


$disStmt->bind_param("i", $year);
$disStmt->execute();
$disRes = $disStmt->get_result();

while ($r = $disRes->fetch_assoc()) {
    $monthlyData[$r['product_id']][$r['month_no']]['dis'] = (int)$r['dis'];
}

$openingStock = [];

$openingSql = "SELECT 
    product_id,
    SUM(remaining_qty) AS opening_qty
FROM supply_table
WHERE supply_date < ?
GROUP BY product_id";

$startDate = $year . '-01-01';

$stmtOpen = $conn->prepare($openingSql);
$stmtOpen->bind_param("s", $startDate);
$stmtOpen->execute();
$resOpen = $stmtOpen->get_result();

while ($r = $resOpen->fetch_assoc()) {
    $openingStock[$r['product_id']] = (int)$r['opening_qty'];
}

?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products Summary - Stationery Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { font-family:'Calibri',sans-serif; margin:0; padding:0; background:url('img1.jpg') no-repeat center center fixed; background-size:cover; color:#333; }
header { background:transparent; color:white; padding:20px 20px 10px; text-align:center; }
.logo { width:80px; height:auto; margin-bottom:10px; }
h1,h2 { text-align:center; color:#6a1b9a; }
table { width:100%; border-collapse:collapse; margin-top:10px; background:#f3e5f5; }
th,td { padding:8px; border:1px solid #ddd; text-align:center; font-size:12px; }
th { background-color:#6a1b9a; color:white; }
.table-wrapper { padding:0 20px; margin-bottom:20px; overflow-x:auto; }
.button-group { margin:20px 30px 0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.btn { background:#6a1b9a; color:white; font-weight:bold; padding:8px 15px; border:none; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:0.3s; font-size:14px; }
.btn:hover { background:#7e57c2; }
.year-filter { margin:10px auto; text-align:center; }
select { padding:5px 10px; border-radius:5px; border:1px solid #6a1b9a; font-size:1rem; }
.gradient-text { font-weight:bold; font-size:2rem; background: linear-gradient(to bottom, black,#6a1b9a,#ffffff,#6a1b9a,#f3e5f5,#6a1b9a,black); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.low-stock { background-color:#ffebee; color:#c62828; font-weight:bold; }
.status-order { background-color:#ffcccc; color:#c62828; font-weight:bold; }
.status-ok { background-color:#e8f5e9; color:#2e7d32; font-weight:bold; }
input.rec-input { width:45px; text-align:center; border:1px solid #aaa; border-radius:4px; background:#fff; }
input.rec-input:focus { outline:2px solid #6a1b9a; }
@media print {
  .button-group, .year-filter { display:none; }
  body { background:none; }
  table { font-size:10px; }
}
</style>
</head>
<body>

<div class="button-group">
    <a href="product_management.php" class="btn"><i class="fa fa-arrow-left"></i> Back</a>
    <button id="printBtn" class="btn"><i class="fa fa-print"></i> Print</button>
    <button id="exportExcel" class="btn"><i class="fa fa-file-excel"></i> Export Excel</button>
    <button id="exportPDF" class="btn"><i class="fa fa-file-pdf"></i> Export PDF</button>
    <a href="dashboard.php" class="btn">Dashboard <i class="fa fa-home"></i></a>
</div>

<header>
    <img src="logo1.png" alt="Stationary Management Logo" class="logo">
    <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Products Summary</h1>
</header>

<div class="year-filter">
<label for="year">Select Year:</label>
<select id="year" onchange="location.href='?year='+this.value;">
<?php for($y=$currentYear-2;$y<=$currentYear+1;$y++): ?>
    <option value="<?= $y ?>" <?= ($y==$year)?'selected':'' ?>><?= $y ?></option>
<?php endfor; ?>
</select>
</div>

<div id="summaryContent">
<?php foreach($categories as $category => $products): ?>
<h2><?= ucfirst($category) ?></h2>
<div class="table-wrapper">
<?php if(!empty($products)): ?>
<table>
<thead>
<tr>
    <th rowspan="2">Product Name</th>
    <th rowspan="2">Available<br>(Year Start)</th>
    <th rowspan="2">Minimum<br>Qty</th>

    <?php foreach($months as $mName): ?>
        <th colspan="2"><?= $mName ?></th>
    <?php endforeach; ?>

    <th colspan="4">Total (Year End)</th>
</tr>
<tr>
    <?php foreach($months as $mName): ?>
        <th>Rec</th>
        <th>Dis</th>
    <?php endforeach; ?>

    <th>Total Rec</th>
    <th>Total Dis</th>
    <th>Balance</th>
    <th>Status</th>
</tr>
</thead>

<tbody>
<?php 
foreach($products as $p):
$available = $openingStock[$p['product_id']] ?? 0;
$minQty = (int)$p['minimum_qty'];
$totalRec = 0;
$totalDis = 0;
$balance = $available;
?>
<tr data-id="<?= $p['product_id'] ?>">
<td><?= htmlspecialchars($p['product_name']); ?><br><small style="color:#666;font-style:italic;"><?= htmlspecialchars($p['type']); ?></small></td>
<td><?= $available ?></td>
<td><?= $minQty ?></td>
<?php foreach($months as $mNo => $mName):
$rec = $monthlyData[$p['product_id']][$mNo]['rec'] ?? 0;
$dis = $monthlyData[$p['product_id']][$mNo]['dis'] ?? 0;
$totalRec += $rec;
$totalDis += $dis;
$balance = $balance + $rec - $dis;
?>
<td>
<input type="number"
       class="rec-input"
       value="<?= $rec ?>"
       disabled>
</td>
<td><?= $dis ?></td>
<?php endforeach; ?>
<td class="total-rec"><?= $totalRec ?></td>
<td class="total-dis"><?= $totalDis ?></td>
<?php 
$cls = ($balance < $minQty) ? 'status-order' : 'status-ok';
$status = ($balance < $minQty) ? 'Order' : 'No';
?>
<td class="balance"><?= $balance ?></td>
<td class="<?= $cls ?>"><?= $status ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p style="text-align:center;">No products found in this category.</p>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="fonts/NotoSansSinhala-Regular-normal.js"></script>

<script>
// 🔹 Print
$("#printBtn").click(() => window.print());

// 🔹 Export Excel
$("#exportExcel").click(() => {
    const wb = XLSX.utils.table_to_book(document.querySelector("#summaryContent"), { sheet: "Summary" });
    XLSX.writeFile(wb, "Product_Summary_<?= $year ?>.xlsx");
});

// 🔹 Export PDF
$('#exportPDF').click(function(){
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');

    // --- English heading ---
    doc.setFont("helvetica"); 
    doc.setFontSize(16);
    doc.text("Product Summary - <?= $year ?>", doc.internal.pageSize.getWidth()/2, 40, { align: 'center' });

    let y = 60;

    // --- Tables with Sinhala content ---
    $('table').each(function(idx, table){
        doc.autoTable({
            html: table,
            startY: y,
            theme: 'grid',
            styles: { font: "NotoSansSinhala-Regular", fontSize: 10 },
            didParseCell: function(data){
                // Highlight Demand % column
                if(data.column.index === data.table.columns.length - 1){ // last column
                    let cellText = data.cell.text[0] || "";
                    if(cellText.includes("Most Demanded")){
                        data.cell.styles.fillColor = [185, 246, 202]; // green
                    } else if(cellText.includes("Lowest Demanded")){
                        data.cell.styles.fillColor = [255, 138, 128]; // red
                    }
                }
            }
        });
        y = doc.lastAutoTable.finalY + 20;
    });

    doc.save('Product_Summary_<?= $year ?>.pdf');
});
</script>
</body>
</html>
