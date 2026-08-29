<?php

session_start();

require_once "config/db.php"; // gives you $pdo


// ==========================================
// ONLY ACCEPT POST REQUEST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: homepage/html/signup.html");

    exit();
}


// ==========================================
// GET FORM DATA
// ==========================================

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$username = trim($_POST['username'] ?? '');
$college_name = trim($_POST['college_name'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';


// ==========================================
// CHECK EMPTY FIELDS
// ==========================================

if (
    empty($full_name) ||
    empty($email) ||
    empty($phone) ||
    empty($username) ||
    empty($college_name) ||
    empty($password) ||
    empty($role)
) {

    die("All fields are required.");

}


// ==========================================
// VALIDATE FULL NAME
// ==========================================

if (!preg_match("/^[A-Za-z ]{3,100}$/", $full_name)) {

    die("Invalid full name.");

}


// ==========================================
// VALIDATE EMAIL
// ==========================================

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    die("Invalid email address.");

}


// ==========================================
// VALIDATE USERNAME
// ==========================================

if (!preg_match("/^[A-Za-z0-9_]{4,50}$/", $username)) {

    die("Invalid username.");

}


// ==========================================
// VALIDATE PHONE
// ==========================================

if (!preg_match("/^(97|98)[0-9]{8}$/", $phone)) {

    die("Enter a valid 10-digit Nepal phone number.");

}


// ==========================================
// VALIDATE PASSWORD
// ==========================================

$passwordPattern =
    "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{8,}$/";

if (!preg_match($passwordPattern, $password)) {

    die(
        "Password must be at least 8 characters and contain " .
        "at least one uppercase letter, one lowercase letter, " .
        "one number, and one special character."
    );

}


// ==========================================
// VALIDATE ROLE
// ==========================================

$allowedRoles = [
    "customer",
    "seller"
];

if (!in_array($role, $allowedRoles, true)) {

    die("Invalid role selected.");

}


// ==========================================
// CHECK EMAIL
// ==========================================

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {

    die("Email already exists.");

}


// ==========================================
// CHECK USERNAME
// ==========================================

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
$stmt->execute(['username' => $username]);

if ($stmt->fetch()) {

    die("Username already exists.");

}


// ==========================================
// HASH PASSWORD
// ==========================================

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);


// ==========================================
// PROFILE PHOTO
// ==========================================

$profilePic = "default.png";

if (
    isset($_FILES['profile_photo']) &&
    $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK
) {

    $file = $_FILES['profile_photo'];

    // Maximum file size = 2 MB
    if ($file['size'] > 2 * 1024 * 1024) {

        die("Profile photo must be less than 2 MB.");

    }


    // Check if uploaded file is an actual image
    $imageInfo = getimagesize($file['tmp_name']);

    if ($imageInfo === false) {

        die("Invalid profile photo.");

    }


    // Allowed MIME types
    $allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!in_array($imageInfo['mime'], $allowedTypes, true)) {

        die("Only JPG, PNG and WEBP images are allowed.");

    }


    // Create upload directory
    $uploadDirectory = __DIR__ . "/images/profile/";

    if (!is_dir($uploadDirectory)) {

        mkdir(
            $uploadDirectory,
            0755,
            true
        );

    }


    // Generate unique filename
    $extension = "";

    switch ($imageInfo['mime']) {

        case "image/jpeg":
            $extension = ".jpg";
            break;

        case "image/png":
            $extension = ".png";
            break;

        case "image/webp":
            $extension = ".webp";
            break;

    }


    $newFileName =
        uniqid("profile_", true) . $extension;


    $destination =
        $uploadDirectory . $newFileName;


    // Move uploaded file
    if (!move_uploaded_file(
        $file['tmp_name'],
        $destination
    )) {

        die("Failed to upload profile photo.");

    }


    $profilePic = $newFileName;

}


// ==========================================
// INSERT USER
// ==========================================

$sql = "INSERT INTO users
        (
            full_name,
            username,
            email,
            phone,
            password,
            role,
            college,
            profile_pic
        )
        VALUES (:full_name, :username, :email, :phone, :password, :role, :college, :profile_pic)";

$stmt = $pdo->prepare($sql);


// ==========================================
// EXECUTE INSERT
// ==========================================

try {

    $stmt->execute([
        'full_name'   => $full_name,
        'username'    => $username,
        'email'       => $email,
        'phone'       => $phone,
        'password'    => $hashedPassword,
        'role'        => $role,
        'college'     => $college_name,
        'profile_pic' => $profilePic,
    ]);

    echo "
        <script>
            alert('Account created successfully!');
            window.location.href =
                'homepage/html/login.html';
        </script>
    ";

    exit();

} catch (PDOException $e) {

    die(
        "Registration failed: " .
        $e->getMessage()
    );

}