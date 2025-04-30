<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
  try {
    $stmt->execute([$name, $email, $password]);
    header("Location: login.php");
  } catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
  }
}
?>

<form method="POST">
  <h2>Register</h2>
  <input name="name" placeholder="Name" required><br>
  <input name="email" type="email" placeholder="Email" required><br>
  <input name="password" type="password" placeholder="Password" required><br>
  <button type="submit">Register</button>
</form>
