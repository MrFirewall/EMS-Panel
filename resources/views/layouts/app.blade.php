<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'EMS Panel')</title>

    {{-- AdminLTE & FONT DEPENDENCIES --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    {{-- DATATABLES DEPENDENCIES --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap4.min.css">

    {{-- SELECT2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

    {{-- Custom Styles & Dark Mode Fixes --}}
    <style>
        /* Preloader */
        .preloader { background-color: #343a40; }
        .ekg-loader { width: 20vw; height: 10vw; max-width: 300px; max-height: 150px; min-width: 120px; min-height: 60px; }
        .ekg-loader path { stroke: #007bff; stroke-width: 4; stroke-dasharray: 1000; stroke-dashoffset: 1000; animation: draw 2s linear infinite; }
        @keyframes draw { to { stroke-dashoffset: 0; } }

        /* Dark Mode: List Group */
        .dark-mode .list-group-item { background-color: #343a40; border-color: #454d55; color: #f8f9fa; }
        .dark-mode a.list-group-item:hover, .dark-mode a.list-group-item:focus { background-color: #495057; color: #ffffff; }
        .dark-mode .text-muted { color: #adb5bd !important; }

        /* Dark Mode: Select2 Overrides */
        .dark-mode .select2-container--bootstrap4 .select2-selection,
        .dark-mode .select2-dropdown { background-color: #343a40; border-color: #6c757d; }
        .dark-mode .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { color: #fff; }
        .dark-mode .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b { border-color: #fff transparent transparent transparent; }
        .dark-mode .select2-search--dropdown .select2-search__field { background-color: #454d55; color: #fff; }
        .dark-mode .select2-container--bootstrap4 .select2-results__option--highlighted { background-color: #007bff; color: #fff; }
        .dark-mode .select2-container--bootstrap4 .select2-results__option { color: #dee2e6; }

        /* Select2: Multi-Select Tag Styling */
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice { background-color: #007bff; color: #fff !important; margin-top: 2px !important; margin-bottom: 2px !important; float: left; }
        .dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice { background-color: #3f6791; }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove { color: #fff !important; text-shadow: 0 1px 0 #495057; font-size: 1.5rem; line-height: 1; opacity: .5; background-color: transparent; border: 0; float: left; padding: 0 3px; margin: 0 1px 0 3px; font-weight: 700; }
        .dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover { color: #fff; text-decoration: none; }

        /* Select2 Multi Height Fixes */
        .select2-container--bootstrap4 .select2-selection--multiple { min-height: 38px; height: auto !important; padding-top: 5px !important; padding-bottom: 5px !important; }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered { line-height: normal; display: block; padding: 0; margin: 0; }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-search--inline { float: none !important; display: inline-block; width: 100%; }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field { min-width: 100px !important; }

        /* Navbar: Notification Badge Size */
        .main-header .navbar-badge { font-size: 0.75rem; padding: 3px 6px; top: 6px; right: 3px; font-weight: 700; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    {{-- PRELOADER --}}
    <div class="preloader flex-column justify-content-center align-items-center">
        <svg class="ekg-loader" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130 65">
            <path fill="none" d="M0,32.5 h20 l5,-20 l5,40 l5,-30 l5,10 h60"/>
        </svg>
    </div>

    {{-- NAVBAR --}}
    <nav class="main-header navbar navbar-expand navbar-white navbar-light" id="mainNavbar">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            {{-- Dark Mode Toggle --}}
            <li class="nav-item">
                <a class="nav-link" id="darkModeToggle" href="#" role="button">
                    <i class="fas fa-moon"></i>
                </a>
            </li>

            {{-- Notification Dropdown --}}
            <li class="nav-item dropdown" id="notification-dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge" id="notification-count" style="display: none;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="notification-list">
                    {{-- Content is loaded via JavaScript --}}
                    <div class="dropdown-item">Lade Benachrichtigungen...</div>
                </div>
            </li>

            {{-- User Dropdown --}}
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    @if(Auth::check()) {{-- Sicherstellen, dass der Benutzer eingeloggt ist --}}
                        <img src="{{ Auth::user()->avatar }}" class="user-image img-circle elevation-1" alt="User Image">
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    @if(Auth::check()) {{-- Sicherstellen, dass der Benutzer eingeloggt ist --}}
                        <li class="user-header bg-primary">
                            <img src="{{ Auth::user()->avatar }}" class="img-circle elevation-2" alt="User Image">
                            <p>
                                {{ Auth::user()->name }}
                                <small>{{ Auth::user()->rank ?? 'Mitarbeiter' }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                             <a href="{{ route('profile.show') }}" class="btn btn-default btn-flat">Profil</a>
                            <a href="#" class="btn btn-default btn-flat float-right"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Abmelden
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    @endif
                </ul>
            </li>
        </ul>
    </nav>

    {{-- MAIN SIDEBAR --}}
    <aside class="main-sidebar sidebar-dark-primary elevation-4" id="mainSidebar">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <i class="fas fa-ambulance brand-image img-circle elevation-3" style="opacity: .8"></i>
            <span class="brand-text font-weight-light">EMS Panel</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                @include('layouts.navigation')
            </nav>
        </div>
    </aside>

    {{-- CONTENT WRAPPER --}}
    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-3">
                @yield('content')
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="main-footer">
         {{-- NEU: Button für Push-Benachrichtigungen --}}
        <button id="enable-push" class="btn btn-sm btn-info float-left mr-3">Desktop-Benachrichtigungen aktivieren</button>

        <div class="float-right d-none d-sm-inline">Version 1.0</div>
        <strong>Copyright &copy; 2025 EMS Panel.</strong> All rights reserved.
    </footer>
</div>

{{-- JAVASCRIPT --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

{{-- AJAX CSRF Setup --}}
<script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

{{-- DATATABLES JS --}}
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap4.min.js"></script>

{{-- SWEETALERT2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- SELECT2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


{{-- ECHO & PUSHER DEPENDENCIES (CDN Version, wie von dir genutzt) --}}
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.0/echo.iife.min.js"></script>

{{-- ===================================================================== --}}
{{-- === HAUPT-SKRIPTBLOCK (inkl. Theme, Swal, Echo, Notifications) === --}}
{{-- ===================================================================== --}}
<script>
    // Alles in $(document).ready() kapseln
    $(document).ready(function() {

        console.log("DOM ready. Initialisiere Haupt-Skripte...");

        // --- THEME-LOGIK ---
        console.log("Initialisiere Theme-Logik...");
        (() => {
            'use strict'
            const getStoredTheme = () => localStorage.getItem('theme')
            const setStoredTheme = theme => localStorage.setItem('theme', theme)
            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme()
                if (storedTheme) return storedTheme
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            }
            const applyTheme = theme => {
                console.log("Anwenden Theme:", theme);
                const body = document.body;
                const navbar = document.getElementById('mainNavbar');
                const sidebar = document.getElementById('mainSidebar');
                const toggleIcon = document.getElementById('darkModeToggle')?.querySelector('i');

                if (!navbar || !sidebar || !toggleIcon) {
                    console.warn("Theme-Elemente nicht gefunden (Navbar, Sidebar oder ToggleIcon)");
                    return;
                }

                if (theme === 'dark') {
                    body.classList.add('dark-mode');
                    navbar.classList.add('navbar-dark');
                    navbar.classList.remove('navbar-white', 'navbar-light');
                    sidebar.classList.add('sidebar-dark-primary');
                    sidebar.classList.remove('sidebar-light-primary');
                    toggleIcon.classList.replace('fa-moon', 'fa-sun');
                } else {
                    body.classList.remove('dark-mode');
                    navbar.classList.add('navbar-white', 'navbar-light');
                    navbar.classList.remove('navbar-dark');
                    sidebar.classList.add('sidebar-light-primary');
                    sidebar.classList.remove('sidebar-dark-primary');
                    toggleIcon.classList.replace('fa-sun', 'fa-moon');
                }
            }
            applyTheme(getPreferredTheme());
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', (e) => {
                     e.preventDefault();
                     console.log("Dark Mode Toggle geklickt.");
                     const currentTheme = getStoredTheme() === 'dark' ? 'light' : 'dark';
                     setStoredTheme(currentTheme);
                     applyTheme(currentTheme);
                });
            } else {
                 console.warn("Dark Mode Toggle Button nicht gefunden.");
            }
        })();

        // --- SWEETALERT2 INTEGRATION ---
        console.log("Initialisiere SweetAlert...");
        function decodeHtml(str) {
             if (!str) return '';
             const doc = new DOMParser().parseFromString(str, "text/html");
             return doc.documentElement.textContent;
        }
        function showSweetAlert(type, message) {
            console.log("Zeige SweetAlert:", type, message);
            setTimeout(() => {
                if (typeof Swal === 'undefined') {
                    console.error("Swal (SweetAlert2) ist nicht definiert!");
                    return;
                }
                let title = type === 'success' ? 'Erfolg!' : 'Fehler!';
                let timer = type === 'success' ? 3000 : 5000;
                const decodedMessage = decodeHtml(message);
                Swal.fire({
                    toast: true, position: 'top-end', icon: type,
                    title: title, text: decodedMessage,
                    showConfirmButton: false, timer: timer
                });
            }, 50);
        }
        const successMessage = ('{{ session("success") }}' || '').trim();
        const errorMessage = ('{{ session("error") }}' || '').trim();
        const validationErrors = @json($errors->all() ?? []);
        if (successMessage.length > 0) { showSweetAlert('success', successMessage); }
        else if (errorMessage.length > 0) { showSweetAlert('error', errorMessage); }
        if (validationErrors.length > 0) {
            console.log("Zeige Validierungsfehler-Swal:", validationErrors);
            const errorHtml = validationErrors.map(err => `<li>${decodeHtml(err)}</li>`).join('');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error', title: 'Validierungsfehler!',
                    html: `Bitte korrigiere die folgenden Fehler:<ul>${errorHtml}</ul>`,
                    showConfirmButton: true, confirmButtonText: 'Verstanden'
                });
            } else {
                 console.error("Swal nicht definiert, kann Validierungsfehler nicht anzeigen.");
            }
        }

        // --- ECHO INITIALISIERUNG (Deine funktionierende Version mit CDN) ---
        if (typeof Pusher !== 'undefined' && typeof Echo !== 'undefined') {
            console.log("Initialisiere Echo mit Pusher/Reverb...");
            window.Pusher = Pusher;
            const isHttps = window.location.protocol === 'https:';
            window.Echo = new Echo({
                broadcaster: 'pusher', // Oder 'reverb', prüfe deine .env BROADCAST_CONNECTION
                key: '{{ env("REVERB_APP_KEY", env("PUSHER_APP_KEY")) }}',
                wsHost: window.location.hostname, // Annahme: Läuft auf demselben Host
                // wssHost: window.location.hostname, // Nur wenn nötig
                wsPort: {{ env("REVERB_PORT") ?? 6001 }}, // Standard Pusher/WS Port
                wssPort: {{ env("REVERB_PORT") ?? 443 }}, // Standard WSS Port
                forceTLS: isHttps || ('{{ env("REVERB_SCHEME", env("PUSHER_SCHEME")) }}' === 'https'),
                // path: '/app', // Nur für Reverb relevant? Prüfen!
                disableStats: true,
                // enabledTransports: ['ws', 'wss'], // Prüfen ob nötig
                authorizer: (channel, options) => {
                    return {
                        authorize: (socketId, callback) => {
                            $.post('/broadcasting/auth', {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                socket_id: socketId,
                                channel_name: channel.name
                            })
                            .done(response => { callback(false, response); })
                            .fail(error => {
                                console.error('Laravel Echo Authorisierung fehlgeschlagen:', error.responseJSON || error);
                                callback(true, error);
                            });
                        }
                    };
                },
            });

            window.Echo.connector.pusher.connection.bind('error', function(err) { console.error("WebSocket Verbindungsfehler:", err); });
            window.Echo.connector.pusher.connection.bind('connected', function() { console.info("WebSocket-Verbindung erfolgreich hergestellt."); });
            window.Echo.connector.pusher.connection.bind('connecting', function() { console.info("Verbinde mit WebSocket..."); });
            window.Echo.connector.pusher.connection.bind('disconnected', function() { console.warn("WebSocket getrennt."); });

        } else {
            console.error("Pusher oder Echo (CDN) konnte nicht geladen werden.");
        }


        // --- JAVASCRIPT FÜR BENACHRICHTIGUNGEN (AJAX Polling & Echo Listener) ---
        console.log("Initialisiere Benachrichtigungs-Logik...");
        function fetchNotifications() {
            console.log("fetchNotifications() aufgerufen.");
            const notificationCount = $('#notification-count');
            const notificationList = $('#notification-list');
            const fetchUrl = '{{ route("api.notifications.fetch") }}';

            let openGroups = [];
            $('#notification-list .collapse.show').each(function() {
                openGroups.push($(this).attr('id'));
            });
            console.log("Offene Gruppen vor Fetch:", openGroups);

            $.ajax({
                url: fetchUrl, method: 'GET', dataType: 'json',
                success: function(response) {
                    console.log("AJAX Success - Notifications:", response);
                    const htmlContent = response.items_html;
                    if (response.count > 0) { notificationCount.text(response.count).show(); }
                    else { notificationCount.hide(); }
                    if (htmlContent) {
                        notificationList.html(htmlContent);
                        openGroups.forEach(function(id) { $(`#${id}`).collapse('show'); });
                        console.log("Gruppen nach Update geöffnet:", openGroups);
                    } else {
                       notificationList.html('<a href="#" class="dropdown-item"><span class="text-muted">Keine neuen Meldungen.</span></a>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fehler beim Laden der Benachrichtigungen via AJAX:', status, error, xhr.responseText);
                    notificationList.html('<a href="#" class="dropdown-item"><i class="fas fa-exclamation-triangle text-danger mr-2"></i> Fehler beim Laden.</a>');
                }
            });
        }
        fetchNotifications(); // Initialer Abruf

        @auth
        if (typeof window.Echo !== 'undefined') {
             console.log("Versuche, Echo-Kanal zu abonnieren: users.{{ Auth::id() }}");
             window.Echo.private(`users.{{ Auth::id() }}`)
                .listen('.new.ems.notification', (e) => {
                     console.log("Echo Event empfangen!", e);
                     fetchNotifications();
                     $('#notification-dropdown .fa-bell').addClass('text-warning').delay(500).queue(function(next){ $(this).removeClass('text-warning'); next(); });
                })
                .error((error) => { console.error('Echo Kanal-Fehler:', error); });
        } else {
             console.warn("Echo nicht definiert, kann Kanal nicht abonnieren für Echtzeit-Updates.");
        }
        @endauth

        // --- FIX: Verhindert Schließen des Dropdowns ---
        $(document).on('click', '#notification-dropdown .dropdown-menu', function (e) {
            const isToggle = $(e.target).closest('a[data-toggle="collapse"]').length > 0;
            const isContent = $(e.target).closest('.collapse').length > 0;
            const isFormElement = $(e.target).closest('form, button[type="submit"]').length > 0;
            if (isToggle || isContent || isFormElement) { e.stopPropagation(); }
        });

    }); // Ende von $(document).ready() für Haupt-Skripte
</script>

{{-- ===================================================================== --}}
{{-- === NEUER, SEPARATER SKRIPTBLOCK FÜR PUSH-BENACHRICHTIGUNGEN === --}}
{{-- ===================================================================== --}}
<script>
    // Kapseln in DOMContentLoaded, um sicherzustellen, dass der Button existiert
    document.addEventListener('DOMContentLoaded', function() {
        console.log("[Push] Initialisiere Push-Benachrichtigungs-Logik...");

        // (1) VAPID Key von Laravel Blade an JavaScript übergeben
        const VAPID_PUBLIC_KEY = '{{ config('webpush.vapid.public_key') }}';
        if (!VAPID_PUBLIC_KEY) {
            console.error("[Push] VAPID_PUBLIC_KEY ist nicht konfiguriert!");
        } else {
            console.log("[Push] VAPID Public Key vorhanden.");
        }

        // (2) Hilfsfunktion
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
            return outputArray;
        }

        // (3) Abo-Funktion
        function subscribeUser() {
            console.log("[Push] subscribeUser() aufgerufen.");
            if (!VAPID_PUBLIC_KEY) {
                 alert("Fehler: Push-Benachrichtigungen sind serverseitig nicht konfiguriert.");
                 return;
            }
             if (!navigator.serviceWorker) {
                 console.error("[Push] Service Worker wird nicht unterstützt.");
                 alert("Ihr Browser unterstützt keine Service Worker, die für Desktop-Benachrichtigungen benötigt werden.");
                 return;
             }

            navigator.serviceWorker.ready.then(registration => {
                console.log("[Push] Service Worker ist bereit für Abo.");
                const subscribeOptions = {
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                };
                return registration.pushManager.subscribe(subscribeOptions);
            })
            .then(pushSubscription => {
                console.log('[Push] Abo vom Browser erhalten:', pushSubscription);
                sendSubscriptionToServer(pushSubscription);
            })
            .catch(error => {
                console.error('[Push] Abo fehlgeschlagen:', error);
                if (Notification.permission === 'denied') {
                     alert('Sie haben Benachrichtigungen blockiert. Bitte ändern Sie dies in Ihren Browser-Einstellungen, um sie zu aktivieren.');
                } else {
                     alert('Aktivierung fehlgeschlagen. Ist der VAPID Key korrekt? Fehler: ' + error.message);
                }
            });
        }

        // (4) Sende-Funktion
        function sendSubscriptionToServer(subscription) {
            console.log("[Push] sendSubscriptionToServer() aufgerufen mit:", subscription);
            // Nutze jQuery AJAX, da es schon verfügbar ist und CSRF-Setup hat
            $.ajax({
                url: '{{ route('push.subscribe') }}',
                method: 'POST',
                contentType: 'application/json', // Wichtig!
                data: JSON.stringify(subscription), // JSON senden
                success: function(response) {
                    console.log('[Push] Abo auf Server gespeichert.', response);
                    alert('Desktop-Benachrichtigungen sind jetzt aktiv!');
                    const pushButton = document.getElementById('enable-push');
                    if(pushButton) pushButton.style.display = 'none';
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("[Push] Fehler beim Senden des Abos an den Server:", textStatus, errorThrown, jqXHR.responseText);
                    alert("Fehler: Das Abo konnte nicht auf dem Server gespeichert werden. Details siehe Konsole.");
                }
            });
        }

        // (5) Hauptlogik und Klick-Event für Push
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            console.log("[Push] Browser unterstützt Service Worker und Push Manager.");

            // Service Worker registrieren
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log("[Push] Service Worker erfolgreich registriert.", reg))
                .catch(err => console.error('[Push] SW-Registrierung fehlgeschlagen:', err));

            const pushButton = document.getElementById('enable-push');
            if (pushButton) {
                console.log("[Push] Button gefunden.");
                pushButton.addEventListener('click', () => {
                    console.log("[Push] Button geklickt!");
                    Notification.requestPermission().then(permission => {
                        console.log("[Push] Berechtigungs-Ergebnis:", permission);
                        if (permission === 'granted') {
                            subscribeUser();
                        } else if (permission === 'denied') {
                             alert('Sie haben Benachrichtigungen blockiert. Bitte ändern Sie dies in Ihren Browser-Einstellungen.');
                        } else {
                             // 'default' - User hat das Fenster geschlossen, nichts tun.
                             console.log('[Push] Berechtigungsanfrage ignoriert oder geschlossen.');
                        }
                    });
                });

                // Button verstecken, wenn schon abonniert ODER wenn Berechtigung verweigert wurde
                 navigator.serviceWorker.ready.then(reg => {
                    console.log("[Push] Prüfe bestehendes Abo und Berechtigung...");
                     // Prüfe zuerst die Berechtigung
                     if (Notification.permission === 'denied') {
                         console.log("[Push] Berechtigung wurde verweigert.");
                         pushButton.style.display = 'none'; // Button verstecken, da nutzlos
                         return; // Keine Abo-Prüfung nötig
                     }
                     // Wenn nicht verweigert, prüfe auf Abo
                     reg.pushManager.getSubscription().then(sub => {
                         if (sub) {
                             console.log("[Push] Benutzer ist bereits abonniert.", sub);
                             pushButton.style.display = 'none';
                         } else {
                              console.log("[Push] Benutzer ist noch nicht abonniert.");
                         }
                     }).catch(err => console.error("[Push] Fehler beim Prüfen des Abos:", err));
                }).catch(err => console.error("[Push] Fehler beim Warten auf Service Worker ready:", err));

            } else {
                 console.warn("[Push] Button ('enable-push') nicht im DOM gefunden!");
            }
        } else {
            console.warn('[Push] Push Messaging oder Service Worker wird von diesem Browser nicht unterstützt.');
            const pushButton = document.getElementById('enable-push');
            if(pushButton) pushButton.style.display = 'none';
        }

    }); // Ende von DOMContentLoaded für Push-Skripte
</script>


@impersonating
    <div style="position: fixed; bottom: 0; width: 100%; z-index: 9999; background-color: #dc3545; color: white; text-align: center; padding: 10px; font-weight: bold;">
        Achtung: Du bist gerade als {{ auth()->user()->name }} eingeloggt.
        <a href="{{ route('impersonate.leave') }}" style="color: white; text-decoration: underline; margin-left: 20px;">Zurück zu meinem Account</a>
    </div>
@endImpersonating

@stack('scripts')
</body>
</html>