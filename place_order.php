<?php
session_start();
include('db.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$branchScope = strtolower($_SESSION['username']);   // or however you store it
$today = new DateTime();
$year  = $_POST['year']  ?? $_GET['year']  ?? $today->format('Y');
$month = $_POST['month'] ?? $_GET['month'] ?? $today->format('F');
$currentYear  = $year;
$currentMonth = $month;

if (isMonthFinalDistributed($conn, $branchScope, $year, $month)) {
    echo '
    <div style="
        max-width:500px;
        margin:80px auto;
        padding:25px;
        text-align:center;
        background:#ffebee;
        border:1px solid #ffcdd2;
        border-radius:10px;
        font-family:Arial;
    ">
        <h2 style="color:#b71c1c; margin-bottom:15px;">
            🔒 Orders for '.$month.' '.$year.' are FINAL DISTRIBUTED
        </h2>

        <p style="margin-bottom:20px;">
            This month is locked. Please select a different month and year.
        </p>

        <a href="view_order.php"
           style="
            display:inline-block;
            padding:10px 18px;
            background:#6a1b9a;
            color:white;
            text-decoration:none;
            border-radius:6px;
           ">
            ⬅ Back to Month Selection
        </a>
    </div>
    ';
    exit; // ✅ stop backend safely
}

/* ========================
   SESSION-BASED SUBJECT
   ======================== */
$password = strtolower($_SESSION['password']);

if (in_array($password, ['est0', 'dev0', 'acc0'])) {
    $subject = 'COMMON';
} else {
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
        $prefix = strtoupper(substr($password, 0, 3));
        $number = str_pad(ltrim(substr($password, 3), '0'), 2, '0', STR_PAD_LEFT);
        $subject = "$prefix:$number";
    }
}

$branch = strtoupper($_SESSION['username']);  

/* ========================
   CHECK UNAPPROVED ORDER
   ======================== */
$editMode = false;
$existingOrder = [];

$checkSql = "SELECT *
    FROM form_data
    WHERE subject = ?
      AND approve = 0
      AND year = ?
      AND month = ?
    LIMIT 1";

$stmt = $conn->prepare($checkSql);
$stmt->bind_param("sis", $subject, $year, $month);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $existingOrder = $res->fetch_assoc();
    $editMode = true;
}

/* ========================
   FETCH PRODUCTS
   ======================== */
$products = [];
$sql = "SELECT * FROM products_table ORDER BY category, `type`, product_name";
$result = $conn->query($sql);
if(!$result) die("Query Error: " . $conn->error);

while($row = $result->fetch_assoc()){
    $products[strtolower($row['category'])][strtolower($row['type'])][] = $row['product_name'];
}

$orderValues = [];

if ($editMode) {
    foreach ($existingOrder as $col => $val) {
        if (is_numeric($val)) {
            $orderValues[$col] = (int)$val;
        }
    }
}

function isMonthFinalDistributed($conn, $branchScope, $year, $month) {

    $scope = strtolower($branchScope);   // ✅ variable

    $stmt = $conn->prepare("
        SELECT distributed
        FROM final_distribution_status
        WHERE scope = ? AND year = ? AND month = ?
        LIMIT 1
    ");

    $stmt->bind_param("sis", $scope, $year, $month);
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
<title>Place Stationery Order</title>
<style>
         body {
    font-family: Calibri, sans-serif;
    background: url('img1.jpg') no-repeat center center fixed;
    background-size: cover;
    background-color: #f7f3fc;
    margin: 0;
    min-height: 100vh;
    color: #333;
}

.page-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;     /* horizontal center */
    justify-content: center; /* vertical center */
}

header {
    text-align: center;
    margin-bottom: 20px;
}

        header {
            background: transparent;
            color: white;
            padding: 20px;
            text-align: center;
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

        .form-container {
            background:linear-gradient(to right, #f3e5f5, #ffffff, #f3e5f5);
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            position: relative;
        }

        h2 {
            text-align: center;
            color: #6a1b9a;
        }

        label {
            font-weight: bold;
            color: #4a148c;
        }

        .info-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-row div {
            flex: 1;
        }

        select{
            width: 90%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        input[type="text"], input[type="number"] {
            width: 85%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            width: 100%;
            margin: 20px auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        table td {font-size:16px;}

        table th {
            background-color: #ede7f6;
            color: #4a148c;
        }

        tr:nth-child(even) {
            background-color: #f2eafa;
        }

        input[type="submit"] {
            margin-top: 25px;
            background-color: #6a1b9a;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #4a148c;
        }

            input[type="reset"] {
            margin-top: 25px;
            background-color: #6a1b9a;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        input[type="reset"]:hover {
            background-color:  #4a148c;
        }


        .types {
            margin-left:20px;
            margin-right:10px;
            color: black;
            font-weight: 500;
            display: inline-block;
            min-width: 50px;
        }

        .top-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
        }

        .backbtn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 10px 15px;
            background: #ede7f6;
            color: #311b92;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .backbtn:hover {
            background-color: #d1c4e9;
        }

        .logout-button {
            display:inline-flex;
            background: linear-gradient(to right, #7e57c2, #5e35b1); /* Red gradient */
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            margin-top: 15px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            transition: background 0.4s ease, transform 0.3s ease;
        }

        .logout-button:hover {
            background: linear-gradient(to right, #5e35b1, #311b92);
            transform: translateY(-4px); /* Lift effect */
            box-shadow: 0 6px 15px rgba(255, 87, 34, 0.3); /* Glow effect */
        }

        .theme-button {
            background: transparent;
            color: #6a1b9a;
            border: 2px solid #6a1b9a; /* Added border thickness */
            padding: 10px 30px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease; /* Added color transition */
            display: block;
            margin-left: auto;
            margin-right: auto;
            font-weight:bold;
        }

        .theme-button:hover {
            background-color: #6a1b9a; /* Hover changes background to purple */
            color: white; /* Text color changes on hover */
        }

        .theme-button:focus {
            outline: none;
        }

    </style>
</head>
<body>
    <div class="page-wrapper">
    <header>
        <img src="logo1.png" alt="Stationery Management Logo" class="logo">
        <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Place Order</h1>
    </header>

    <div class="form-container">
    <div class="top-buttons">
    <a href="view_order.php" class="backbtn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </a>
    <a href="logout.php" class="logout-button">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
    </div>

<h2>Place Order</h2>
        <form action="process_form.php" method="POST">

            <!-- Row 1: Year, Month -->
            <div class="info-row">
                <div>
                    <label for="year">Year:</label>
                    <select <?= $editMode ? '' : 'name="year"' ?> id="year" required <?= $editMode ? 'disabled' : '' ?>>

                        <?php
                        for ($y = date("Y") - 1; $y <= date("Y") + 3; $y++) {
                            echo "<option value='$y'" . ($y == $currentYear ? " selected" : "") . ">$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="month">Month:</label>
                    <select <?= $editMode ? '' : 'name="month"' ?> id="month" required <?= $editMode ? 'disabled' : '' ?>>

                        <?php
                        $months = range(1, 12);
                        foreach ($months as $m) {
                            $monthName = date('F', mktime(0, 0, 0, $m, 10));
                            echo "<option value='$monthName'" . ($monthName == $currentMonth ? " selected" : "") . ">$monthName</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <?php if ($editMode): ?>
                <input type="hidden" name="month" value="<?= htmlspecialchars($existingOrder['month']) ?>">
                <input type="hidden" name="year" value="<?= htmlspecialchars($existingOrder['year']) ?>">
                <input type="hidden" name="edit_mode" value="1">
            <?php endif; ?>


            <!-- Row 2: Subject No & Branch -->
            <div class="info-row">
                <div>
                    <label for="subject">Subject No:</label>
                    <input type="text" name="subject" id="subject" value="<?php echo htmlspecialchars($subject); ?>" readonly>
                </div>
                <div>
                    <label for="branch">Branch:</label>
                    <input type="text" name="branch" id="branch" value="<?php echo htmlspecialchars($branch); ?>" readonly>
                </div>
            </div>

            <!-- Row 3: Name -->
            <?php
            $readonly_common = in_array(strtolower($_SESSION['password']), ['est0', 'dev0', 'acc0']);
            $auto_name = strtoupper('අංශයට');
            ?>

            <label for="name">Name:</label>
            <input 
            type="text" 
            name="name" 
            id="name"  
            required 
            autofocus 
            value="<?= $editMode ? htmlspecialchars($existingOrder['name']) : ($readonly_common ? $auto_name : '') ?>" 
            <?= $readonly_common ? 'readonly' : '' ?>
        >


<table>
<thead>
<tr><th>Item</th><th>Quantity</th></tr>
</thead>

<?php
function render_category($products, $categories, $tbody_id=null, $toggle=false){
    // Custom order for types
    $type_order = ['pens','papers','general','cr_books','glues','covers','files','tapes'];

    if($tbody_id) echo "<tbody id='$tbody_id' style='display:".($toggle?'none':'table-row-group')."'>";

    foreach($products as $cat => $subcats){
        if(in_array(strtolower($cat), $categories)){

            // Main Category Heading Row
            echo "<tr><td colspan='2' style='background:#ede7f6; font-weight:bold; text-align:center;'>"
                . strtoupper($cat) . "</td></tr>";

            // Sort types by custom order
            uksort($subcats, function($a, $b) use ($type_order) {
                $aIndex = array_search(strtolower($a), $type_order);
                $bIndex = array_search(strtolower($b), $type_order);
                $aIndex = $aIndex === false ? PHP_INT_MAX : $aIndex;
                $bIndex = $bIndex === false ? PHP_INT_MAX : $bIndex;
                return $aIndex <=> $bIndex;
            });

            // Render types
            foreach($subcats as $sub => $items){
                echo "<tr>";
                echo "<td style='font-weight:bold; background:#f9f9f9;'>".strtoupper(str_replace('_',' ', $sub))."</td>";

                echo "<td>";
                echo "<div style='display:flex; flex-wrap:wrap; gap:10px; align-items:center;'>"; // FLEX container

                foreach($items as $item){
                    // Clean name
                    $clean_name = ucwords(str_replace('_',' ', $item));
                    
                    $safe_name = strtolower(str_replace(' ','_', $clean_name));
                    $id = $safe_name;

                    // Each product wrapped in its own mini-box
                    echo "<div style='display:flex; flex-direction:column; align-items:center; min-width:70px;'>";
                    echo "<label for='$id' class='types' style='font-size:0.85em; text-align:center;'>$clean_name</label>";
                    $val = isset($GLOBALS['orderValues'][$safe_name]) ? (int)$GLOBALS['orderValues'][$safe_name] : 0;

echo "<input type='number' name='order[$safe_name]' id='$id' min='0' value='$val' style='width:60px; text-align:center;'>";

                    echo "</div>";
                }

                echo "</div>";
                echo "</td>";
                echo "</tr>";
            }
        }
    }

    if($tbody_id) echo "</tbody>";
}

// Render categories
render_category($products,['stationery']);            // always visible
render_category($products,['other'],'fileSection',true);
render_category($products,['hardware'],'printerSection',true);
?>
</table>

<div style="display: flex; gap: 10px; margin-top: 20px;">
    <button type="button" onclick="toggleSection('fileSection')" class="theme-button">Other</button>
    <?php if ($readonly_common): ?>
    <button type="button"  onclick="toggleSection('printerSection')" class="theme-button">Hardware</button>
    <?php endif; ?>
</div>

<div style="margin-top:15px; display:flex; gap:10px; position: sticky; bottom: 0;z-index: 1000;">
    <input type="submit" value="Submit Order" style="flex:1; padding:10px; background:#6a1b9a; color:white; border:none; border-radius:5px;">
    <input type="reset" value="Reset Form" style="flex:1; padding:10px; background:#6a1b9a; color:white; border:none; border-radius:5px;">
</div>

</form>
</div>
    </div>
<script>
function toggleSection(id){
    const sec = document.getElementById(id);
    sec.style.display = (sec.style.display==='none')?'table-row-group':'none';
}

// Prevent submit if all quantities are 0
document.querySelector("form").addEventListener("submit", function(e){
    const total = Array.from(this.querySelectorAll('input[type=number]')).reduce((sum,i)=>sum+parseInt(i.value||0),0);
    if(total===0){ e.preventDefault(); alert("Please order at least one item."); }
});
</script>

</body>
</html>
