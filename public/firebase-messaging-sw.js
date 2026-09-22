importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

const getFirebaseConfig = async () => {
  try {
    const response = await fetch('/firebase-config');

    if (!response.ok) {
      return null;
    }

    return response.json();
  } catch (error) {
    return null;
  }
};

let firebaseReady = false;
let firebaseInitialization;

const ensureFirebase = async () => {
  if (firebaseReady) {
    return true;
  }

  if (firebaseInitialization) {
    return firebaseInitialization;
  }

  firebaseInitialization = (async () => {
    if (typeof firebase === 'undefined') {
      return false;
    }

    const config = await getFirebaseConfig();

    if (!config || !config.projectId || !config.messagingSenderId || !config.appId) {
      return false;
    }

    if (!firebase.apps.length) {
      firebase.initializeApp(config);
    }

    firebaseReady = true;
    const messaging = firebase.messaging();

    messaging.onBackgroundMessage((payload) => {
      const title = payload?.notification?.title || payload?.data?.title || 'Nouvelle notification';
      const body = payload?.notification?.body || payload?.data?.body || 'Vous avez un nouveau message.';
      const url = payload?.data?.click_action || payload?.fcmOptions?.link || '/notifications';

      clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
        for (const client of clientList) {
          client.postMessage({ type: 'app-push', title, body, url });
        }
      });
    });

    return true;
  })().catch((error) => {
    firebaseInitialization = null;
    console.error('Firebase messaging initialization failed', error);

    return false;
  });

  return firebaseInitialization;
};

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(Promise.all([
    self.clients.claim(),
    ensureFirebase(),
  ]));
});

ensureFirebase();

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/notifications';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === self.location.origin + url && 'focus' in client) {
          return client.focus();
        }
      }

      return clients.openWindow(url);
    })
  );
});
