// public/sw.js
console.log('Service Worker geladen.');

// Listener für eingehende Push-Nachrichten
self.addEventListener('push', function(event) {
    console.log('[SW] Push Event gestartet.'); // NEU

    let data = {};
    try {
        if (event.data) {
            data = event.data.json();
            console.log('[SW] Push Daten empfangen:', data); // NEU: Daten loggen
        } else {
            console.log('[SW] Push Event hatte keine Daten.'); // NEU
        }
    } catch (e) {
        console.error('[SW] Fehler beim Parsen der Push-Daten:', e); // NEU: Fehler loggen
        // Fallback, damit showNotification nicht crasht
        data = { title: 'Fehler', body: 'Ungültige Push-Daten empfangen.', url: '/' };
    }

    const title = data.title || 'EMS Panel';
    const options = {
        body: data.body || 'Sie haben eine neue Benachrichtigung.',
        icon: data.icon || '/img/logo_192x192.png', // Stelle sicher, dass diese Datei existiert!
        badge: data.badge || '/img/logo_72x72.png',  // Stelle sicher, dass diese Datei existiert!
        data: { // Wichtig: Daten müssen hier verschachtelt sein
            url: data.url || '/'
        }
    };
    console.log('[SW] Notification Optionen vorbereitet:', options); // NEU: Optionen loggen

    try {
        const promise = self.registration.showNotification(title, options);
        console.log('[SW] self.registration.showNotification aufgerufen.'); // NEU

        event.waitUntil(
            promise.then(() => {
                console.log('[SW] showNotification Promise erfolgreich.'); // NEU: Erfolg loggen
            }).catch(err => {
                console.error('[SW] Fehler bei showNotification Promise:', err); // NEU: Fehler loggen
            })
        );
    } catch (e) {
         console.error('[SW] Kritischer Fehler beim Aufruf von showNotification:', e); // NEU: Kritischen Fehler loggen
    }

    console.log('[SW] Push Event Ende.'); // NEU
});

// Listener für Klicks auf die Benachrichtigung
self.addEventListener('notificationclick', function(event) {
     console.log('[SW] Notification geklickt:', event.notification.data.url); // NEU: Klick loggen
    event.notification.close();

    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(clientList => {
            // ... (restliche Klick-Logik bleibt gleich) ...
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

// Optional: Listener für Service Worker Aktivierung (zeigt, dass die neue Version läuft)
self.addEventListener('activate', event => {
  console.log('[SW] Service Worker aktiviert!');
  event.waitUntil(clients.claim()); // Übernimmt Kontrolle sofort
});