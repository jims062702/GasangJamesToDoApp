<?php
$host = 'localhost';
$db   = 'todo_app_db';
$user = 'root';
$pass = ''; // update if needed
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
  die("DB connection failed: " . $e->getMessage());
}
