const CACHE_NAME = 'smart-gcm-cache-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/parte.php',
  '/partes_recebidas.php',
  '/minhas_partes.php',
  '/parte_nova.php',
  '/css/style.css',
  '/public/img/logo1.png',
  '/public/img/cabecalho.png',
  // Adicione outros arquivos importantes aqui
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
}); 