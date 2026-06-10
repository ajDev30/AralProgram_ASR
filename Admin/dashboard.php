<?php
session_start();
require_once __DIR__ . '/../.env/config.php';

// Optionally protect the dashboard so only logged-in admins can view it.
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$sql = "SELECT id, name, email, created_at, grade_level, role FROM users";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f4f4f4; }
  </style>
</head>
<body>
<section>
  <h3>Student list</h3>
  <table>
    <tr>
      <th>ID No#</th>
      <th>Name</th>
      <th>Email</th>
      <th>Grade Level</th>
      <th>Role</th>
      <th>Created At</th>
    </tr>
    <?php if (count($users) === 0): ?>
      <tr>
        <td colspan="6">No users found.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['id']) ?></td>
          <td><?= htmlspecialchars($user['name']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['grade_level']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td><?= htmlspecialchars($user['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</section>
</body>
</html>
