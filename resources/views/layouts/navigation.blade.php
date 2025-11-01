@php
    /*
    |--------------------------------------------------------------------------
    | USER-BEREICH HELPER
    |--------------------------------------------------------------------------
    */
    
    // NEU: Für den Link zum Benachrichtigungs-Archiv
    $isNotificationsActive = Request::routeIs('notifications.*');

    // Dropdown: Ausbildung (User)
    $isAusbildungAnmeldungActive = Request::routeIs('forms.evaluations.modulAnmeldung', 'forms.evaluations.pruefungsAnmeldung');
    $isAusbildungUserActive = $isAusbildungAnmeldungActive;

    // Dropdown: Formulare (User)
    $isEvaluationsActive = Request::routeIs('forms.evaluations.azubi', 'forms.evaluations.praktikant', 'forms.evaluations.mitarbeiter', 'forms.evaluations.leitstelle');
    $isFormsUserActive = $isEvaluationsActive || Request::routeIs('vacations.create');

    /*
    |--------------------------------------------------------------------------
    | ADMIN-BEREICH HELPER
    |--------------------------------------------------------------------------
    */

    // Dropdown: Personalverwaltung (Admin)
    $isAdminPersonalActive = Request::routeIs('admin.users.*') || 
                             Request::routeIs('admin.vacations.*') || 
                             Request::routeIs('admin.roles.*');

    // Dropdown: Ausbildungsleitung (Admin)
    $isExamManagementActive = Request::routeIs('admin.exams.*') || Request::routeIs('admin.exams.attempts.*');
    $isAdminAusbildungActive = Request::routeIs('forms.evaluations.index') || 
                               Request::routeIs('modules.*') || 
                               $isExamManagementActive;

    // Dropdown: System & Konfiguration (Admin)
    $isNotificationRulesActive = Request::routeIs('admin.notification-rules.*');
    // Optimiert: admin.permissions.* (statt .index) und admin.logs.index (da nur index existiert)
    $isAdminSystemActive = Request::routeIs('admin.announcements.*') ||
                           Request::routeIs('admin.permissions.*') ||
                           Request::routeIs('admin.logs.index') ||
                           $isNotificationRulesActive;

@endphp

<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    
    {{-- ================================================================= --}}
    {{-- ALLGEMEINER BEREICH (USER)
    {{-- ================================================================= --}}
    
    <li class="nav-item">
        <a href="{{ route('dashboard') }}" class="nav-link {{ Request::routeIs('dashboard') ? 'active' : '' }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
        </a>
    </li>
    
    @can('profile.view')
    <li class="nav-item">
        <a href="{{ route('profile.show') }}" class="nav-link {{ Request::routeIs('profile.show') ? 'active' : '' }}">
            <i class="nav-icon fas fa-user"></i>
            <p>Profil</p>
        </a>
    </li>
    @endcan

    {{-- NEU HINZUGEFÜGT (BASIEREND AUF WEB.PHP) --}}
    <li class="nav-item">
        <a href="{{ route('notifications.index') }}" class="nav-link {{ $isNotificationsActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-bell"></i>
            <p>Benachrichtigungen</p>
        </a>
    </li>
    
    {{-- EINSATZWESEN GRUPPE --}}
    @canany(['reports.view', 'citizens.view'])
    <li class="nav-header">EINSATZWESEN</li>
    @can('reports.view')
    <li class="nav-item">
        <a href="{{ route('reports.index') }}" class="nav-link {{ Request::routeIs('reports.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-hospital-alt"></i>
            <p>Einsatzberichte</p>
        </a>
    </li>
    @endcan
     @can('citizens.view')
     <li class="nav-item">
        <a href="{{ route('citizens.index') }}" class="nav-link {{ Request::routeIs('citizens.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-address-book"></i>
            <p>Patientenakten</p>
        </a>
    </li>
    @endcan
    @endcanany

    {{-- AUSBILDUNG GRUPPE (USER) --}}
    @can('training.view') {{-- Ggf. Berechtigung anpassen --}}
    <li class="nav-item has-treeview {{ $isAusbildungUserActive ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $isAusbildungUserActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-graduation-cap"></i>
            <p>
                Meine Ausbildung
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            @can('evaluations.create') 
            <li class="nav-item"><a href="{{ route('forms.evaluations.modulAnmeldung') }}" class="nav-link {{ Request::routeIs('forms.evaluations.modulAnmeldung') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Modul-Anmeldung</p></a></li>
            <li class="nav-item"><a href="{{ route('forms.evaluations.pruefungsAnmeldung') }}" class="nav-link {{ Request::routeIs('forms.evaluations.pruefungsAnmeldung') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Prüfungs-Anmeldung</p></a></li>
            @endcan
        </ul>
    </li>
    @endcan

    {{-- FORMULARE GRUPPE (USER) --}}
    @canany(['evaluations.create', 'vacations.create'])
    <li class="nav-item has-treeview {{ $isFormsUserActive ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $isFormsUserActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-file-alt"></i>
            <p>
                Formulare & Anträge
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            
            {{-- 1. NESTED DROPDOWN: BEWERTUNGEN --}}
            @can('evaluations.create')
            <li class="nav-item has-treeview {{ $isEvaluationsActive ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ $isEvaluationsActive ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>
                        Bewertungen
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item"><a href="{{ route('forms.evaluations.azubi') }}" class="nav-link {{ Request::routeIs('forms.evaluations.azubi') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Azubibewertung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.praktikant') }}" class="nav-link {{ Request::routeIs('forms.evaluations.praktikant') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Praktikantenbewertung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.mitarbeiter') }}" class="nav-link {{ Request::routeIs('forms.evaluations.mitarbeiter') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Mitarbeiterbewertung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.leitstelle') }}" class="nav-link {{ Request::routeIs('forms.evaluations.leitstelle') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Leitstellenbewertung</p></a></li>
                </ul>
            </li>
            @endcan

            {{-- 2. STANDALONE LINK: URLAUBSANTRAG --}}
            @can('vacations.create')
            <li class="nav-item">
                <a href="{{ route('vacations.create') }}" class="nav-link {{ Request::routeIs('vacations.create') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Urlaubsantrag</p>
                </a>
            </li>
            @endcan
        </ul>
    </li>
    @endcanany


    {{-- ================================================================= --}}
    {{-- ADMINISTRATIONS-BEREICH (ADMIN)
    {{-- ================================================================= --}}
    @can('admin.access') 
    <li class="nav-header">ADMINISTRATION</li>
    
        {{-- PERSONALVERWALTUNG (Personalabteilung) --}}
        @canany(['users.view', 'vacations.manage', 'roles.view'])
        <li class="nav-item has-treeview {{ $isAdminPersonalActive ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $isAdminPersonalActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-users-cog"></i>
                <p>Personalverwaltung<i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                @can('users.view')
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ Request::routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Mitarbeiter</p>
                    </a>
                </li>
                @endcan
                @can('vacations.manage')
                <li class="nav-item">
                    <a href="{{ route('admin.vacations.index') }}" class="nav-link {{ Request::routeIs('admin.vacations.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Urlaubsanträge</p>
                    </a>
                </li>
                @endcan
                @can('roles.view')
                <li class="nav-item">
                    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ Request::routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Rollen & Abteilungen</p>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endcanany

        {{-- AUSBILDUNGSLEITUNG (Ausbildungsabteilung) --}}
        @canany(['evaluations.view.all', 'exams.manage', 'training.view']) 
        <li class="nav-item has-treeview {{ $isAdminAusbildungActive ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $isAdminAusbildungActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-book-reader"></i>
                <p>Ausbildungsleitung<i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                @can('evaluations.view.all')
                <li class="nav-item">
                    {{-- Diese Route ist NICHT im Admin-Prefix, daher route('forms.evaluations.index') --}}
                    <a href="{{ route('forms.evaluations.index') }}" class="nav-link {{ Request::routeIs('forms.evaluations.index') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Eing. Formulare</p>
                    </a>
                </li>
                @endcan
                
                @can('training.view') 
                <li class="nav-item">
                     {{-- Diese Route ist NICHT im Admin-Prefix, daher route('modules.index') --}}
                    <a href="{{ route('modules.index') }}" class="nav-link {{ Request::routeIs('modules.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Ausbildungsmodule</p>
                    </a>
                </li>
                @endcan

                @can('exams.manage')
                <li class="nav-item has-treeview {{ $isExamManagementActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isExamManagementActive ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Prüfungsmanagement<i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.exams.index') }}" class="nav-link {{ Request::routeIs('admin.exams.index', 'admin.exams.show', 'admin.exams.create', 'admin.exams.edit') ? 'active' : '' }}">
                                <i class="far fa-dot-circle nav-icon"></i><p>Prüfungsdefinitionen</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.exams.attempts.index') }}" class="nav-link {{ Request::routeIs('admin.exams.attempts.*') ? 'active' : '' }}">
                                <i class="far fa-dot-circle nav-icon"></i><p>Prüfungsversuche</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan
            </ul>
        </li>
        @endcanany

        {{-- SYSTEM & KONFIGURATION (Rechtsabteilung / IT) --}}
        @canany(['announcements.view', 'permissions.view', 'logs.view', 'notification.rules.manage'])
        <li class="nav-item has-treeview {{ $isAdminSystemActive ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $isAdminSystemActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-cogs"></i>
                <p>System & Konfiguration<i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                @can('announcements.view')
                <li class="nav-item">
                    <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ Request::routeIs('admin.announcements.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Ankündigungen</p>
                    </a>
                </li>
                @endcan
                @can('permissions.view')
                <li class="nav-item">
                    <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ Request::routeIs('admin.permissions.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Berechtigungen</p>
                    </a>
                </li>
                @endcan
                @can('logs.view')
                <li class="nav-item">
                    <a href="{{ route('admin.logs.index') }}" class="nav-link {{ Request::routeIs('admin.logs.index') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Aktivitäten-Log</p>
                    </a>
                </li>
                @endcan
                @can('notification.rules.manage')
                <li class="nav-item">
                    <a href="{{ route('admin.notification-rules.index') }}" class="nav-link {{ $isNotificationRulesActive ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i><p>Benachrichtigungsregeln</p>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endcanany

    @endcan
</ul>

{{-- NEU: SESSION-TIMER (nur wenn nicht "angemeldet bleiben") --}}
@if(session('is_remembered') === false)
@push('scripts')
<script>
    // Diese Funktion wird ausgeführt, sobald das Dokument geladen ist.
    (function() {
        // 1. Setze die Dauer (aus Laravel Config, z.B. 120 Minuten)
        // (config('session.lifetime') ist in Minuten, wir brauchen Sekunden)
        // Wir ziehen 10 Sekunden ab, um einen Puffer zu haben, bevor der Server uns rauswirft.
        let sessionLifetimeInSeconds = ({{ config('session.lifetime', 120) * 60 }}) - 10;
        
        // 2. Finde das Timer-Element (diesmal in der Haupt-Navbar)
        const timerElement = document.getElementById('session-timer');
        if(!timerElement) return; // Stopp, wenn das Element nicht da ist

        // 3. Funktion zum Umleiten (zum Lockscreen)
        function redirectToLockscreen() {
            // Setze einen Flag, damit die Middleware weiß, dass dies ein Inaktivitäts-Timeout war
            // (Obwohl die Middleware dies bereits durch 'is_cfx_authenticated' erkennen sollte)
            window.location.href = '{{ route('lockscreen') }}';
        }

        // 4. Funktion zum Aktualisieren des Timers
        function updateTimer() {
            sessionLifetimeInSeconds--;

            if (sessionLifetimeInSeconds <= 0) {
                clearInterval(timerInterval);
                redirectToLockscreen();
                return;
            }

            let minutes = Math.floor(sessionLifetimeInSeconds / 60);
            let seconds = sessionLifetimeInSeconds % 60;

            // Führende Null hinzufügen
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            timerElement.textContent = minutes + ':' + seconds;
            
            // Ändere die Farbe auf rot, wenn weniger als 5 Minuten übrig sind
            if(sessionLifetimeInSeconds < 300) {
                timerElement.classList.remove('badge-danger');
                timerElement.classList.add('badge-warning');
            }
        }

        // 5. Timer starten
        let timerInterval = setInterval(updateTimer, 1000);

        // 6. Inaktivitäts-Reset
        // (Setzt den Timer zurück, wenn der Benutzer etwas tut)
        function resetTimer() {
            // Sende einen Ping an den Server, um die Session am Leben zu halten
            // (WICHTIG: Funktioniert nur, wenn die Session nicht abgelaufen ist)
            fetch('{{ route('session.keepalive') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).catch(err => console.warn('Keep-alive ping failed.'));

            // Setze den Timer-Countdown zurück
            clearInterval(timerInterval);
            sessionLifetimeInSeconds = ({{ config('session.lifetime', 120) * 60 }}) - 10;
            updateTimer(); // Timer sofort aktualisieren
            timerInterval = setInterval(updateTimer, 1000);
            
            // Farbe zurücksetzen
            timerElement.classList.remove('badge-warning');
            timerElement.classList.add('badge-danger');
        }

        // Events, die den Timer zurücksetzen (jQuery verwenden, da es bereits geladen ist)
        $(window).on('mousemove mousedown click keydown scroll', resetTimer);

    })();
</script>
@endpush
@endif
{{-- ENDE NEU --}}

