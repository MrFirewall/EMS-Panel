console.log('Service Worker geladen.');

// Listener für eingehende Push-Nachrichten
self.addEventListener('push', function(event) {
    console.log('[SW] Push empfangen.');

    let data = {};
    if (event.data) {
        data = event.data.json();
    }

    const title = data.title || 'EMS Panel';
    const options = {
        body: data.body || 'Sie haben eine neue Benachrichtigung.',
        icon: data.icon || '/img/logo_192x192.png', // Du brauchst ein Icon hier
        badge: data.badge || '/img/logo_72x72.png',  // Und hier
        data: {
            url: data.url || '/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Listener für Klicks auf die Benachrichtigung
self.addEventListener('notificationclick', function(event) {
    event.notification.close(); 

    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clientList => {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});