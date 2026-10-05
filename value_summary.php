<?php
require 'db.php';

$year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

/* ===========================
   DATE HANDLING
=========================== */
$openingDate = ($month > 0)
    ? sprintf('%04d-%02d-01', $year, $month)
    : sprintf('%04d-01-01', $year);

$closingDate = ($month > 0)
    ? date('Y-m-t', strtotime("$year-$month-01"))
    : "$year-12-31";

/* ===========================
   LOAD ALL PRODUCTS
=========================== */
$products = [];
$res = $conn->query("SELECT product_id, product_name FROM products_table");
while ($r = $res->fetch_assoc()) {
    $products[$r['product_id']] = $r['product_name'];
}

/* ===========================
   OPENING STOCK
=========================== */
$opening = [];
$stmt = $conn->prepare("
    SELECT product_id, SUM(remaining_qty * unit_price) val
    FROM supply_table
    WHERE unit_price IS NOT NULL
      AND supply_date < ?
    GROUP BY product_id
");
$stmt->bind_param("s", $openingDate);
$stmt->execute();
$rs = $stmt->get_result();
while ($r = $rs->fetch_assoc()) {
    $opening[$r['product_id']] = $r['val'];
}

/* ===========================
   PURCHASE VALUE
=========================== */
$sql = "
    SELECT product_id, SUM(total_value) val
    FROM supply_table
    WHERE unit_price IS NOT NULL
      AND year=?
";
$params = [$year];
$types = "i";

if ($month > 0) {
    $sql .= " AND month=?";
    $params[] = $month;
    $types .= "i";
}
$sql .= " GROUP BY product_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rs = $stmt->get_result();

$purchase = [];
while ($r = $rs->fetch_assoc()) {
    $purchase[$r['product_id']] = $r['val'];
}

/* ===========================
   CLOSING STOCK
=========================== */
$closing = [];
$stmt = $conn->prepare("
    SELECT product_id, SUM(remaining_qty * unit_price) val
    FROM supply_table
    WHERE unit_price IS NOT NULL
      AND supply_date <= ?
    GROUP BY product_id
");
$stmt->bind_param("s", $closingDate);
$stmt->execute();
$rs = $stmt->get_result();
while ($r = $rs->fetch_assoc()) {
    $closing[$r['product_id']] = $r['val'];
}

/* ===========================
   FINAL ROWS
=========================== */
$rows = [];

foreach ($products as $pid => $name) {
    $open  = $opening[$pid]  ?? 0;
    $pur   = $purchase[$pid] ?? 0;
    $close = $closing[$pid]  ?? 0;

    $rows[] = [
        'name' => $name,
        'opening' => $open,
        'purchase' => $pur,
        'distributed' => max(0, $open + $pur - $close),
        'closing' => $close
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stock Value Summary</title>

<style>
body{
    font-family: Calibri;
    background:url('img1.jpg') no-repeat center center fixed;
    background-size:cover;
    padding:30px;
}
header { background:transparent; color:white; padding:20px 20px 10px; text-align:center; }
.logo { width:80px; height:auto; margin-bottom:10px; }
h1,h2 { text-align:center; color:#6a1b9a; }
.box{
    background:#fff;
    max-width:950px;
    margin:auto;
    padding:25px;
    border-radius:10px;
    box-shadow:0 6px 14px rgba(0,0,0,.15);
}
.filter{text-align:center;margin-bottom:15px;}
select,button{padding:6px 12px;border-radius:5px;}
button{background:#6a1b9a;color:#fff;border:none;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
th,td{padding:10px;border:1px solid #ddd;}
th{background: #6a1b9a;color: #fff;}
td{text-align:right;}
td:first-child{text-align:left;}
.total{
    font-weight:bold;
    background:#e3f2fd;
    border-top:3px solid #6a1b9a;
}
.back-btn{
    background:white;
    color: #6a1b9a;
    padding:6px 14px;
    border-radius:5px;
    text-decoration:none;
    display:inline-block;
    margin-bottom:10px;
}
thead th{
    position: sticky;
    top: 0;
    z-index: 5;
}
td.distributed {
    color: #c62828;
    font-weight:600;
}
tbody tr:nth-child(even){
    background:#f9f9f9;
}
#searchInput:focus{
    outline:none;
    border-color:#6a1b9a;
    box-shadow:0 0 4px rgba(106,27,154,.5);
}

</style>
</head>

<body>

<?php
$return = $_GET['return'] ?? 'view_supply.php';
?>
<a href="<?= htmlspecialchars($return) ?>" class="back-btn">⬅ Back</a>

<header>
    <img src="logo1.png" alt="Stationary Management Logo" class="logo">
    <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Value Summary</h1>
</header>
<div class="box">
<h2>Stock Value Summary (Per Product)</h2>

<div style="text-align:right;margin-bottom:10px;">
    <button onclick="printTable()">🖨 Print</button>
    <button onclick="exportExcel()">📊 Excel</button>
    <button id="exportPDF" >📄 PDF</button>
</div>

<form method="get" class="filter">
    <input
    type="text"
    id="searchInput"
    placeholder="🔍 Search product..."
    style="padding:6px 12px;border-radius:5px;border:1px solid #aaa;min-width:200px;"
    onkeyup="filterTable()"
/>

    Year:
    <select name="year">
        <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
            <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
        <?php endfor; ?>
    </select>

    Month:
    <select name="month">
        <option value="0">All</option>
        <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?=$m?>" <?=$m==$month?'selected':''?>><?=$m?></option>
        <?php endfor; ?>
    </select>

    <button type="submit">View</button>
</form>

<div id="summaryContent">
<table id="valueTable">
<thead>
<tr>
    <th>Product</th>
    <th>Opening</th>
    <th>Purchased/Received/Gift</th>
    <th>Distributed</th>
    <th>Closing</th>
</tr>
</thead>

<tbody>
<?php
$to=$tp=$td=$tc=0;
foreach($rows as $r):
$to+=$r['opening']; $tp+=$r['purchase']; $td+=$r['distributed']; $tc+=$r['closing'];
?>
<tr>
<td><?=htmlspecialchars($r['name'])?></td>
<td><?=number_format($r['opening'],2)?></td>
<td><?=number_format($r['purchase'],2)?></td>
<td class = "distributed"><?=number_format($r['distributed'],2)?></td>
<td><?=number_format($r['closing'],2)?></td>
</tr>
<?php endforeach; ?>
</tbody>

<tr class="total">
<td>TOTAL</td>
<td><?=number_format($to,2)?></td>
<td><?=number_format($tp,2)?></td>
<td><?=number_format($td,2)?></td>
<td><?=number_format($tc,2)?></td>
</tr>
</table>
</div>
</div>

<?php
$monthName = ($month > 0)
    ? date('F', mktime(0, 0, 0, $month, 1))
    : 'All Months';
?>

<script>
function filterTable() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {
        const product = row.cells[0].innerText.toLowerCase();
        row.style.display = product.includes(input) ? "" : "none";
    });
}
</script>

<script>
function printTable(){
    const rows = document.querySelectorAll("tbody tr");
    let html = `
        <html>
        <head>
            <title>Stock Value Summary</title>
            <style>
                table{width:100%;border-collapse:collapse;}
                th,td{border:1px solid #000;padding:6px;}
                th{background:#eee;}
            </style>
        </head>
        <body>
        <h3 style="text-align:center">Stock Value Summary</h3>
        <table>
            <thead>${document.querySelector("thead").innerHTML}</thead>
            <tbody>
    `;

    rows.forEach(row=>{
        if(row.style.display !== "none"){
            html += `<tr>${row.innerHTML}</tr>`;
        }
    });

    html += "</tbody></table></body></html>";

    const w = window.open("", "", "width=900,height=650");
    w.document.write(html);
    w.document.close();
    w.print();
}
</script>

<script>
function exportExcel(){

    let table = document.createElement("table");
    table.innerHTML = document.querySelector("thead").outerHTML;

    const tbody = document.createElement("tbody");

    document.querySelectorAll("tbody tr").forEach(row => {
        if (row.style.display !== "none") {
            tbody.appendChild(row.cloneNode(true));
        }
    });

    table.appendChild(tbody);

    const wb = XLSX.utils.table_to_book(table, { sheet: "Value Summary" });

    const fileName = "Value_Summary_<?= $year ?>_<?= $monthName ?>.xlsx";

    XLSX.writeFile(wb, fileName);
}
</script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<!-- Sinhala Unicode font -->
<script src="fonts/NotoSansSinhala-Regular-normal.js"></script>

<script>
$('#exportPDF').click(function(){

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');

    const pageWidth = doc.internal.pageSize.getWidth();

    /* ===============================
       TITLE (Year + Month)
    =============================== */
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.text(
        "Stock Value Summary",
        pageWidth / 2,
        35,
        { align: 'center' }
    );

    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.text(
        "<?= $monthName ?> - <?= $year ?>",
        pageWidth / 2,
        55,
        { align: 'center' }
    );

    let y = 75;

    /* ===============================
       TABLE
    =============================== */
    doc.autoTable({
        html: '#valueTable',
        startY: y,
        theme: 'grid',
        styles: {
            font: "NotoSansSinhala-Regular",   // Sinhala support
            fontSize: 10,
            cellPadding: 4
        },
        headStyles: {
            fillColor: [106, 27, 154],         // theme purple
            textColor: 255,
            fontStyle: 'bold'
        },
        alternateRowStyles: {
            fillColor: [245, 245, 255]
        }
    });

    /* ===============================
       FOOTER
    =============================== */
    doc.setFontSize(9);
    doc.text(
        "Ministry of Agriculture – Stationery Management System",
        pageWidth / 2,
        doc.internal.pageSize.getHeight() - 20,
        { align: 'center' }
    );

    /* ===============================
       FILE NAME (Year + Month)
    =============================== */
    doc.save(
        "Value_Summary_<?= $year ?>_<?= str_replace(' ', '_', $monthName) ?>.pdf"
    );
});
</script>

</body>
</html>
