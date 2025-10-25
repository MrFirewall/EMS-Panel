// public/sw.js
// Version 4: Favicon Pfad korrigiert und erweiterte Debug-Logik

console.log('[SW] Service Worker geladen. Version 4.');

self.addEventListener('push', function(event) {
    console.log('[SW] Push Event gestartet.');

    let data = {};
    let urlToOpen = '/dashboard'; // Fallback-URL
    
    // --- KRITISCH: Icon Pfade ---
    // Der absolute öffentliche Pfad zum Favicon
    const ICON_PATH = '/favicon.ico'; 

    try {
        if (event.data) {
            data = event.data.json();
            console.log('[SW] Push Daten empfangen:', data);
            urlToOpen = data.url || urlToOpen;
        } else {
            console.log('[SW] Push Event hatte keine Daten.');
        }
    } catch (e) {
        console.error('[SW] Fehler beim Parsen der Push-Daten:', e);
        data.title = 'Fehler beim Parsen';
        data.body = 'Ungültige Push-Daten empfangen.';
    }

    const title = data.title || 'EMS Panel';
    const bodyText = data.body || 'Sie haben eine neue Benachrichtigung.';
    
    const options = {
        body: bodyText,
        // Nutzt das Favicon als Icon
        icon: ICON_PATH, 
        // badge: ICON_PATH, // Kann optional denselben Pfad nutzen
        vibrate: [200, 100, 200],
        data: {
            url: urlToOpen
        }
    };
    console.log('[SW] Notification Optionen:', options);

    try {
        const promise = self.registration.showNotification(title, options);
        console.log('[SW] self.registration.showNotification aufgerufen.');

        event.waitUntil(
            promise.then(() => {
                console.log('[SW] showNotification Promise erfolgreich.');
            }).catch(err => {
                console.error('[SW] Fehler bei showNotification Promise:', err);
            })
        );
    } catch (e) {
         console.error('[SW] Kritischer Fehler beim Aufruf von showNotification:', e);
    }
});

self.addEventListener('notificationclick', function(event) {
    console.log('[SW] Notification geklickt:', event.notification.data.url);
    event.notification.close(); 

    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(clientList => {
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

self.addEventListener('activate', event => {
    console.log('[SW] Service Worker aktiviert!');
    event.waitUntil(clients.claim()); 
});