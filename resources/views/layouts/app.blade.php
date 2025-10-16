<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="sidebar-mini layout-fixed layout-navbar-fixed">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'EMS Panel')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE Style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <!-- HIER: DataTables CSS via CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap4.min.css">
    <!-- Optionales Dark Mode Styling -->
    <style>
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
            background-color: #007bff; /* Primärfarbe für bessere Sichtbarkeit */
            border-color: #0069d9;
            color: #fff;
        }
        /* Verbessertes Styling für das "X" zum Entfernen eines Tags */
        .dark-mode .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #adb5bd;
            text-shadow: 0 1px 0 #495057;
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
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light" id="mainNavbar">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" id="darkModeToggle" href="#" role="button">
                    <i class="fas fa-moon"></i>
                </a>
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
                        <a href="{{ route('profile.show') }}" class="btn btn-default btn-flat">Profil</a>
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
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
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

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid pt-3"> {{-- Padding Top für Abstand --}}
                @yield('content')
            </div>
        </div>
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">Version 1.0</div>
        <strong>Copyright &copy; 2025 EMS Panel.</strong> All rights reserved.
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<!-- HIER: DataTables & Plugins JS via CDN -->
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap4.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

<script>
    // DARK MODE LOGIK
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
                body.classList.remove('light-mode');
                
                navbar.classList.add('navbar-dark');
                navbar.classList.remove('navbar-white', 'navbar-light');
                
                sidebar.classList.add('sidebar-dark-primary');
                sidebar.classList.remove('sidebar-light-primary');
                
                toggleIcon.classList.replace('fa-moon', 'fa-sun');
            } else {
                body.classList.add('light-mode');
                body.classList.remove('dark-mode');
                
                navbar.classList.add('navbar-white', 'navbar-light');
                navbar.classList.remove('navbar-dark');
                
                sidebar.classList.add('sidebar-light-primary');
                sidebar.classList.remove('sidebar-dark-primary');
                
                toggleIcon.classList.replace('fa-sun', 'fa-moon');
            }
            document.documentElement.setAttribute('data-bs-theme', theme); 
        }
        
        // Initialer Zustand
        const initialTheme = getPreferredTheme();
        applyTheme(initialTheme);
        
        // Speichert den Zustand, wenn auf den Toggle geklickt wird
        document.getElementById('darkModeToggle').addEventListener('click', () => {
            const currentTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            setStoredTheme(currentTheme);
            applyTheme(currentTheme);
        });
    })();
</script>

<script>
    // ------------------------------------------------------------------------
    // SWEETALERT2 INTEGRATION
    // Die Logik ist jetzt stabil, da SweetAlert2 direkt geladen wird.
    // ------------------------------------------------------------------------
    
    /**
     * Helferfunktion zum Dekodieren von HTML-Entities (z.B. &#039; -> ')
     * @param {string} str
     * @returns {string}
     */
    function decodeHtml(str) {
        const doc = new DOMParser().parseFromString(str, "text/html");
        return doc.documentElement.textContent;
    }

    /**
     * Zeigt eine SweetAlert2-Meldung an.
     * @param {string} type - 'success' oder 'error'
     * @param {string} message - Der Hauptnachrichtentext
     */
    function showSweetAlert(type, message) {
        // Wir verwenden einen leichten Timeout, um sicherzustellen, dass Swal definitiv gerendert ist
        setTimeout(() => {
            if (typeof Swal === 'undefined') return;

            let title = type === 'success' ? 'Erfolg!' : 'Fehler!';
            let timer = type === 'success' ? 3000 : 5000;
            
            // KORREKTUR: Wir dekodieren den String, da Laravel ihn kodiert zurückgibt.
            const decodedMessage = decodeHtml(message);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: title,
                text: decodedMessage, // TEXT statt HTML verwenden, wenn es keine HTML-Liste ist
                showConfirmButton: false,
                timer: timer
            });
        }, 50); // Minimaler Timeout
    }

    // Führt die Logik aus, sobald jQuery bereit ist
    $(document).ready(function() {
        // --- Session-Werte aus PHP holen und codieren ---
        // Wir verwenden die PHP-Werte, die die Session sendet.
        const successMessage = '{{ session("success") }}'.trim();
        const errorMessage = '{{ session("error") }}'.trim();
        const validationErrors = @json($errors->all() ?? []);

        // --- Logik für Session Messages (Success/Error) ---
        if (successMessage.length > 0) {
            showSweetAlert('success', successMessage);
        } else if (errorMessage.length > 0) {
            showSweetAlert('error', errorMessage);
        } 
        
        // --- Logik für Validierungsfehler ---
        if (validationErrors.length > 0) {
            const errorHtml = validationErrors.map(err => `<li>${err}</li>`).join('');

            // Zeigt die Validierungsfehler als großes, nicht-toasted Alert an
            // Wir verwenden hier KEIN decodeHtml, da die map-Funktion die Liste selbst baut.
            Swal.fire({
                icon: 'error',
                title: 'Validierungsfehler!',
                html: `Bitte korrigiere die folgenden Fehler:<ul>${errorHtml}</ul>`,
                showConfirmButton: true,
                confirmButtonText: 'Verstanden'
            });
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
