<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = strtolower(trim($_POST['username'])); // branch name
    $password = strtolower(trim($_POST['password'])); // user password

    $_SESSION['username'] = $username;
    $_SESSION['password'] = $password;

    // ADMIN login
    if ($username === 'admin' && $password === 'ad123') {
        header("Location: dashboard.php");
        exit;
    }

    // ESTABLISHMENT
    if ($username === 'establishment') {
        if (preg_match('/^est\d+$/', $password)|| $password ==='sec') {
            header("Location: view_order.php");
        } elseif (in_array($password, ['as1', 'as2','ao'])) {
            header("Location: user_dashboard.php");
        } else {
            invalidLogin();
        }
        exit;
    }

    // DEVELOPMENT
    if ($username === 'development') {
        if (preg_match('/^dev\d+$/', $password)|| $password ==='to') {
            header("Location: view_order.php");
        } elseif (in_array($password, ['ad1', 'ad2', 'to'])) {
            header("Location: user_dashboard.php");
        } else {
            invalidLogin();
        }
        exit;
    }

    // ACCOUNT
    if ($username === 'account') {
        if (preg_match('/^acc\d+$/', $password)) {
            header("Location: view_order.php");
        } 
        elseif ($password === 'abc') {
            header("Location: user_dashboard.php");
        } elseif ($password === 'acc') {
            header("Location: account_dashboard.php");
        } else {
            invalidLogin();
        }
        exit;
    }

    // Fallback
    invalidLogin();
}

function invalidLogin() {
    echo "<script>alert('Invalid username or password.'); window.location.href='login.html';</script>";
    exit;
}
?>
