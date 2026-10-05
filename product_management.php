<?php
session_start();
require 'db.php';

$latestYear = date('Y'); // fallback

$yearResult = $conn->query("SELECT MAX(year) AS max_year FROM quantity_table");
if ($yearResult && $row = $yearResult->fetch_assoc()) {
    if (!empty($row['max_year'])) {
        $latestYear = (int)$row['max_year'];
    }
}

$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : $latestYear;

// Join products_table with quantity_table for the selected year
$query = "
SELECT 
    p.product_id, 
    p.product_name, 
    p.category, 
    p.type,

    COALESCE(
        q.minimum_qty,
        (
            SELECT q2.minimum_qty
            FROM quantity_table q2
            WHERE q2.product_id = p.product_id
            AND q2.year = $filterYear - 1
            LIMIT 1
        ),
        0
    ) AS minimum_qty,

    COALESCE((
    SELECT SUM(s_prev.remaining_qty)
    FROM supply_table s_prev
    WHERE s_prev.product_id = p.product_id
    AND s_prev.year = $filterYear - 1
    ), 0) AS opening_available,

    -- 🔹 Total Received
    COALESCE((
        SELECT SUM(s.quantity)
        FROM supply_table s
        WHERE s.product_id = p.product_id
        AND s.year = $filterYear
        AND s.supply_type IN ('PURCHASE','RECEIVED')
    ), 0) AS total_received,

    -- 🔹 Total Distributed
    COALESCE((
        SELECT SUM(d.quantity)
        FROM distribution_table d
        WHERE d.product_id = p.product_id
        AND d.year = $filterYear
    ), 0) AS total_distributed,

    -- 🔹 Currently Available (Remaining Stock)
    COALESCE((
        SELECT SUM(s2.remaining_qty)
        FROM supply_table s2
        WHERE s2.product_id = p.product_id
        AND s2.year = $filterYear
    ), 0) AS currently_available

FROM products_table p
LEFT JOIN quantity_table q
    ON p.product_id = q.product_id AND q.year = $filterYear

ORDER BY 
    FIELD(p.category, 'stationery','other','hardware'),
    p.product_id
";

$result = $conn->query($query);


$categories = []; // Array to group products by category

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category = $row['category'] ?? 'Uncategorized'; // Fallback if null
        $categories[$category][] = $row;
    }
}

// Build a list of unique categories dynamically from the products table
$allCategories = [];
$catResult = $conn->query("SELECT DISTINCT category FROM products_table ORDER BY category ASC");
if ($catResult && $catResult->num_rows > 0) {
    while ($catRow = $catResult->fetch_assoc()) {
        if (!empty($catRow['category'])) {
            $allCategories[] = $catRow['category'];
        }
    }
}
// If no categories exist yet, fallback to defaults
if (empty($allCategories)) {
    $allCategories = ['stationery', 'other', 'hardware'];
}

$preferredOrder = ['stationery', 'other', 'hardware'];
usort($allCategories, function($a, $b) use ($preferredOrder) {
    $posA = array_search(strtolower($a), $preferredOrder);
    $posB = array_search(strtolower($b), $preferredOrder);
    $posA = $posA === false ? PHP_INT_MAX : $posA;
    $posB = $posB === false ? PHP_INT_MAX : $posB;
    return $posA <=> $posB ?: strcmp($a, $b);
});



// 2. Handle updates if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['products'])) {
    foreach ($_POST['products'] as $product_id => $product_data) {
        $category = $product_data['category'];
        $type = $product_data['type'];
        $minimum_qty = (int) $product_data['minimum_qty'];

        // Update product category/type in products_table
        $update_product_query = "UPDATE products_table 
                                 SET category = ?, `type` = ?
                                 WHERE product_id = ?";
        if ($stmt = $conn->prepare($update_product_query)) {
            $stmt->bind_param("ssi", $category, $type, $product_id);
            $stmt->execute();
            $stmt->close();
        }

        // Upsert (insert or update) quantity for this year
       $upsert_query = "INSERT INTO quantity_table (product_id, year, minimum_qty)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            minimum_qty = VALUES(minimum_qty)";

        if ($stmt2 = $conn->prepare($upsert_query)) {
            $stmt2->bind_param("iii", $product_id, $filterYear, $minimum_qty);
            $stmt2->execute();
            $stmt2->close();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?year=" . $filterYear);
    exit();
}

?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stationary Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Existing styling */
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

        header {
            background: transparent;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .logo {
            width: 80px;
            margin-bottom: 10px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin: 20px auto;
            width: 90%;
            max-width: 1100px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        h2 {
            color: #6a1b9a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: linear-gradient(to right, #f3e5f5, #ffffff, #f3e5f5);
        }

        table th, table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: #6a1b9a;
            color: white;
            font-weight: bold;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
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

        .success-message {
            color: green;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }

        /* Parent container needs position relative */
        .container {
            position: relative; /* Ensures that the button is positioned within this container */
        }

        /* Styling for the back button */
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
    
        /* Container for the buttons */
        .button-group {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px; 
            margin-bottom: 20px;
            width: 100%; 
            max-width: 800px; 
            margin-left: auto; 
            margin-right: auto; 
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Individual buttons within the button group */
        .action-btn,
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #6a1b9a;
            color: white;
            font-weight: bold;
            font-size: 14px;
            padding: 15px 15px;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.4);
            transition: background 0.3s ease-in-out, transform 0.2s;
            flex: 1; /* Ensures all buttons take equal space */
            min-width: 120px; /* Ensures minimum width for buttons */
            text-align: center; /* Center the text inside the buttons */
        }

        /* Icons inside the buttons */
        .action-btn i,
        .btn i {
            margin-right: 8px;
        }

        /* Hover effect for buttons */
        .action-btn:hover,
        .btn:hover {
            background: linear-gradient(to right, #7e57c2, #5e35b1);
            transform: scale(1.08);
        }

        /* Active effect for buttons */
        .action-btn:active,
        .btn:active {
            transform: scale(0.95);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
        }

        .year-filter-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
        }

        .year-label {
            font-size: 16px;
            font-weight: bold;
            margin-right: 10px;
            color: #6a1b9a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .year-label i {
            color: #6a1b9a;
        }

        .year-dropdown {
            padding: 8px 15px;
            font-size: 16px;
            border-radius: 30px;
            border: 2px solid #6a1b9a;
            background: white;
            color: #6a1b9a;
            font-weight: bold;
            outline: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .year-dropdown:hover,
        .year-dropdown:focus {
            background: white;
            color:  #6a1b9a;
            transform: scale(1.05);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2);
        }

        .category-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #bbb;
            font-size: 14px;
            background-color: #fff;
        }

        .category-select:focus {
            border-color: #6a1b9a;
            outline: none;
        }
    </style>
</head>

<body>
    <header>
        <img src="logo1.png" alt="Stationery Management Logo" class="logo">
        <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Products Management</h1>
    </header>

    <form action="" method="POST">
        <div class="button-group">
            <button class="action-btn">
                <i class="fas fa-save"></i> Save
            </button>
            <a href="add_delete.php" class="action-btn">
                <i class="fas fa-edit"></i> Add/Delete
            </a>
            <a href="orders_summary.php" class="action-btn">
                <i class="fas fa-clipboard-list"></i> Summary
            </a>
        </div>
        
        <div class="main-content">
            <section class="product-overview">
                <a href="dashboard.php" class="backbtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>

                <div class="year-filter-container">
                    <label for="yearSelect" class="year-label">
                        <i class="fas fa-calendar-alt"></i> Select Year:
                    </label>
                    <select id="yearSelect" class="year-dropdown"
                            onchange="window.location.href='?year=' + this.value">
                        <?php
                        $currentYear = date('Y');
                        $years = range(2025, $currentYear+2); // from next year down to 5 years back
                        foreach ($years as $y) {
                            $selected = ($y == $filterYear) ? "selected" : "";
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>


                <h2>Products Overview</h2>

                <?php if (!empty($categories)) { ?>
                    <?php foreach ($categories as $category_name => $products) { ?>
                        <h3 style="margin-top: 20px;"><?= htmlspecialchars($category_name) ?></h3>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Type</th>                                       
                                        <th>Minimum Quantity</th>
                                        <th>Available <br>(Year Start)</th>
                                        <th>Total Received</th>
                                        <th>Total Distributed</th>
                                        <th>Currently Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                <td class="category-td">
                                                    <select name="products[<?= $product['product_id'] ?>][category]" class="category-select">
                                                    <?php foreach ($allCategories as $cat) { ?>
                                                        <option value="<?= htmlspecialchars($cat) ?>" 
                                                            <?= $cat === $product['category'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       name="products[<?= $product['product_id'] ?>][type]" 
                                                       value="<?= htmlspecialchars($product['type']) ?>">
                                            </td>
                                                <td>
                                                    <input type="number" 
                                                        name="products[<?= $product['product_id'] ?>][minimum_qty]"
                                                        value="<?= $product['minimum_qty'] ?>">
                                                </td>
                                                <td style="font-weight:bold; color:#1565c0;">
                                                    <?= number_format($product['opening_available']) ?>
                                                </td>
                                                <td><?= number_format($product['total_received']) ?></td>

                                                <td><?= number_format($product['total_distributed']) ?></td>

                                                <td style="font-weight:bold; color:
                                                    <?= ($product['currently_available'] < $product['minimum_qty']) ? 'red' : 'green' ?>">
                                                    <?= number_format($product['currently_available']) ?>
                                                </td>                                            
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>No products found.</p>
                <?php } ?>
            </section>
        </div>
    </form>

</body>
</html>
