<?php
session_start();

$successMessage = '';
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

require 'db.php';

// Build a list of unique categories dynamically
$allCategories = [];
$catResult = $conn->query("SELECT DISTINCT category FROM products_table ORDER BY category ASC");
if ($catResult && $catResult->num_rows > 0) {
    while ($catRow = $catResult->fetch_assoc()) {
        if (!empty($catRow['category'])) {
            $allCategories[] = $catRow['category'];
        }
    }
}
// If no categories exist yet, provide defaults
if (empty($allCategories)) {
    $allCategories = ['stationery', 'other', 'hardware'];
}

if (isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $type = trim($_POST['type']);
    $minimum_qty = intval($_POST['minimum_qty']);
    $year = intval($_POST['year']);

    // 1. Insert into products_table
    $stmt = $conn->prepare(
        "INSERT INTO products_table (product_name, category, `type`)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $product_name, $category, $type);
    $stmt->execute();
    $new_product_id = $stmt->insert_id;
    $stmt->close();

    // 2. Insert into quantity_table for this year
    $stmt2 = $conn->prepare(
        "INSERT INTO quantity_table (product_id, year, minimum_qty)
         VALUES (?, ?, ?)"
    );
    $stmt2->bind_param("iii", $new_product_id, $year, $minimum_qty);
    $stmt2->execute();
    $stmt2->close();

    $_SESSION['success'] = "Product added successfully for $year!";
    header("Location: add_delete.php");
    exit();
}

if (isset($_POST['delete_products']) && !empty($_POST['product_ids'])) {
    $year = intval($_POST['year']);
    $ids = array_map('intval', $_POST['product_ids']);
    $ids_list = implode(',', $ids);

    if (!empty($ids_list)) {
        // 1. Delete from quantity_table for this year
        $conn->query("DELETE FROM quantity_table WHERE product_id IN ($ids_list) AND year = $year");

        // 2. Check if product exists in other years, if not delete from products_table
        foreach ($ids as $id) {
            $check = $conn->query("SELECT COUNT(*) AS c FROM quantity_table WHERE product_id = $id");
            $row = $check->fetch_assoc();
            if ($row['c'] == 0) {
                $conn->query("DELETE FROM products_table WHERE product_id = $id");
            }
        }

        $_SESSION['success'] = "Product(s) deleted successfully for $year!";
    }
    header("Location: add_delete.php");
    exit();
}

// Fetch all products
$query = "SELECT * FROM products_table";
$result = $conn->query($query);

// Fetch all products and group by category
$categories = [];
$productResult = $conn->query("SELECT * FROM products_table ORDER BY category ASC, product_id ASC");

if ($productResult && $productResult->num_rows > 0) {
    while ($row = $productResult->fetch_assoc()) {
        $cat = !empty($row['category']) ? $row['category'] : 'Uncategorized';
        $categories[$cat][] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - Stationary Management</title>
    <style>
    body {
        font-family: Calibri, sans-serif;
        background: url('img1.jpg') no-repeat center center fixed;
        background-size: cover;
        background-color: #f7f3fc; /* Fallback */
        padding: 20px;
        margin: 0;
        text-align: center;
        color: #333;
    }

    header {
            background: transparent;
            color: white;
            padding: 20px;
            text-align: center;
        }

    h3 {
        color: #6a1b9a;
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

    .btn-group {
        margin: 20px 0;
    }

    .btn {
        background-color: #6a1b9a;
        color: white;
        padding: 12px 25px;
        margin: 10px;
        font-weight: bold;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        transition: transform 0.2s, background 0.3s;
        position: sticky;
        bottom: 0;
        z-index: 1000;
    }

    .btn:hover {
        transform: scale(1.05);
        background: linear-gradient(to right, #7e57c2, #5e35b1);
    }

    .form-popup {
        display: none;
        margin: 20px auto;
        background: linear-gradient(to right, #f3e5f5, #ffffff, #f3e5f5);
        padding: 25px;
        border-radius: 15px;
        width: 60%;
        box-shadow: 0 6px 15px rgba(0,0,0,0.25);
    }

    input[type="text"], input[type="number"],select {
        padding: 10px;
        margin: 10px 0;
        width: 90%;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 16px;
    }

    table {
        width: 90%;
        margin: 30px auto;
        border-collapse: collapse;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        background: linear-gradient(to right, #f3e5f5, #ffffff, #f3e5f5);
        border-radius: 10px;
        overflow: hidden;
    }

    th, td {
        padding: 14px;
        border: 1px solid #ddd;
        text-align: left;
    }

    th {
        background-color: #6a1b9a;
        color: white;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: #f2eafa;
    }

    .success {
        color: green;
        font-weight: bold;
        margin-top: 10px;
    }

    /* Back Button */
    .backbtn {
        position: absolute;
        top: 20px;
        left: 20px;
        background-color: #6a1b9a;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .backbtn:hover {
        background-color: #5e35b1;
    }

    @media (max-width: 768px) {
        .form-popup, table {
            width: 95%;
        }

        input[type="text"], input[type="number"] {
            width: 95%;
        }
    }

    .icon {
    vertical-align: middle;
    margin-right: 8px;
}

</style>

    <script>
        function showSection(section) {
            document.getElementById("addForm").style.display = (section === 'add') ? 'block' : 'none';
            document.getElementById("deleteSection").style.display = (section === 'delete') ? 'block' : 'none';
        }
    </script>
</head>
<body>
<a href="product_management.php" class="backbtn">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6" />
    </svg>
</a>

<header>
        <img src="logo1.png" alt="Stationery Management Logo" class="logo">
        <h1 class="gradient-text">MINISTRY OF AGRICULTURE <br>Add / Remove Products</h1>
    </header>

    <?php if (!empty($successMessage)): ?>
    <p class="success"><?= $successMessage; ?></p>
<?php endif; ?>


<div class="btn-group">
    <button class="btn" onclick="showSection('add')">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add Product
    </button>

    <button class="btn" onclick="showSection('delete')">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
            <path d="M10 11v6"></path>
            <path d="M14 11v6"></path>
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
        </svg>
        Delete Product
    </button>
</div>

<!-- Add Product Form -->
<div id="addForm" class="form-popup">
    <form method="POST">
        <h3>Add Product</h3>

        <select name="year" required>
            <?php
            $currentYear = date("Y");
            for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                echo "<option value='$y'>$y</option>";
            }
            ?>
        </select><br>

        <input type="text" name="product_name" placeholder="Product Name" required><br>
        <select name="category" id="category" required>
            <option value="">-- Uncategorized --</option>
            <?php foreach ($allCategories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select><br>

        <input type="text" name="type" placeholder="Type (e.g. pens, papers,general,other,etc...)" required><br>
        <input type="number" name="minimum_qty" placeholder="Minimum Quantity" required><br>
        <button type="submit" name="add_product" class="btn">Add</button>
    </form>
</div>

        <!-- Delete Section -->
<div id="deleteSection" class="form-popup">
    <form method="POST">
        <h3>Delete Products</h3>

        <select name="year" required>
            <?php
            $currentYear = date("Y");
            for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                echo "<option value='$y'>$y</option>";
            }
            ?>
        </select><br><br>

        <h3>Delete Products</h3>

        <?php if (!empty($categories)) { ?>
            <?php foreach ($categories as $category_name => $products) { ?>
                <h4><?= htmlspecialchars($category_name) ?></h4>
                <table>
                    <tr>
                        <th>Select</th>
                        <th>Product Name</th>
                    </tr>
                    <?php foreach ($products as $product) { ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="product_ids[]" value="<?= $product['product_id']; ?>">
                            </td>
                            <td>
                                <?= htmlspecialchars($product['product_name']); ?>
                                <small>(<?= htmlspecialchars($product['type']); ?>)</small>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        <?php } else { ?>
            <p>No products found.</p>
        <?php } ?>

        <button type="submit" name="delete_products" class="btn">Delete Selected</button>
    </form>
</div>
</body>
</html>
