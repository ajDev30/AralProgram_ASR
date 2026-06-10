<?php
session_start();

// Corrected relative path to your configuration file
require_once __DIR__ . '/.env/config.php';

// REGISTER LOGIC
if (isset($_POST['register'])) {

    $name  = trim($_POST['name']);
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $glvl  = $_POST['grade_level'];

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, grade_level)
            VALUES (:name, :email, :password, :grade_level)
        ");

        $executed = $stmt->execute([
            ':name'        => $name,
            ':email'       => $email,
            ':password'    => $password,
            ':grade_level' => $glvl
        ]);

        if ($executed) {
            $_SESSION['register_success'] = 'Registration successful! Please log in.';
            $_SESSION['active_form'] = 'login';
        } else {
            $_SESSION['register_error'] = 'Something went wrong. Please try again.';
            $_SESSION['active_form'] = 'register';
        }
    }

    header("Location: index.php");
    exit();
}

// LOGIN LOGIC
if (isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'admin') {
          header ("Location: Admin/dashboard.php");
        } else {
          header ("Location: landing.html");
        }
        exit();
    } else {
        $_SESSION['login_error'] = 'Incorrect email or password.';
        $_SESSION['active_form']  = 'login';

        header("Location: index.php");
        exit();
    }
}
?>
