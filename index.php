<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>To-Do App</title>
  <link rel="manifest" href="/manifest.json" />
  <meta name="theme-color" content="#1a202c" />
  <link rel="icon" href="/assets/icon-192.png" />
  <style>
    body { font-family: sans-serif; text-align: center; padding: 2rem; }
  </style>
</head>
<body>
  <h1>Welcome to the To-Do App</h1>
  <p><a href="dashboard.php">Go to Dashboard</a></p>

  <script>
    // Register service worker (Step 3)
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/service-worker.js")
        .then(reg => console.log("Service Worker registered:", reg.scope))
        .catch(err => console.error("Service Worker failed:", err));
    }
  </script>
</body>
</html>
