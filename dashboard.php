<?php
session_start();
require 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selection Page - Stationary Management System</title>
    <link rel="stylesheet" href="style.css">

    <!-- Font Awesome CDN for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Calibri', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #ece9f0, #e4dcfa); /* Soft lavender gradient */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-image: url('img1.jpg');
        }

        header {
            background: transparent; 
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .logo {
            width: 80px;  
            height: auto;   
            margin-bottom: 10px; 
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .selection-container {
            text-align: center;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(93, 64, 156, 0.3); /* Enhanced depth */
            padding: 40px 30px;
            max-width: 600px;
            width: 100%;
            animation: fadeIn 1.2s ease-in-out;
            position:relative;
        }

        .selection-container h2 {
            margin-bottom: 25px;
            font-size: 1.8em;
            color: #4a148c;
            text-transform: uppercase;
        }

        .selection-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px; /* Add some space between the buttons */
            margin-bottom: 25px;
        }

        .selection-buttons a {
            display: flex;
            align-items: center;
            background: linear-gradient(to right, #7e57c2, #5e35b1); /* Smooth gradient */
            color: white;
            text-decoration: none;
            padding: 25px 20px;
            border-radius: 12px;
            font-size: 1.5em;
            font-weight: 600;
            transition: background 0.4s ease, transform 0.3s ease;
            width: 150px; /* Adjust width */
            justify-content: center;
        }

        .selection-buttons a:hover {
            background: linear-gradient(to right, #5e35b1, #311b92);
            transform: translateY(-4px); /* Lift effect */
            box-shadow: 0 6px 15px rgba(49, 27, 146, 0.3); /* Glow effect */
        }

        /* Icon Animation on Hover */
        .selection-buttons a i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .selection-buttons a:hover i {
            transform: rotate(360deg); /* Rotate icon when hovered */
        }

        /* Logout button */
        .logout-button {
            display: block;
            background: linear-gradient(to right, #7e57c2, #5e35b1); /* Red gradient */
            color: white;
            text-decoration: none;
            padding: 15px 0;
            margin-top: 15px;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: 600;
            text-align: center;
            transition: background 0.4s ease, transform 0.3s ease;
        }

        .logout-button:hover {
            background: linear-gradient(to right, #5e35b1, #311b92);
            transform: translateY(-4px); /* Lift effect */
            box-shadow: 0 6px 15px rgba(255, 87, 34, 0.3); /* Glow effect */
        }

        /* Fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .backbtn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 10px 15px;
            background: transparent;
            color: #311b92;
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

    </style>
</head>
<body>
    <header>
        <img src="logo1.png" alt="Stationary Management Logo" class="logo">
        <h1>Ministry of Agriculture <br> Stationery Management System Dashboard</h1>
    </header>

    <div class="main-content">
        <div class="selection-container">
        <a href="login.html" class="backbtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
            <h2>Please Select an Option:</h2>
            <div class="selection-buttons">
                <a href="product_management.php">
                    <i class="fas fa-box"></i> Product Management
                </a>
                <a href="order_management.php">
                    <i class="fas fa-clipboard-list"></i> Order Management
                </a>
                <a href="view_supply.php">
                    <i class="fas fa-clipboard-list"></i> Supply Management
                </a>
            </div>
            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
