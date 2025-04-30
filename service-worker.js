const CACHE_NAME = 'todo-app-cache-v1';
const URLS_TO_CACHE = [
  '/',
  '/dashboard.php',
  '/manifest.json',
  'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js',
  'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css',
  'https://cdn.tailwindcss.com'
];

// Install
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(URLS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activate
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keyList => {
      return Promise.all(keyList.map(key => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    })
  );
  self.clients.claim();
});

// Fetch
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
