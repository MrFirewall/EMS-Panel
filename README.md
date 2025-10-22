# EMS Panel / Verwaltung

Ein umfassendes Verwaltungs-Panel (Admin-Panel) für Rettungsdienste (EMS), entwickelt mit dem Laravel-Framework. Es dient zur Verwaltung von Personal, Ausbildung, Prüfungen, Einsätzen und Patientenakten.

> ⚠️ **Hinweis:** Diese Datei wurde **anonymisiert**. Alle Platzhalter (z. B. `<repository-url>`, `<user>`, `<app_key>`) **müssen vor der Verwendung manuell ersetzt** werden. Bitte kopiere die Datei **nicht unverändert** in eine Produktionsumgebung.
---

> **Transparenz-Hinweis:**  
> Das EMS-Panel wurde komplett mit KI-Unterstützung (Google Gemini) erstellt.  
> Inhalte und Funktionen wurden automatisiert generiert. Es wird keine Haftung für Fehler oder unvollständige Funktionen übernommen. 

---

## Hauptfunktionen

Basierend auf dem Entwicklungsverlauf umfasst das Panel folgende Kernmodule:

### 1. Personal- & Rollenverwaltung

* Benutzerverwaltung (CRUD)
* Rollen & Berechtigungen (Policies)
* Super-Admin mit vollem Zugriff
* Impersonalisierung (Login als anderer Benutzer)
* Dienststatusverwaltung (aktiv/inaktiv)

### 2. Dienst- & Personalakte

* Automatische Stundenberechnung und Archivierung
* Wochenübersicht der Dienststunden
* Aktivitätsprotokoll aller Benutzeraktionen

### 3. Prüfungssystem

* Erstellung und Verwaltung von Prüfungen
* Unterstützung von Single-Choice, Multiple-Choice und Freitextfragen
* Prüfungsversuche mit eindeutigen Links
* Anti-Cheat-Protokollierung (Fokusverlust im Browser)
* Automatische und manuelle Auswertung
* Zurücksetzen von Versuchen durch Administratoren

### 4. Ausbildungsmodule

* Verwaltung von Ausbildungsmodulen (z. B. Kurse)
* Zuweisung an Benutzer mit Statusverwaltung

### 5. Formulare & Anträge

* Verwaltung von Anträgen für Module und Prüfungen
* Evaluierungen und Feedbackformulare
* Statusverfolgung (z. B. pending, processed)

### 6. Einsatzberichte

* Erfassung und Verwaltung von Einsatzberichten
* Nutzung von Vorlagen
* Zuordnung beteiligten Personals

### 7. Patientenakten

* Verwaltung von Patienteninformationen (Blutgruppe, Allergien etc.)
* Speicherung medizinischer und persönlicher Daten

### 8. Rezept-Management

* Erstellung und Verwaltung von Rezepten
* Verwendung von Vorlagen

### 9. Technische Features

* Modernes Admin-Layout (AdminLTE)
* DataTables & Select2-Integration
* Dark Mode-Unterstützung
* Umfassendes Logging-System

---

## Voraussetzungen

* PHP ≥ 8.1
* Composer
* Node.js & NPM
* Datenbank (z. B. MySQL, MariaDB, PostgreSQL)
* Webserver (z. B. Nginx, Apache)

---

## Installation & Inbetriebnahme

1. Repository klonen
   ⚠️ *Ersetze `<repository-url>` durch die echte Repository-Adresse.*

   ```bash
   git clone <repository-url>
   cd ems-panel
   ```
2. Abhängigkeiten installieren

   ```bash
   composer install
   npm install
   ```
3. Frontend kompilieren

   ```bash
   npm run dev   # Entwicklung
   npm run build # Produktion
   ```
4. Umgebungsdatei einrichten

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
5. Datenbank konfigurieren
   ⚠️ *Ersetze Benutzername und Passwort durch reale Werte.*

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ems_panel
   DB_USERNAME=<user>
   DB_PASSWORD=<password>
   ```
6. Migrationen ausführen

   ```bash
   php artisan migrate
   ```
7. (Optional) Seeders & Vorlagen importieren

   ```bash
   php artisan db:seed
   php artisan import:report-templates
   php artisan import:prescription-templates
   ```
8. (Optional) Speicher verlinken

   ```bash
   php artisan storage:link
   ```
9. Lokalen Server starten

   ```bash
   php artisan serve
   ```

Anwendung läuft unter `APP_URL` (z. B. [http://localhost:8000](http://localhost:8000)).

---

## Produktivbetrieb (Beispielkonfiguration)

> ⚠️ **Wichtig:** Alle Pfade (`/path/to/...`) und Benutzer (`<system-user>`) sind Platzhalter und müssen an dein System angepasst werden.

### Supervisor (Queue Worker)

```ini
[program:ems-worker]
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=<system-user>
autostart=true
autorestart=true
stdout_logfile=/path/to/storage/logs/worker.log
```

### Supervisor (Laravel Reverb)

```ini
[program:reverb]
command=php /path/to/artisan reverb:start
user=<system-user>
autostart=true
autorestart=true
stdout_logfile=/path/to/storage/logs/reverb-worker.log
```

### Nginx-Proxy

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 60s;
}
```

---

## Beispielkonfiguration (.env & Laravel)

> ⚠️ **Hinweis:** Diese Konfigurationswerte sind Platzhalter. Ersetze `<app_id>`, `<app_key>`, `<app_secret>` und `<domain>` durch reale Werte aus deiner Umgebung.

### .env

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<app_id>
REVERB_APP_KEY=<app_key>
REVERB_APP_SECRET=<app_secret>
REVERB_HOST="<domain>"
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST="127.0.0.1"
REVERB_SERVER_PORT=8080
REVERB_SERVER_SCHEME=http
```

### config/broadcasting.php

```php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_SERVER_HOST', '127.0.0.1'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ],
],
```

### config/reverb.php

```php
'servers' => [
    'reverb' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => env('REVERB_SERVER_PORT', 8080),
        'hostname' => env('REVERB_HOST'),
    ],
],

'apps' => [
    'provider' => 'config',
    'apps' => [
        [
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_SERVER_HOST', '127.0.0.1'),
                'port' => env('REVERB_SERVER_PORT', '8080'),
                'scheme' => 'http',
                'useTLS' => false,
            ],
            'allowed_origins' => ['*'],
        ],
    ],
],
```
