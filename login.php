<?php

session_start();

require_once "config/db.php"; // this gives you $pdo


// ==========================================
// ONLY ACCEPT POST REQUEST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: homepage/html/login.html");

    exit();

}


// ==========================================
// GET LOGIN DATA
// ==========================================

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';


// ==========================================
// CHECK EMPTY FIELDS
// ==========================================

if ($login === '' || $password === '') {

    die("Please enter your username/email and password.");

}


// ==========================================
// SEARCH USERNAME OR EMAIL
// ==========================================

$sql = "SELECT
            user_id,
            full_name,
            username,
            email,
            password,
            role,
            status
        FROM users
        WHERE username = :login OR email = :login
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['login' => $login]);

$row = $stmt->fetch();


// ==========================================
// CHECK USER
// ==========================================

if (!$row) {

    echo "
        <script>
            alert('User not found.');
            window.location.href = 'homepage/html/login.html';
        </script>
    ";

    exit();

}


// ==========================================
// CHECK ACCOUNT STATUS
// ==========================================

if ($row['status'] !== 'active') {

    echo "
        <script>
            alert('Your account is not active.');
            window.location.href = 'homepage/html/login.html';
        </script>
    ";

    exit();

}


// ==========================================
// VERIFY PASSWORD
// ==========================================

if (!password_verify($password, $row['password'])) {

    echo "
        <script>
            alert('Incorrect password.');
            window.location.href = 'homepage/html/login.html';
        </script>
    ";

    exit();

}


// ==========================================
// LOGIN SUCCESS
// ==========================================


// Regenerate session ID for security

session_regenerate_id(true);


// Store user information

$_SESSION['user_id'] = $row['user_id'];

$_SESSION['full_name'] = $row['full_name'];

$_SESSION['username'] = $row['username'];

$_SESSION['email'] = $row['email'];

$_SESSION['role'] = $row['role'];


// ==========================================
// UPDATE LAST LOGIN
// ==========================================

$updateLogin =
    "UPDATE users
     SET last_login = CURRENT_TIMESTAMP
     WHERE user_id = :id";

$updateStmt = $pdo->prepare($updateLogin);
$updateStmt->execute(['id' => $row['user_id']]);


// ==========================================
// ROLE-BASED REDIRECTION
// ==========================================

switch ($row['role']) {

    case 'customer':

        header("Location: customer/dashboard.php");

        exit();


    case 'seller':

        header("Location: seller/dashboard.php");

        exit();


    case 'admin':

        header("Location: admin/dashboard.php");

        exit();


    default:

        // Invalid or unknown role

        session_unset();

        session_destroy();

        die("Invalid user role.");

}