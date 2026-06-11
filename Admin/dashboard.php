<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

// Protect page
if (empty($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Get current logged-in user
$currentUserId = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT id, name, email, role
    FROM users
    WHERE id = ?
");
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all users
$stmt = $pdo->query("
    SELECT id, name, email, created_at, grade_level, role
    FROM users
    ORDER BY id ASC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<style>
body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#87a5e6;
}

.container{
    display:flex;
    min-height:100vh;
}

.sidebar{
    width:250px;
    padding:20px;
}

.profile-box{
    border:2px solid #3498db;
    padding:20px;
    background:white;
    border-radius:10px;
    margin-bottom:20px;
    font-weight:bold;
}

.menu{
    background:rgba(255,255,255,.2);
    border-radius:15px;
    padding:10px;
}

.menu a{
    display:block;
    padding:12px;
    text-decoration:none;
    color:#000;
    margin:5px 0;
}

.menu a:hover{
    background:rgba(255,255,255,.3);
    border-radius:8px;
}

.content{
    flex:1;
    padding:20px;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.search-box input{
    padding:10px;
    width:300px;
    border-radius:20px;
    border:1px solid #ccc;
}

.add-btn{
    padding:10px 20px;
    border:none;
    border-radius:20px;
    background:#4a7df5;
    color:white;
    cursor:pointer;
}

table{
    width:100%;
    border-collapse:collapse;
    background:rgba(255,255,255,.4);
    border-radius:15px;
    overflow:hidden;
}

th, td{
    padding:12px;
    border:1px solid rgba(0,0,0,.2);
    text-align:left;
}

th{
    background:rgba(255,255,255,.6);
}

.role-admin{
    color:green;
    font-weight:bold;
}

.role-student{
    color:#333;
}

.logout{
    color:red;
}
</style>

</head>
<body>

<div class="container">

    <!-- Sidebar -->
    <div class="sidebar">

        <div class="profile-box">
            <?= htmlspecialchars($currentUser["name"]) ?>
        </div>

        <div class="menu">
            <a href="#">Student</a>
            <a href="#">Materials</a>
            <a href="#">Monitor</a>
            <a href="../logout.php" class="logout">Log Out</a>
        </div>

    </div>

    <!-- Main Content -->
    <div class="content">

        <div class="header">
            <div class="search-box">
                <input type="text" placeholder="Search user...">
            </div>

            <button class="add-btn">ADD STUDENT</button>
        </div>

        <h2>Student List</h2>

        <table>
            <thead>
                <tr>
                    <th>ID No#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Grade Level</th>
                    <th>Role</th>
                    <th>Created At</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6">No users found.</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user["id"]) ?></td>
                        <td><?= htmlspecialchars($user["name"]) ?></td>
                        <td><?= htmlspecialchars($user["email"]) ?></td>
                        <td><?= htmlspecialchars($user["grade_level"]) ?></td>

                        <td class="<?= $user["role"] === "admin"
                            ? "role-admin"
                            : "role-student" ?>">
                            <?= htmlspecialchars($user["role"]) ?>
                        </td>

                        <td><?= htmlspecialchars($user["created_at"]) ?></td>
                    </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>

</body>
</html>
