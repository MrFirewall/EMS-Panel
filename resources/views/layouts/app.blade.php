<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'EMS Panel')</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap4.min.css">
    <style>
        .preloader {
            background-color: #343a40; /* Dunkler Hintergrund für den Preloader, verhindert "Aufblitzen" */
        }
        
        .dark-mode .list-group-item {
            background-color: #343a40;
            border-color: #454d55;
            color: #f8f9fa;
        }
        .dark-mode a.list-group-item:hover, .dark-mode a.list-group-item:focus {
            background-color: #495057;
            color: #ffffff;
        }
        .dark-mode .text-muted {
            color: #adb5bd !important;
        }

        /* Stellt sicher, dass Select2-Felder im Dark Mode den dunklen Hintergrund und die passende Schriftfarbe erhalten */
        .dark-mode .select2-container--bootstrap4 .select2-selection {
            background-color: #343a40;
            border-color: #6c757d;
        }
        /* Stellt sicher, dass der Text im Auswahlfeld weiß ist */
        .dark-mode .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            color: #fff;
        }
        /* Färbt den Dropdown-Pfeil weiß */
        .dark-mode .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
            border-color: #fff transparent transparent transparent;
        }
        /* Stil für die ausgewählten Tags in der Mehrfachauswahl */
        .dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            background-color: #3f6791; /* Primärfarbe für bessere Sichtbarkeit */
            color: #fff;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff; /* Primärfarbe für bessere Sichtbarkeit */
            color: #fff !important;
        }
        /* Verbessertes Styling für das "X" zum Entfernen eines Tags */
        .dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff;
            text-shadow: 0 1px 0 #495057;
            font-size: 1.5rem;
            line-height: 1;
            opacity: .5;
            background-color: transparent;
            border: 0;
            float: left;
            padding-right: 3px;
            padding-left: 3px;
            margin-right: 1px;
            margin-left: 3px;
            font-weight: 700;
        }.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff !important;
            text-shadow: 0 1px 0 #495057;
            font-size: 1.5rem;
            line-height: 1;
            opacity: .5;
            background-color: transparent;
            border: 0;
            float: left;
            padding-right: 3px;
            padding-left: 3px;
            margin-right: 1px;
            margin-left: 3px;
            font-weight: 700;
        }
        .dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #fff;
            text-decoration: none;
        }
        /* Stil für das Dropdown-Menü selbst */
        .dark-mode .select2-dropdown {
            background-color: #343a40;
            border-color: #6c757d;
        }
        /* Stil für das Suchfeld im Dropdown */
        .dark-mode .select2-search--dropdown .select2-search__field {
            background-color: #454d55;
            color: #fff;
        }
        /* Stil für die hervorgehobene Option in der Liste */
        .dark-mode .select2-container--bootstrap4 .select2-results__option--highlighted {
            background-color: #007bff;
            color: #fff;
        }
        
        /* NEUE REGEL: Größe des Benachrichtigungs-Badges anpassen */
        .main-header .navbar-badge {
            font-size: 0.75rem; /* Standardmäßig ist es oft 0.5em oder kleiner. Erhöhen Sie es. */
            padding: 3px 6px;  /* Mehr Füllung für eine größere Klickfläche */
            top: 6px;          /* Passt die vertikale Position im AdminLTE-Header an */
            right: 3px;        /* Passt die horizontale Position an */
            font-weight: 700;  /* Macht die Zahl dicker */
        }


        /* Preloader: Option 3 (EKG-Linie) */
        .ekg-loader {
            width: 20vw;      /* Breite ist 20% der Bildschirmbreite */
            height: 10vw;     /* Höhe ist die Hälfte der Breite (2:1 Verhältnis) */
            max-width: 300px; /* Wird nie breiter als 300px */
            max-height: 150px;/* Wird nie höher als 150px */
            min-width: 120px; /* Wird nie schmaler als 120px */
            min-height: 60px; /* Wird nie kleiner als 60px */
        }
        .ekg-loader path {
            stroke: #007bff;
            stroke-width: 4;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: draw 2s linear infinite;
        }

        @keyframes draw {
            to {
                stroke-dashoffset: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

<div class="preloader flex-column justify-content-center align-items-center">
    <svg class="ekg-loader" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130 65">
        <path fill="none" d="M0,32.5 h20 l5,-20 l5,40 l5,-30 l5,10 h60"/>
    </svg>
</div>

    <nav class="main-header navbar navbar-expand navbar-white navbar-light" id="mainNavbar">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" id="darkModeToggle" href="#" role="button">
                    <i class="fas fa-moon"></i>
                </a>
            </li>

            {{-- BENACHRICHTIGUNGS-DROPDOWN --}}
            <li class="nav-item dropdown" id="notification-dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge" id="notification-count" style="display: none;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="notification-list">
                    {{-- Der Inhalt wird per JavaScript geladen und nutzt layouts._notifications --}}
                    <div class="dropdown-item">Lade Benachrichtigungen...</div>
                </div>
            </li>
            
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="{{ Auth::user()->avatar }}" class="user-image img-circle elevation-1" alt="User Image">
                    <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <li class="user-header bg-primary">
                        <img src="{{ Auth::user()->avatar }}" class="img-circle elevation-2" alt="User Image">
                        <p>
                            {{ Auth::user()->name }}
                            <small>{{ Auth::user()->rank ?? 'Mitarbeiter' }}</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="#" class="btn btn-default btn-flat float-right"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Abmelden
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
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

    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-3"> {{-- Padding Top für Abstand --}}
                @yield('content')
            </div>
        </div>
    </div>
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">Version 1.0</div>
        <strong>Copyright &copy; 2025 EMS Panel.</strong> All rights reserved.
    </footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

{{-- NEU: AJAX CSRF Setup --}}
<script>
    // Stellt das CSRF-Token für alle jQuery-AJAX-Anfragen ein
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
{{-- ENDE NEU --}}

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

{{-- ECHO & PUSHER DEPENDENCIES (FÜR ECHTZEIT) --}}
<script src="https://js.pusher.com/7.0/pusher.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.0/echo.iife.min.js"></script>

<script>
    // ------------------------------------------------------------------------
    // THEME-LOGIK (Wiederhergestellt)
    // ------------------------------------------------------------------------
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
            const toggleIcon = document.getElementById('darkModeToggle').querySelector('i');
            
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
        
        document.getElementById('darkModeToggle').addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = getStoredTheme() === 'dark' ? 'light' : 'dark';
            setStoredTheme(currentTheme);
            applyTheme(currentTheme);
        });
    })();


    // ------------------------------------------------------------------------
    // SWEETALERT2 INTEGRATION (Wiederhergestellt)
    // ------------------------------------------------------------------------
    function decodeHtml(str) {
        const doc = new DOMParser().parseFromString(str, "text/html");
        return doc.documentElement.textContent;
    }
    function showSweetAlert(type, message) {
        setTimeout(() => {
            if (typeof Swal === 'undefined') return;
            let title = type === 'success' ? 'Erfolg!' : 'Fehler!';
            let timer = type === 'success' ? 3000 : 5000;
            const decodedMessage = decodeHtml(message);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: title,
                text: decodedMessage,
                showConfirmButton: false,
                timer: timer
            });
        }, 50);
    }
    $(document).ready(function() {
        const successMessage = '{{ session("success") }}'.trim();
        const errorMessage = '{{ session("error") }}'.trim();
        const validationErrors = @json($errors->all() ?? []);
        if (successMessage.length > 0) {
            showSweetAlert('success', successMessage);
        } else if (errorMessage.length > 0) {
            showSweetAlert('error', errorMessage);
        } 
        if (validationErrors.length > 0) {
            const errorHtml = validationErrors.map(err => `<li>${err}</li>`).join('');
            Swal.fire({
                icon: 'error',
                title: 'Validierungsfehler!',
                html: `Bitte korrigiere die folgenden Fehler:<ul>${errorHtml}</ul>`,
                showConfirmButton: true,
                confirmButtonText: 'Verstanden'
            });
        }
    });


    // ------------------------------------------------------------------------
    // ECHO INITIALISIERUNG
    // ------------------------------------------------------------------------
    window.Pusher = Pusher;

    // Host wird von Laravel übernommen
    const isHttps = window.location.protocol === 'https:';

    console.log('[DEBUG] 1. Initialisierung Echo-Konfig.');
    console.log(`[DEBUG] Host: {{ request()->getHost() }}, Port: {{ env("REVERB_PORT") ?? 8080 }}, TLS: ${isHttps}`);

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: '{{ env("REVERB_APP_KEY") }}', 
        
        wsHost: '{{ request()->getHost() }}', 
        wssHost: '{{ request()->getHost() }}',

        wsPort: {{ env("REVERB_PORT") ?? 8080 }}, 
        wssPort: {{ env("REVERB_PORT") ?? 8080 }},
        
        forceTLS: isHttps || ('{{ env("REVERB_SCHEME") }}' === 'https'),

        disableStats: true,
        // Authorizer bleibt unverändert
        authorizer: (channel, options) => {
             console.log(`[DEBUG] 2. Autorisierungsanfrage für Channel: ${channel.name}`);
            return {
                authorize: (socketId, callback) => {
                    $.post('/broadcasting/auth', {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                    .done(response => {
                        console.log('[DEBUG] 3. Autorisierung erfolgreich:', response);
                        callback(false, response);
                    })
                    .fail(error => {
                        console.error('[DEBUG] 3. Autorisierung FEHLGESCHLAGEN!', error);
                        callback(true, error);
                    });
                }
            };
        },
    });
    
    // Globaler Listener für Statusänderungen (hilfreich für Verbindungsprobleme)
    window.Echo.connector.pusher.connection.bind('state_change', function(states) {
        console.warn(`[DEBUG] Reverb Statusänderung: ${states.current} (Vorher: ${states.previous})`);
        if (states.current === 'connected') {
            console.info('[DEBUG] WebSocket-Verbindung erfolgreich hergestellt und verbunden!');
        }
    });


    // JAVASCRIPT FÜR BENACHRICHTIGUNGEN (Echtzeit-fähig)
    
    // Holt Benachrichtigungen vom Server und stellt den Zustand der Collapse-Gruppen wieder her.
    function fetchNotifications() {
        const notificationCount = $('#notification-count');
        const notificationList = $('#notification-list');
        const fetchUrl = '{{ route("api.notifications.fetch") }}'; 
        
        // 1. Zustand speichern: IDs aller geöffneten Collapse-Gruppen sammeln
        let openGroups = [];
        $('#notification-list .collapse.show').each(function() {
            openGroups.push($(this).attr('id'));
        });
        
        console.log('[DEBUG] 4. Starte AJAX-Fetch für Benachrichtigungen. Offene Gruppen:', openGroups);

        $.ajax({
            url: fetchUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('[DEBUG] 5. AJAX-Fetch erfolgreich.', response);
                const htmlContent = response.items_html;
                
                // Zähler aktualisieren
                if (response.count > 0) {
                    notificationCount.text(response.count).show();
                } else {
                    notificationCount.hide();
                }

                // Dropdown-Liste mit dem NEUEN HTML aus dem Partial füllen
                if (htmlContent) {
                    notificationList.html(htmlContent);

                    // 6. Zustand wiederherstellen: Geöffnete Gruppen erneut öffnen
                    openGroups.forEach(function(id) {
                        $('#' + id).collapse('show');
                        console.log(`[DEBUG] 6. Gruppe ${id} wiederhergestellt (show).`);
                    });

                } else {
                   notificationList.html('<a href="#" class="dropdown-item"><span class="text-muted">Keine neuen Meldungen.</span></a>');
                }
            },
            error: function(xhr, status, error) {
                console.error('[DEBUG] 5. AJAX-Fetch FEHLGESCHLAGEN:', status, error);
                notificationList.html('<a href="#" class="dropdown-item"><i class="fas fa-exclamation-triangle text-danger mr-2"></i> Fehler beim Laden.</a>');
            }
        });
    }

    $(document).ready(function() {
        // Führe die Funktion sofort beim Laden der Seite aus, um den initialen Zustand zu setzen
        fetchNotifications();

        // --------------------------------------------------------------------
        // ECHTE ECHTZEIT-LOGIK (Laravel Echo)
        // --------------------------------------------------------------------
        @auth
        // Lauscht auf den privaten Kanal des eingeloggten Benutzers
        console.log('[DEBUG] 7. Listener für .App.Notifications.GeneralNotification aktiviert.');
        window.Echo.private(`users.{{ Auth::id() }}`) 
            // FINALER FIX: Lauscht auf den vollen Klassennamen, um den Namespace-Konflikt zu vermeiden
            .listen('.new.ems.notification', (e) => { // Beachtet den im Backend definierten broadcastAs-Namen
                console.log('--- ECHTZEIT EVENT EMPFANGEN ---');
                console.log('[DEBUG] 8. Benachrichtigung über .listen() erhalten!', e);
                // Lädt das Dropdown nur, wenn ein Event eintrifft
                fetchNotifications(); 
            });
        @endauth
        // --------------------------------------------------------------------

    });

    // FIX: Verhindert, dass das Haupt-Dropdown-Menü schließt, wenn auf Toggle- oder Content-Elemente der Untergruppen geklickt wird.
    $(document).on('click', '#notification-dropdown .dropdown-menu', function (e) {
        // Überprüft, ob das geklickte Element (oder ein Elternteil) ein Link für eine Collapse-Gruppe ist
        const isToggle = $(e.target).closest('a[data-toggle="collapse"]').length > 0;
        // Überprüft, ob das geklickte Element innerhalb einer Collapse-Gruppe liegt
        const isContent = $(e.target).closest('.collapse').length > 0;

        // Nur wenn es ein Toggle-Klick ist oder ein Klick auf den Inhalt einer geöffneten Gruppe,
        // stoppen wir die Weitergabe des Events, um das Haupt-Dropdown offen zu halten.
        if (isToggle || isContent) {
             e.stopPropagation();
        }
    });
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
