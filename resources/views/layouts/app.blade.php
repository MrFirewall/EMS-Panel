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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css" integrity="sha512-8vq2g5nHE062j3xor4XxPeZiPjmRDh6wlufQlfC6pdQ/9urJkU07NM0tEREeymP++NczacJ/Q59ul+/K2eYvcg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            @if(session('is_remembered') === false)
            <li class="nav-item d-flex align-items-center px-2">
                <span class="text-muted small d-none d-sm-inline mr-1">Sitzung endet in:</span>
                <span class="badge badge-info" id="session-timer">--:--</span>
            </li>
            @endif

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
        {{-- Push Notification Buttons --}}
        <button id="enable-push" class="btn btn-sm btn-info float-left mr-3" style="display: none;">Desktop-Benachrichtigungen aktivieren</button>
        <button id="disable-push" class="btn btn-sm btn-danger float-left mr-3" style="display: none;">Desktop-Benachrichtigungen deaktivieren</button>

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
    $(document).ready(function() {

        // --- THEME-LOGIK ---
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
                const body = document.body;
                const navbar = document.getElementById('mainNavbar');
                const sidebar = document.getElementById('mainSidebar');
                const toggleIcon = document.getElementById('darkModeToggle')?.querySelector('i');

                if (!navbar || !sidebar || !toggleIcon) {
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
                     const currentTheme = getStoredTheme() === 'dark' ? 'light' : 'dark';
                     setStoredTheme(currentTheme);
                     applyTheme(currentTheme);
                });
            }
        })();

        // --- SWEETALERT2 INTEGRATION ---
        function decodeHtml(str) {
             if (!str) return '';
             const doc = new DOMParser().parseFromString(str, "text/html");
             return doc.documentElement.textContent;
        }
        function showSweetAlert(type, message) {
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

        // --- ECHO INITIALISIERUNG ---
        if (typeof Pusher !== 'undefined' && typeof Echo !== 'undefined') {
            window.Pusher = Pusher;
            const isHttps = window.location.protocol === 'https:';
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: '{{ env("REVERB_APP_KEY", env("PUSHER_APP_KEY")) }}',
                wsHost: window.location.hostname,
                wsPort: {{ env("REVERB_PORT") ?? 6001 }},
                wssPort: {{ env("REVERB_PORT") ?? 443 }},
                forceTLS: isHttps || ('{{ env("REVERB_SCHEME", env("PUSHER_SCHEME")) }}' === 'https'),
                disableStats: true,
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

        } else {
            console.error("Pusher oder Echo (CDN) konnte nicht geladen werden.");
        }

        // --- BENACHRICHTIGUNGEN ---
        function fetchNotifications() {
            const notificationCount = $('#notification-count');
            const notificationList = $('#notification-list');
            const fetchUrl = '{{ route("api.notifications.fetch") }}';

            let openGroups = [];
            $('#notification-list .collapse.show').each(function() {
                openGroups.push($(this).attr('id'));
            });

            $.ajax({
                url: fetchUrl, method: 'GET', dataType: 'json',
                success: function(response) {
                    const htmlContent = response.items_html;
                    if (response.count > 0) { notificationCount.text(response.count).show(); }
                    else { notificationCount.hide(); }
                    if (htmlContent) {
                        notificationList.html(htmlContent);
                        openGroups.forEach(function(id) { $(`#${id}`).collapse('show'); });
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
        fetchNotifications();

        @auth
        if (typeof window.Echo !== 'undefined') {
             window.Echo.private(`users.{{ Auth::id() }}`)
                .listen('.new.ems.notification', (e) => {
                     fetchNotifications();
                     $('#notification-dropdown .fa-bell').addClass('text-warning').delay(500).queue(function(next){ $(this).removeClass('text-warning'); next(); });
                })
                .error((error) => { console.error('Echo Kanal-Fehler:', error); });
        }
        @endauth

        $(document).on('click', '#notification-dropdown .dropdown-menu', function (e) {
            const isToggle = $(e.target).closest('a[data-toggle="collapse"]').length > 0;
            const isContent = $(e.target).closest('.collapse').length > 0;
            const isFormElement = $(e.target).closest('form, button[type="submit"]').length > 0;
            if (isToggle || isContent || isFormElement) { e.stopPropagation(); }
        });

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const VAPID_PUBLIC_KEY = '{{ config('webpush.vapid.public_key') }}';
        if (!VAPID_PUBLIC_KEY) {
            console.error("[Push] VAPID_PUBLIC_KEY ist nicht konfiguriert!");
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
            return outputArray;
        }

        function subscribeUser() {
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
                const subscribeOptions = {
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                };
                return registration.pushManager.subscribe(subscribeOptions);
            })
            .then(pushSubscription => {
                sendSubscriptionToServer(pushSubscription);
            })
            .catch(error => {
                console.error('[Push] Abo fehlgeschlagen:', error);
                if (Notification.permission === 'denied') {
                     alert('Sie haben Benachrichtigungen blockiert. Bitte ändern Sie dies in Ihren Browser-Einstellungen, um sie zu aktivieren.');
                } else {
                     alert('Aktivierung fehlgeschlagen. Ist der VAPID Key korrekt? Fehler: ' + error.message);
                }
                 updatePushButtons(false);
            });
        }

        function sendSubscriptionToServer(subscription) {
            $.ajax({
                url: '{{ route('push.subscribe') }}',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(subscription),
                success: function() {
                    alert('Desktop-Benachrichtigungen sind jetzt aktiv!');
                    updatePushButtons(true);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("[Push] Fehler beim Senden des Abos an den Server:", textStatus, errorThrown, jqXHR.responseText);
                    alert("Fehler: Das Abo konnte nicht auf dem Server gespeichert werden.");
                     updatePushButtons(false);
                }
            });
        }

        function unsubscribeUser() {
             if (!navigator.serviceWorker) { return; }

            navigator.serviceWorker.ready.then(registration => {
                registration.pushManager.getSubscription().then(subscription => {
                    if (subscription) {
                        subscription.unsubscribe().then(successful => {
                            if (successful) {
                                sendUnsubscriptionToServer(subscription);
                            } else {
                                console.error("[Push] Deabonnieren beim Browser fehlgeschlagen.");
                                alert("Fehler beim Deaktivieren der Benachrichtigungen im Browser.");
                            }
                        }).catch(error => {
                             console.error("[Push] Fehler bei subscription.unsubscribe():", error);
                             alert("Fehler beim Deaktivieren: " + error.message);
                        });
                    } else {
                         updatePushButtons(false);
                    }
                }).catch(error => {
                     console.error("[Push] Fehler bei getSubscription():", error);
                     alert("Fehler beim Abrufen des Abonnements.");
                });
            });
        }

        function sendUnsubscriptionToServer(subscription) {
            const endpoint = subscription.endpoint;
            $.ajax({
                url: '{{ route('push.unsubscribe') }}',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ endpoint: endpoint }),
                success: function() {
                    alert('Desktop-Benachrichtigungen wurden deaktiviert.');
                    updatePushButtons(false);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("[Push] Fehler beim Entfernen des Abos vom Server:", textStatus, errorThrown, jqXHR.responseText);
                    alert("Fehler: Das Abo konnte nicht auf dem Server entfernt werden.");
                }
            });
        }

        function updatePushButtons(isSubscribed) {
            const enableBtn = document.getElementById('enable-push');
            const disableBtn = document.getElementById('disable-push');
            if (!enableBtn || !disableBtn) {
                 return;
            }

            enableBtn.style.display = 'none';
            disableBtn.style.display = 'none';

            if (Notification.permission !== 'denied') {
                if (isSubscribed) {
                    disableBtn.style.display = 'inline-block';
                } else {
                    enableBtn.style.display = 'inline-block';
                }
            }
        }

        if ('serviceWorker' in navigator && 'PushManager' in window) {
            navigator.serviceWorker.register('/sw.js');

            const pushButtonEnable = document.getElementById('enable-push');
            const pushButtonDisable = document.getElementById('disable-push');

            if (pushButtonEnable && pushButtonDisable) {
                pushButtonEnable.addEventListener('click', () => {
                    Notification.requestPermission().then(permission => {
                        if (permission === 'granted') {
                            subscribeUser();
                        } else if (permission === 'denied') {
                             alert('Sie haben Benachrichtigungen blockiert. Bitte ändern Sie dies in Ihren Browser-Einstellungen.');
                             updatePushButtons(false);
                        }
                    });
                });

                pushButtonDisable.addEventListener('click', () => {
                    unsubscribeUser();
                });

                 navigator.serviceWorker.ready.then(reg => {
                     if (Notification.permission === 'denied') {
                         updatePushButtons(false);
                         return;
                     }
                     reg.pushManager.getSubscription().then(sub => {
                         updatePushButtons(!!sub);
                     }).catch(() => {
                         updatePushButtons(false);
                     });
                }).catch(() => {
                     updatePushButtons(false);
                });
            }
        } else {
            const pushButtonEnable = document.getElementById('enable-push');
            const pushButtonDisable = document.getElementById('disable-push');
            if(pushButtonEnable) pushButtonEnable.style.display = 'none';
            if(pushButtonDisable) pushButtonDisable.style.display = 'none';
        }

    });
</script>
<script>
    // Dieser Log wird IMMER angezeigt
    console.log('[DEBUG] ------------------------------------');
    console.log('[DEBUG] Seite geladen.');
    
    // 1. Den Wert aus der Server-Session ausgeben
    let isRemembered = '{{ var_export(session('is_remembered'), true) }}';
    console.log('[DEBUG] session(is_remembered) ist: ' + isRemembered);

    @if(session('is_remembered') === false)
        // 2. Dieser Log erscheint NUR, wenn die Bedingung WAHR ist
        console.log('[DEBUG] @@if-Bedingung (is_remembered === false) ist WAHR.');
        console.log('[DEBUG] => Der Timer-Block WIRD geladen.');
    @else
        // 3. Dieser Log erscheint NUR, wenn die Bedingung FALSCH ist
        console.log('[DEBUG] @@if-Bedingung (is_remembered === false) ist FALSCH.');
        console.log('[DEBUG] => Der Timer-Block WIRD NICHT geladen.');
    @endif
    
    console.log('[DEBUG] ------------------------------------');
</script>
{{-- FINALER SESSION-TIMER (SERVER-GESTEUERT MIT PING-RESET & DEBUGGING) --}}
@if(session('is_remembered') === false)
<script>
 (function() {
  // === DEBUGGING: Globale Log-Funktion ===
  const DEBUG = true;
  function log(message, ...args) {
      if (DEBUG) {
          // %c für CSS-Styling in der Konsole
          console.log(`%c[TIMER_DEBUG] %c${message}`, 'color: #1e90ff; font-weight: bold;', 'color: black;', ...args);
      }
  }
  log('Skript wird initialisiert.');

  const puffer = 10; // 10 Sekunden Puffer
  
  const timerElement = document.getElementById('session-timer');
  const timerTextElement = document.querySelector('.d-sm-inline.mr-1');
  if(!timerElement) {
      log('%cFEHLER: Timer-Element #session-timer nicht gefunden. Skript gestoppt.', 'color: red;');
      return; 
  }

  let timerInterval;
  let expiryTimestamp; // Hält den ZIEL-Timestamp (in Sekunden)
  let lastDisplayedMinutes; 
  let isResetting = false; // Sperre, um Ping-Spam zu verhindern

  function redirectToLockscreen() {
   log('%c[ACTION] redirectToLockscreen() wird aufgerufen! Umleitung zu /lock.', 'color: red; font-weight: bold;');
   clearInterval(timerInterval); // Stoppe alle weiteren Timer
   window.location.href = '{{ route('lock') }}';
  }

  function displayTime(remainingSeconds) {
    let totalMinutes = Math.ceil(remainingSeconds / 60);
    
    // Nur aktualisieren, wenn sich die Minute ändert
    if (totalMinutes === lastDisplayedMinutes && remainingSeconds > 0) {
        return; 
    }
    
    log(`[DISPLAY] displayTime() wird aufgerufen. Verbleibend: ${remainingSeconds.toFixed(0)}s. Zeige ${totalMinutes}min an.`);
    lastDisplayedMinutes = totalMinutes;

    if (totalMinutes <= 0) { 
        totalMinutes = 1; // Falls wir im Puffer-Bereich sind, zeige 1 Min.
    }
    
    if (totalMinutes < 60) {
      timerElement.textContent = totalMinutes + ' min';
    } else {
      let hours = Math.floor(totalMinutes / 60);
      let minutes = totalMinutes % 60;
      minutes = minutes < 10 ? '0' + minutes : minutes;
      timerElement.textContent = hours + 'h ' + minutes + ' min';
    }
    
    // Farblogik
    if(totalMinutes < 5) { 
      log('[DISPLAY] Status: Warnung (< 5 min)');
      timerElement.classList.remove('badge-primary');
      timerElement.classList.add('badge-warning');
      if(timerTextElement) timerTextElement.textContent = 'Sitzung endet bald:';
    } else {
      log('[DISPLAY] Status: Normal (> 5 min)');
      timerElement.classList.remove('badge-warning');
      timerElement.classList.add('badge-primary');
      if(timerTextElement) timerTextElement.textContent = 'Sitzung endet in:';
    }
  }

  function checkTimer() {
    const nowTimestamp = Math.floor(Date.now() / 1000);
    
    // Log alle 30 Sekunden, um die Konsole nicht zu fluten
    if (nowTimestamp % 30 === 0) {
        log(`[CHECK] Timer-Prüfung... Verbleibend: ${(expiryTimestamp - puffer) - nowTimestamp}s`);
    }
    
    // PRÜFUNG: Ist die Zeit abgelaufen?
    if (nowTimestamp >= (expiryTimestamp - puffer)) {
      log(`[CHECK] Zeit abgelaufen! now: ${nowTimestamp}, expiry: ${expiryTimestamp} (mit Puffer)`);
      clearInterval(timerInterval);
      redirectToLockscreen();
      return;
    }
    
    const remainingSeconds = (expiryTimestamp - puffer) - nowTimestamp;
    displayTime(remainingSeconds);
  }

  // STARTPUNKT: Holt die "Wahrheit" vom Server
  function fetchSessionExpiry() {
    log('%c[FETCH] fetchSessionExpiry() startet...', 'color: blue;');
    clearInterval(timerInterval);

    $.ajax({
        url: '{{ route("api.session.expiry") }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            expiryTimestamp = data.expiry_timestamp;
            const localTime = new Date(expiryTimestamp * 1000).toLocaleString();
            log(`%c[FETCH] Erfolg! Ziel-Timestamp: ${expiryTimestamp} (Lokal: ${localTime})`, 'color: green;');
            
            checkTimer(); // Einmal sofort anzeigen
            // Starte den sekundengenauen Prüfer
            timerInterval = setInterval(checkTimer, 1000); 
        },
        error: function(xhr) {
            log(`%c[FETCH] FEHLER!`, 'color: red;', xhr.responseText);
            console.error("Konnte Session-Ablaufzeit nicht vom Server laden.");
            timerElement.textContent = 'FEHLER';
            timerElement.classList.add('badge-danger');
        }
    });
  }

  // "ORDENTLICHER" RESET-TIMER
  function resetTimer() {
    if (isResetting) {
        log('[RESET] Reset-Anfrage ignoriert (Spam-Sperre aktiv).');
        return; 
    }
    isResetting = true;
    log('%c[RESET] resetTimer() startet... Pinge Server.', 'color: orange; font-weight: bold;');
    clearInterval(timerInterval);

    $.ajax({
        url: '{{ route("api.session.ping") }}',
        type: 'GET',
        dataType: 'json'
    }).done(function() {
        log('[RESET] Ping erfolgreich. Starte fetchSessionExpiry() neu.');
        // fetchSessionExpiry startet den Timer-Loop (setInterval) neu.
        fetchSessionExpiry();
    }).fail(function(xhr) {
        log(`%c[RESET] PING FEHLGESCHLAGEN!`, 'color: red;', xhr.responseText);
    }).always(function() {
        // Sperre nach 5 Sekunden wieder freigeben
        setTimeout(() => { 
            log('[RESET] Spam-Sperre aufgehoben.');
            isResetting = false; 
        }, 5000);
    });
  }
  
  // ===================================================================
  // === DER "WAKE UP" FIX GEGEN BACKGROUND THROTTLING ===
  // ===================================================================
  // Dieser Event feuert, wenn der Tab (oder das Browserfenster)
  // wieder in den Fokus rückt.
  window.addEventListener('focus', function() {
      log('%c[FOCUS] Tab ist wieder im Fokus. Prüfe Zeit...', 'color: purple; font-weight: bold;');
      
      // Starte sofort eine manuelle Prüfung. 
      // checkTimer() ist sicher, da es die Zeit vergleicht und ggf. redirectToLockscreen() aufruft.
      if (expiryTimestamp) { // Nur ausführen, wenn wir schon ein Ziel haben
         checkTimer();
      } else {
         // Falls der allererste fetch nie passiert ist (z.B. kein Netz beim Laden)
         log('[FOCUS] Kein Ziel-Timestamp gefunden. Starte fetchSessionExpiry()');
         fetchSessionExpiry();
      }
  });
  // ===================================================================

  // Events, die den Timer zurücksetzen (und neu fetchen)
  $(window).on('mousedown keydown', resetTimer);
  
  // Timer initial starten
  fetchSessionExpiry();

 })();
</script>
@endif

@impersonating
    <div style="position: fixed; bottom: 0; width: 100%; z-index: 9999; background-color: #dc3545; color: white; text-align: center; padding: 10px; font-weight: bold;">
        Achtung: Du bist gerade als {{ auth()->user()->name }} eingeloggt.
        <a href="{{ route('impersonate.leave') }}" style="color: white; text-decoration: underline; margin-left: 20px;">Zurück zu meinem Account</a>
    </div>
@endImpersonating

@stack('scripts')
</body>
</html>