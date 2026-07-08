<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

// Role enforcement safeguard
if (empty($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aral Program - Home</title>
    <link rel="stylesheet" href="studentstyle.css">
</head>
<body>

<div class="app-container">

    <aside class="sidebar">
        <div class="logo-box">
            <div class="logo-placeholder">
                <span style="font-size: 12px; color: #1e3d75; font-weight: bold;">SCHOOL LOGO</span>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="home.php" class="nav-item active">
                <span class="icon">🏠</span> Home
            </a>
            <a href="materials.php" class="nav-item">
                <span class="icon">📖</span> Materials
            </a>
            <a href="progress.php" class="nav-item">
                <span class="icon">📈</span> Progress
            </a>
            <a href="settings.php" class="nav-item">
                <span class="icon">⚙️</span> Settings
            </a>
            <a href="../logout.php" class="nav-item logout-item">
                <span class="icon">🚪</span> Log out
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1>ARAL PROGRAM</h1>
        </header>

        <section class="dashboard-card">
            <div class="card-grid">
                
                <div class="quote-block">
                    <h2>It is a Skill, Not a Trait:</h2>
                    <p>Reading is a learned skill that improves with practice, not a natural talent you are born with.</p>
                </div>

                <div class="collage-block">
                    <div class="photo-frame p1">
                        <div class="img-mock dark-blue"></div>
                    </div>
                    <div class="photo-frame p2">
                        <div class="img-mock light-blue"></div>
                    </div>
                    <div class="photo-frame p3">
                        <div class="img-mock border-frame"></div>
                    </div>
                </div>

            </div>
        </section>
    </main>

</div>

</body>
</html>
