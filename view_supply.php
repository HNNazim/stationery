<?php
require 'db.php';

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

$where = "WHERE s.year = ?";
$params = [$year];
$types  = "i";

if ($month > 0) {
    $where .= " AND s.month = ?";
    $params[] = $month;
    $types   .= "i";
}

$sql = "
SELECT 
    s.supply_id,
    p.product_name,
    p.category,
    s.supply_date,
    s.supply_type,
    s.quantity,
    s.remaining_qty,
    s.unit_price,
    s.total_value,
    s.source,
    s.remarks
FROM supply_table s
JOIN products_table p ON p.product_id = s.product_id
$where
ORDER BY s.supply_date ASC, s.supply_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$months = [
    1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Supply Records</title>

<style>
body {
        font-family: Calibri, sans-serif;
        background: url('img1.jpg') no-repeat center center fixed;
        background-size: cover;
        background-color: #f7f3fc; /* Fallback */
        padding: 20px;
        margin: 0;
        color: #333;
    }
header { background:transparent; color:white; padding:20px 20px 10px; text-align:center; }
.logo { width:80px; height:auto; margin-bottom:10px; }
h1,h2{ text-align:center; color:#6a1b9a; }

.box{
    background:#fff;
    padding:20px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.08)
}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px
}
.header h2{
    margin:0;
    color:#6a1b9a
}
.btn{
    background:#6a1b9a;
    color:#fff;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
    font-weight:bold;
    transition:.3s
}
.btn:hover{background:#7e57c2}

.filter{
    display:flex;
    gap:10px;
    align-items:center;
    margin-bottom:15px
}
select,button{
    padding:6px 10px;
    border-radius:5px;
    border:1px solid #aaa
}
button{
    background:#6a1b9a;
    color:#fff;
    border:none;
    cursor:pointer
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:13px
}
th,td{
    border:1px solid #ddd;
    padding:7px;
    text-align:center
}
th{
    background:#6a1b9a;
    color:#fff
}
tbody tr:hover{
    background:#f3e5f5
}

.low{
    color:#c62828;
    font-weight:bold
}

.badge{
    padding:3px 8px;
    border-radius:12px;
    font-size:11px;
    font-weight:bold;
    color:#fff
}
.purchase{background:#2e7d32}
.received{background:#0277bd}
.gift{background:#6a1b9a}
.opening {background: yellow;}

.backbtn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 15px 15px;
            background: transparent;
            color:white;
            border: none;
            border-radius: 6px;
            font-size: 28px;
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

</style>
</head>

<body>
    <a href="dashboard.php" class="backbtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
<header>

    <img src="logo1.png" alt="Stationary Management Logo" class="logo">
    <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Supply Management</h1>
</header>


<div class="box">

<div class="header">
    <h2>Supply Records</h2>
    <a href="value_summary.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"  class="btn" style="background:#2e7d32">
    💰 Value Summary</a>
    <a href="add_supply.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn">➕ Add Supply</a>
</div>

<form method="get" class="filter">
    <strong>Year</strong>
    <select name="year">
    <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
        <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
    <?php endfor;?>
    </select>

    <strong>Month</strong>
    <select name="month">
        <option value="0">All</option>
        <?php foreach($months as $k=>$m): ?>
        <option value="<?=$k?>" <?=$k==$month?'selected':''?>><?=$m?></option>
        <?php endforeach;?>
    </select>

    <button>Filter</button>
</form>

<table>
<thead>
<tr>
<th>Date</th>
<th>Product</th>
<th>Category</th>
<th>Type</th>
<th>Qty</th>
<th>Remaining</th>
<th>Unit Price</th>
<th>Total Value</th>
<th>Source</th>
<th>Remarks</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php while($r=$result->fetch_assoc()): ?>
<tr>
<td><?=date('Y-m-d',strtotime($r['supply_date']))?></td>
<td><?=htmlspecialchars($r['product_name'])?></td>
<td><?=ucfirst($r['category'])?></td>
<td>
<span class="badge <?= strtolower($r['supply_type']) ?>">
    <?=$r['supply_type']?>
</span>
</td>
<td><?=$r['quantity']?></td>
<td class="<?=$r['remaining_qty']==0?'low':''?>">
    <?=$r['remaining_qty']?>
</td>
<td><?= $r['unit_price']!==null ? number_format($r['unit_price'],2) : '-' ?></td>
<td><?= $r['total_value']!==null ? number_format($r['total_value'],2) : '-' ?></td>
<td><?=htmlspecialchars($r['source'])?></td>
<td><?=htmlspecialchars($r['remarks'])?></td>
<td>
    <?php if($r['quantity']==$r['remaining_qty']): ?>
    <a href="add_supply.php?id=<?=$r['supply_id']?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn">
    Edit
    </a>

    <?php else: ?>
    —
    <?php endif; ?>

    <a href="delete_supply.php?id=<?= $r['supply_id'] ?>"
       onclick="return confirm('Are you sure you want to delete this supply record?')"
       class="btn"
       style="padding:4px 8px;font-size:11px;background:#c62828;">
       Delete
    </a>
</td>

</tr>
<?php endwhile;?>
</tbody>
</table>

</div>
</body>
</html>
