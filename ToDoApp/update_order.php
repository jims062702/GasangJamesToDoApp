<?php
session_start();
require_once './includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_id']) || !isset($data['order'])) {
  http_response_code(400);
  echo json_encode(["error" => "Invalid request"]);
  exit;
}

$order = $data['order'];
$user_id = $_SESSION['user_id'];

foreach ($order as $position => $task_id) {
  $stmt = $pdo->prepare("UPDATE tasks SET display_order = ? WHERE id = ? AND user_id = ?");
  $stmt->execute([$position, $task_id, $user_id]);
}

echo json_encode(["success" => true]);
?>
