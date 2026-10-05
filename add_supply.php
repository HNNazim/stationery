<?php
require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = false;
$data = [];

if ($id > 0) {
    $stmt = $conn->prepare("
        SELECT * FROM supply_table
        WHERE supply_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if (!$data) {
        die("Invalid supply record");
    }

    // prevent edit if distributed
    if ($data['quantity'] != $data['remaining_qty']) {
        die("Cannot edit distributed supply");
    }

    $edit = true;
}

$products = [];
$res = $conn->query("SELECT product_id, product_name, category FROM products_table ORDER BY product_id");
while($r=$res->fetch_assoc()){
    $products[strtolower($r['category'])][] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Add Supply</title>
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

.form-box{
    background:#fff;
    padding:22px;
    border-radius:10px;
    max-width:720px;
    margin:auto;
    box-shadow:0 4px 10px rgba(0,0,0,0.08)
}

/* Tabs */
.tabs{
    display:flex;
    gap:6px;
    margin-bottom:15px
}
.tabs button{
    flex:1;
    padding:8px 0;
    border:none;
    cursor:pointer;
    background:#e0e0e0;
    border-radius:6px;
    font-weight:bold
}
.tabs button.active{
    background:#6a1b9a;
    color:#fff
}

/* Labels & Inputs */
label{
    font-weight:bold;
    margin-top:10px;
    display:block
}
input, select, textarea{
    width:100%;
    padding:7px 9px;
    margin-top:4px;
    border-radius:5px;
    border:1px solid #bbb;
    font-size:14px
}
textarea{resize:vertical}

/* Quantity rows */
#priceRows{
    background:#f9f9f9;
    padding:10px;
    border-radius:6px;
    margin-top:8px
}
.row{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:10px;
    margin-bottom:8px
}

/* Buttons */
.btn{
    background:#6a1b9a;
    color:#fff;
    padding:8px 16px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}
.btn:hover{
    background:#7e57c2
}

.add-row-btn{
    margin-top:6px;
    margin-bottom:10px
}

/* Mobile */
@media(max-width:600px){
    .row{
        grid-template-columns:1fr
    }
}

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

<?php
$return = $_GET['return'] ?? 'view_supply.php';
?>
<a href="<?= htmlspecialchars($return) ?>" class="backbtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
<header>

    <img src="logo1.png" alt="Stationary Management Logo" class="logo">
    <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Add Supplies</h1>
</header>


<div class="form-box">

<h2>Add Supply</h2>

<div class="tabs">
<button onclick="showTab('stationery')" class="active">Stationery</button>
<button onclick="showTab('hardware')">Hardware</button>
<button onclick="showTab('other')">Other</button>
</div>

<form method="post" action="save_supply.php">
    
<input type="hidden" name="return" value="<?= htmlspecialchars($return) ?>">

<input type="hidden" name="supply_id" value="<?= $edit ? $id : '' ?>">
<input type="hidden" name="product_type" id="ptype" value="stationery">

<label>Product</label>
<select name="product_id" id="productSelect" <?= $edit ? 'disabled' : '' ?> required>
<?php
$cat = strtolower($data['category'] ?? 'stationery');
foreach ($products[$cat] as $p):
?>
<option value="<?= $p['product_id'] ?>"
    <?= $edit && $p['product_id']==$data['product_id']?'selected':'' ?>>
    <?= htmlspecialchars($p['product_name']) ?>
</option>
<?php endforeach; ?>
</select>

<?php if($edit): ?>
<input type="hidden" name="product_id" value="<?= $data['product_id'] ?>">
<?php endif; ?>

<label>Supply Type</label>
<select name="supply_type" id="supply_type" <?= $edit ? 'disabled' : '' ?>>
<option value="PURCHASE" <?= $edit && $data['supply_type']=='PURCHASE'?'selected':'' ?>>Purchased</option>
<option value="RECEIVED" <?= $edit && $data['supply_type']=='RECEIVED'?'selected':'' ?>>Received</option>
<option value="GIFT" <?= $edit && $data['supply_type']=='GIFT'?'selected':'' ?>>Gift</option>
<option value="OPENING BALANCE" <?= $edit && $data['supply_type']=='OPENING BALANCE'?'selected':'' ?>>Opening Balance</option>
</select>

<?php if($edit): ?>
<input type="hidden" name="supply_type" value="<?=$data['supply_type']?>">
<?php endif; ?>

<label>Date</label>
<input type="date" name="supply_date"
       value="<?= $edit ? $data['supply_date'] : '' ?>"
       <?= $edit ? 'readonly' : '' ?> required>

<div id="priceRows">
<div class="row">
    <input type="number" name="quantity[]" min="1" placeholder="Qunatity" required
           value="<?= $edit ? $data['quantity'] : '' ?>">
    <input type="number" step="0.01" name="unit_price[]" placeholder="Unit Price"
           value="<?= $edit ? $data['unit_price'] : '' ?>">
</div>
</div>

<button type="button" class="btn add-row-btn" onclick="addRow()">+ Add Price Row</button>

<label>Purchased / Received From</label>
<input type="text" name="source" value="<?= $edit ? htmlspecialchars($data['source']) : '' ?>">

<label>Remarks</label>
<textarea name="remarks"><?= $edit ? htmlspecialchars($data['remarks']) : '' ?></textarea>


<br><br>
<button class="btn">
<?= $edit ? 'Update Supply' : 'Save Supply' ?>
</button>
</form>
</div>

<script>
const products = <?= json_encode($products) ?>;

function showTab(type){
    document.getElementById('ptype').value = type;

    document.querySelectorAll('.tabs button')
        .forEach(b=>b.classList.remove('active'));
    event.target.classList.add('active');

    const sel = document.getElementById('productSelect');
    sel.innerHTML = '';

    if(!products[type]) return;

    products[type].forEach(p=>{
        let selected =
            <?= $edit ? $data['product_id'] : 'null' ?> == p.product_id
            ? 'selected' : '';
        sel.innerHTML +=
            `<option value="${p.product_id}" ${selected}>
                ${p.product_name}
             </option>`;
    });
}

showTab('<?= $edit ? strtolower($data['category']) : 'stationery' ?>');

function addRow(){
    const div = document.createElement('div');
    div.className='row';
    div.innerHTML = `
        <input type="number" name="quantity[]" min="1" required>
        <input type="number" step="0.01" name="unit_price[]">
    `;
    document.getElementById('priceRows').appendChild(div);
}

document.getElementById('supply_type').addEventListener('change',function(){
    document.querySelectorAll('[name="unit_price[]"]').forEach(i=>{
        i.style.display = this.value==='PURCHASE' ? 'block':'none';
    });
});
</script>

</body>
</html>
