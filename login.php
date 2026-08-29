<?php

session_start();

require_once "config/db.php";


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
        WHERE username = ? OR email = ?
        LIMIT 1";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database error.");

}


// Bind login value twice:
// once for username
// once for email

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $login,
    $login
);


// Execute query

mysqli_stmt_execute($stmt);


// Get result

$result = mysqli_stmt_get_result($stmt);


// ==========================================
// CHECK USER
// ==========================================

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    mysqli_close($conn);

    echo "
        <script>
            alert('User not found.');
            window.location.href = 'homepage/html/login.html';
        </script>
    ";

    exit();

}


// Get user data

$row = mysqli_fetch_assoc($result);


// ==========================================
// CHECK ACCOUNT STATUS
// ==========================================

if ($row['status'] !== 'active') {

    mysqli_stmt_close($stmt);

    mysqli_close($conn);

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

    mysqli_stmt_close($stmt);

    mysqli_close($conn);

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
     WHERE user_id = ?";


$updateStmt =
    mysqli_prepare($conn, $updateLogin);


if ($updateStmt) {

    mysqli_stmt_bind_param(
        $updateStmt,
        "i",
        $row['user_id']
    );

    mysqli_stmt_execute($updateStmt);

    mysqli_stmt_close($updateStmt);

}


// Close original statement

mysqli_stmt_close($stmt);


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

        mysqli_close($conn);

        die("Invalid user role.");

}


mysqli_close($conn);

?>