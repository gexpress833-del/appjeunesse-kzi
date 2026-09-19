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

const ensureFirebase = async () => {
  if (firebaseReady || typeof firebase === 'undefined') {
    return;
  }

  const config = await getFirebaseConfig();

  if (!config || !config.projectId || !config.messagingSenderId || !config.appId) {
    return;
  }

  firebase.initializeApp(config);
  firebaseReady = true;

  const messaging = firebase.messaging();

  messaging.onBackgroundMessage((payload) => {
    const title = payload?.notification?.title || 'Nouvelle notification';
    const body = payload?.notification?.body || 'Vous avez un nouveau message.';

    self.registration.showNotification(title, {
      body,
      icon: '/logoEglise.jpg',
      badge: '/logoEglise.jpg',
      data: { url: payload?.data?.click_action || '/notifications' },
      tag: 'appjeunesse-push',
    });
  });
};

self.addEventListener('push', (event) => {
  if (event.data) {
    const payload = event.data.json();
    const title = payload?.notification?.title || 'Nouvelle notification';
    const body = payload?.notification?.body || 'Vous avez un nouveau message.';
    const url = payload?.data?.click_action || '/notifications';

    event.waitUntil(
      clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
        for (const client of clientList) {
          client.postMessage({
            type: 'app-push',
            title,
            body,
            url,
          });
        }

        return self.registration.showNotification(title, {
          body,
          icon: '/logoEglise.jpg',
          badge: '/logoEglise.jpg',
          data: { url },
          tag: 'appjeunesse-push',
        });
      })
    );
  }
});

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
