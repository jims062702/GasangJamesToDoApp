<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: auth/login.php");
  exit;
}
require_once './includes/db.php';

// Handle marking a task as done
if (isset($_GET['done'])) {
  $taskId = intval($_GET['done']);
  $stmt = $pdo->prepare("UPDATE tasks SET is_done = 1 WHERE id = ? AND user_id = ?");
  $stmt->execute([$taskId, $_SESSION['user_id']]);
  header("Location: dashboard.php?undo_success=$taskId");
  exit;
}

// Handle undo
if (isset($_GET['undo'])) {
  $taskId = intval($_GET['undo']);
  $stmt = $pdo->prepare("UPDATE tasks SET is_done = 0 WHERE id = ? AND user_id = ?");
  $stmt->execute([$taskId, $_SESSION['user_id']]);
  header("Location: dashboard.php");
  exit;
}

// Handle deleting a task
if (isset($_GET['delete'])) {
  $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
  $stmt->execute([intval($_GET['delete']), $_SESSION['user_id']]);
  header("Location: dashboard.php");
  exit;
}

// Handle new task submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, due_date, priority) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([
    $_SESSION['user_id'],
    $_POST['title'],
    $_POST['description'],
    $_POST['due_date'],
    $_POST['priority']
  ]);
}

// Filtering
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';

$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$_SESSION['user_id']];

if ($statusFilter === 'done') {
  $query .= " AND is_done = 1";
} elseif ($statusFilter === 'pending') {
  $query .= " AND is_done = 0";
}
if ($priorityFilter) {
  $query .= " AND priority = ?";
  $params[] = $priorityFilter;
}

$query .= " ORDER BY display_order ASC, due_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Notifications
$upcomingTasks = array_filter($tasks, fn($task) =>
  !$task['is_done'] && strtotime($task['due_date']) == strtotime(date("Y-m-d"))
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>To-Do Dashboard</title>
  <link rel="icon" href="/icons/icon-192.png" sizes="192x192">
  <link rel="apple-touch-icon" href="/icons/icon-512.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#1a202c" />
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">📆 Your To-Do Dashboard</h1>
      <a href="auth/logout.php" class="text-red-500">Logout</a>
    </div>

    <button onclick="toggleModal(true)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-4">➕ Add Task</button>

    <!-- Modal -->
    <div id="taskModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-4">Add a New Task</h2>
        <form method="POST">
          <input type="text" name="title" placeholder="Task Title" required class="border p-2 w-full mb-2" />
          <textarea name="description" placeholder="Task Description" class="border p-2 w-full mb-2"></textarea>
          <input type="date" name="due_date" required class="border p-2 w-full mb-2" />
          <select name="priority" class="border p-2 w-full mb-4" required>
            <option value="High">🔥 High</option>
            <option value="Medium" selected>⚖️ Medium</option>
            <option value="Low">🌿 Low</option>
          </select>
          <div class="flex justify-between">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Task</button>
            <button type="button" onclick="toggleModal(false)" class="text-gray-500">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="mb-4 flex flex-wrap gap-2 items-center">
      <select name="status" class="border px-3 py-2 rounded">
        <option value="">📝 All Status</option>
        <option value="done" <?= $statusFilter === 'done' ? 'selected' : '' ?>>✅ Done</option>
        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
      </select>
      <select name="priority" class="border px-3 py-2 rounded">
        <option value="">📊 All Priority</option>
        <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>🔥 High</option>
        <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>⚖️ Medium</option>
        <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>🌿 Low</option>
      </select>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">🔍 Filter</button>
    </form>

    <!-- Task List -->
    <div id="taskList" class="space-y-4">
      <?php foreach ($tasks as $task): ?>
        <div class="p-4 rounded border <?= $task['is_done'] ? 'bg-gray-200 line-through text-gray-500' : 'bg-gray-50' ?>" data-id="<?= $task['id'] ?>">
          <div class="font-bold"><?= htmlspecialchars($task['title']) ?></div>
          <div class="text-sm"><?= nl2br(htmlspecialchars($task['description'])) ?></div>
          <div class="text-sm">Due: <?= $task['due_date'] ?></div>
          <span class="text-xs font-semibold px-2 py-1 rounded text-white <?= match($task['priority']) {
            'High' => 'bg-red-500', 'Medium' => 'bg-yellow-400', default => 'bg-green-400'
          } ?>">
            <?= $task['priority'] ?>
          </span>
          <div class="mt-2 space-x-2">
            <?php if (!$task['is_done']): ?>
              <a href="?done=<?= $task['id'] ?>" class="text-green-600">✅ Done</a>
            <?php else: ?>
              <a href="?undo=<?= $task['id'] ?>" class="text-yellow-600">↩️ Undo</a>
            <?php endif; ?>
            <a href="?delete=<?= $task['id'] ?>" class="text-red-500">🗑️ Delete</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Calendar -->
    <div id="calendar" class="mt-10"></div>
  </div>

  <script>
    function toggleModal(show) {
      document.getElementById('taskModal').classList.toggle('hidden', !show);
      document.getElementById('taskModal').classList.toggle('flex', show);
    }

    // Notifications
    document.addEventListener("DOMContentLoaded", () => {
      if ('Notification' in window && Notification.permission !== 'granted') {
        Notification.requestPermission();
      }

      <?php if (!empty($upcomingTasks)): ?>
      const tasks = <?= json_encode(array_column($upcomingTasks, 'title')) ?>;
      if (Notification.permission === 'granted') {
        tasks.forEach(title => new Notification("📌 Task Due Today", {
          body: title,
          icon: "https://cdn-icons-png.flaticon.com/512/1828/1828884.png"
        }));
      }
      <?php endif; ?>
    });

    // Calendar setup
    document.addEventListener("DOMContentLoaded", () => {
      const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        events: [
          <?php foreach ($tasks as $task): ?>
          {
            title: <?= json_encode($task['title']) ?>,
            start: <?= json_encode($task['due_date']) ?>,
            color: <?= json_encode(match($task['priority']) {
              'High' => '#f87171',
              'Medium' => '#facc15',
              default => '#34d399'
            }) ?>
          },
          <?php endforeach; ?>
        ]
      });
      calendar.render();
    });

    // Drag-and-drop ordering
    new Sortable(document.getElementById('taskList'), {
      animation: 150,
      onEnd: function () {
        const orderedIds = [...document.querySelectorAll('#taskList [data-id]')].map(el => el.dataset.id);
        fetch('update_order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order: orderedIds })
        });
      }
    });

    // Service worker for PWA
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js')
        .then(reg => console.log("Service Worker Registered", reg))
        .catch(err => console.error("Service Worker Failed", err));
    }
  </script>
</body>
</html>
