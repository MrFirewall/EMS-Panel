@php
    // Überprüft, ob eine Route im Formularbereich aktiv ist (für das Ausklappen des Treeviews)
    $isEvaluationsActive = Request::routeIs('forms.evaluations.azubi', 'forms.evaluations.praktikant', 'forms.evaluations.mitarbeiter', 'forms.evaluations.leitstelle', 'forms.evaluations.index');
    $isAusbildungAnmeldungActive = Request::routeIs('forms.evaluations.modulAnmeldung', 'forms.evaluations.pruefungsAnmeldung');
    
    // Neue Logik zur Prüfung, ob eine Prüfungs-Route aktiv ist (WICHTIG: Prüft auf beide Sub-Gruppen)
    $isExamManagementActive = Request::routeIs('admin.exams.index', 'admin.exams.show', 'admin.exams.create', 'admin.exams.edit') || Request::routeIs('admin.exams.attempts.*');
    
    // NEU: Prüfung für Benachrichtigungsregeln
    $isNotificationRulesActive = Request::routeIs('admin.notification-rules.*');

    // Haupt-Dropdown ist aktiv, wenn eine seiner Unterkategorien oder ein direkter Link aktiv ist
    $isFormsActive = $isEvaluationsActive || $isAusbildungAnmeldungActive || Request::routeIs('vacations.create');

    // NEU/Optional: Haupt-Admin-Dropdown ist aktiv, wenn eine seiner Unterseiten aktiv ist (passen Sie dies ggf. an)
    $isAdminActive = Request::routeIs('admin.announcements.*') ||
                     Request::routeIs('admin.users.*') ||
                     Request::routeIs('modules.*') || // Annahme: gehört logisch zu Admin
                     $isExamManagementActive ||
                     Request::routeIs('admin.roles.*') ||
                     Request::routeIs('admin.permissions.index') ||
                     Request::routeIs('admin.vacations.*') ||
                     Request::routeIs('admin.logs.index') ||
                     $isNotificationRulesActive; // Hinzugefügt
@endphp

<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    
    {{-- ALLGEMEIN --}}
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
    
    {{-- FORMULARE GRUPPE --}}
    @canany(['evaluations.create', 'vacations.create'])
    <li class="nav-item has-treeview {{ $isFormsActive ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $isFormsActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-file-alt"></i>
            <p>
                Formulare
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            
            {{-- 1. NESTED DROPDOWN: BEWERTUNGEN --}}
            @canany(['evaluations.view.all', 'evaluations.create'])
            <li class="nav-item has-treeview {{ $isEvaluationsActive ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ $isEvaluationsActive ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>
                        Bewertungen
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    @can('evaluations.view.all')
                    <li class="nav-item"><a href="{{ route('forms.evaluations.index') }}" class="nav-link {{ Request::routeIs('forms.evaluations.index') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Übersicht</p></a></li>
                    @endcan
                    @can('evaluations.create')
                    <li class="nav-item"><a href="{{ route('forms.evaluations.azubi') }}" class="nav-link {{ Request::routeIs('forms.evaluations.azubi') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Azubibewertung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.praktikant') }}" class="nav-link {{ Request::routeIs('forms.evaluations.praktikant') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Praktikantenbewertung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.mitarbeiter') }}" class="nav-link {{ Request::routeIs('forms.evaluations.mitarbeiter') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Mitarbeiterbewertung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.leitstelle') }}" class="nav-link {{ Request::routeIs('forms.evaluations.leitstelle') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Leitstellenbewertung</p></a></li>
                    @endcan
                </ul>
            </li>
            @endcanany

            {{-- 2. NESTED DROPDOWN: AUSBILDUNG --}}
            @can('evaluations.create') 
            <li class="nav-item has-treeview {{ $isAusbildungAnmeldungActive ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ $isAusbildungAnmeldungActive ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>
                        Ausbildung
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item"><a href="{{ route('forms.evaluations.modulAnmeldung') }}" class="nav-link {{ Request::routeIs('forms.evaluations.modulAnmeldung') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Modul-Anmeldung</p></a></li>
                    <li class="nav-item"><a href="{{ route('forms.evaluations.pruefungsAnmeldung') }}" class="nav-link {{ Request::routeIs('forms.evaluations.pruefungsAnmeldung') ? 'active' : '' }}"><i class="far fa-dot-circle nav-icon"></i><p>Prüfungs-Anmeldung</p></a></li>
                </ul>
            </li>
            @endcan

            {{-- STANDALONE LINKS (LEVEL 2) --}}
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

    {{-- ADMIN GRUPPE --}}
    {{-- Passe ggf. die Berechtigung 'admin.access' an --}}
    @can('admin.access') 
    <li class="nav-header">ADMINISTRATION</li>
        {{-- Haupt-Admin-Menüpunkt (optional, zum Ausklappen) --}}
        {{-- <li class="nav-item has-treeview {{ $isAdminActive ? 'menu-open' : '' }}"> --}}
        {{-- <a href="#" class="nav-link {{ $isAdminActive ? 'active' : '' }}"> --}}
        {{--    <i class="nav-icon fas fa-cogs"></i><p>Administration <i class="right fas fa-angle-left"></i></p> --}}
        {{-- </a> --}}
        {{-- <ul class="nav nav-treeview"> --}}

        @can('announcements.view')
        <li class="nav-item">
            <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ Request::routeIs('admin.announcements.*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-bullhorn"></i>
                <p>Ankündigungen</p>
            </a>
        </li>
        @endcan
        @can('users.view')
        <li class="nav-item">
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ Request::routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-users"></i>
                <p>Mitarbeiter</p>
            </a>
        </li>
        @endcan
        
        @can('training.view')
        <li class="nav-item">
            <a href="{{ route('modules.index') }}" class="nav-link {{ Request::routeIs('modules.*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-graduation-cap"></i>
                <p>Ausbildungsmodule</p>
            </a>
        </li>
        @endcan

        {{-- PRÜFUNGSVERWALTUNG DROPDOWN --}}
        @can('exams.manage')
        <li class="nav-item has-treeview {{ $isExamManagementActive ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $isExamManagementActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-tasks"></i>
                <p>
                    Prüfungsmanagement
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                {{-- 1. Link: CRUD für Prüfungsdefinitionen --}}
                <li class="nav-item">
                    <a href="{{ route('admin.exams.index') }}" class="nav-link {{ Request::routeIs('admin.exams.index', 'admin.exams.show', 'admin.exams.create', 'admin.exams.edit') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Prüfungsdefinitionen</p>
                    </a>
                </li>
                {{-- 2. Link: Verwaltung der Versuche (NEU) --}}
                <li class="nav-item">
                    <a href="{{ route('admin.exams.attempts.index') }}" class="nav-link {{ Request::routeIs('admin.exams.attempts.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Prüfungsversuche</p>
                    </a>
                </li>
            </ul>
        </li>
        @endcan
        
        @can('roles.view')
        <li class="nav-item">
            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ Request::routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-shield"></i>
                <p>Rollenverwaltung</p>
            </a>
        </li>
        @endcan
        @can('permissions.view')
        <li class="nav-item">
            <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ Request::routeIs('admin.permissions.index') ? 'active' : '' }}">
                <i class="nav-icon fas fa-key"></i>
                <p>Rechte Verwaltung</p>
            </a>
        </li>
        @endcan
        @can('vacations.manage')
        <li class="nav-item">
            <a href="{{ route('admin.vacations.index') }}" class="nav-link {{ Request::routeIs('admin.vacations.*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-calendar-alt"></i>
                <p>Urlaubsanträge Verwalten</p>
            </a>
        </li>
        @endcan
        @can('logs.view')
        <li class="nav-item">
            <a href="{{ route('admin.logs.index') }}" class="nav-link {{ Request::routeIs('admin.logs.index') ? 'active' : '' }}">
                <i class="nav-icon fas fa-clipboard-list"></i>
                <p>Aktivitäten-Log</p>
            </a>
        </li>
        @endcan

        {{-- NEUER MENÜPUNKT: BENACHRICHTIGUNGSREGELN --}}
        @can('manage notification rules')
        <li class="nav-item">
            <a href="{{ route('admin.notification-rules.index') }}" class="nav-link {{ $isNotificationRulesActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-cogs"></i> {{-- Oder fas fa-bell-slash / fas fa-sliders-h --}}
                <p>Benachrichtigungsregeln</p>
            </a>
        </li>
        @endcan

        {{-- Ende der optionalen Haupt-Admin-Gruppe --}}
        </ul> 
        </li>
    @endcan
</ul>