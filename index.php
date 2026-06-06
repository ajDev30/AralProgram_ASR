<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 1. Check URL parameter first (?form=register), fallback to session, default to 'login'
$activeForm = $_GET['form'] ?? $_SESSION['active_form'] ?? 'login';

function isActiveForm($formName, $activeForm){
  return $formName == $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
  <title>ARAL Program ASR</title>
  <style>
    /* Quick fallback styles for error visibility */
    .error-msg { color: #ff3333; margin-bottom: 10px; font-weight: bold; }
    .success-msg { color: #22bb22; margin-bottom: 10px; font-weight: bold; }
  </style>
</head>

<body>
  <h2>ARAL Reading Assessment</h2>
  
  <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
    <h3>Login</h3>
    
    <?php 
    if (isset($_SESSION['login_error'])) {
        echo '<p class="error-msg">' . htmlspecialchars($_SESSION['login_error']) . '</p>';
        unset($_SESSION['login_error']);
    }
    if (isset($_SESSION['register_success'])) {
        echo '<p class="success-msg">' . htmlspecialchars($_SESSION['register_success']) . '</p>';
        unset($_SESSION['register_success']);
    }
    ?>

    <form action="register_process.php" method="post">
      <div class="imgcontainer">
        <img src="default_photo.jpg" alt="Profile" class="avatar">
      </div>
      <div class="container">
        <label for="email"><b>Email</b></label>
        <input type="email" name="email" placeholder="e.g., example@gmail.com" required>
        
        <label for="password"><b>Password</b></label>
        <input type="password" placeholder="Enter Password" name="password" required>
        
        <button type="submit" name="login">Login</button>
        <p>Don't have an account? <a href="?form=register" onclick="showForm('register-form'); return false;">Register</a></p>
      </div>
    </form>
  </div>

  <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
    <h3>Register</h3>

    <?php 
    if (isset($_SESSION['register_error'])) {
        echo '<p class="error-msg">' . htmlspecialchars($_SESSION['register_error']) . '</p>';
        unset($_SESSION['register_error']);
    }
    ?>

    <form action="register_process.php" method="post">
      <div class="container">
        <label for="name"><b>Full Name</b></label>
        <input type="text" name="name" placeholder="Input your name" required>
        
        <label for="email"><b>Email</b></label>
        <input type="email" name="email" placeholder="e.g., example@gmail.com" required>
        
        <label for="password"><b>Password</b></label>
        <input type="password" name="password" placeholder="Password" required>
        
        <label for="grade_level"><b>Grade Level</b></label>
        <select name="grade_level" required>
          <option value="G7">Grade 7</option>
          <option value="G8">Grade 8</option>
          <option value="G9">Grade 9</option>
          <option value="G10">Grade 10</option>
        </select>
        
        <button type="submit" name="register">Register</button>
        <p>Already have an account? <a href="?form=login" onclick="showForm('login-form'); return false;">Login</a></p>
      </div>
    </form>
  </div>

  <?php unset($_SESSION['active_form']); // Reset after rendering ?>
  <script src="scripts/script.js"></script>
</body>

</html>
