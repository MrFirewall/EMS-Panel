EMS Verwaltungssystem
Ein internes Verwaltungssystem für das Emergency Medical Service (EMS), entwickelt mit dem Laravel Framework. Diese Anwendung dient der Verwaltung von Mitarbeitern, Rollen, Akteneinträgen, Einsatzberichten und internen Formularen.

✨ Features
Dashboard: Zentrale Übersicht über wichtige Informationen und Statistiken.

Mitarbeiterverwaltung: Anlegen, Bearbeiten und Verwalten von Mitarbeiterprofilen.

Dynamisches Berechtigungssystem:

Rollen & Ränge: Feingranulare Rechtevergabe basierend auf einer klaren Rang-Hierarchie (von Praktikant bis EMS Director).

Abteilungs-Rollen: Spezielle Rollen für Abteilungen (Rechts-, Ausbildungs- & Personalabteilung) mit eigener Zuweisungslogik.

Super-Admin: Eine unsichtbare Admin-Rolle mit allumfassenden Rechten für die technische Verwaltung.

"Einloggen als"-Funktion (Impersonation): Administratoren können sich als andere Benutzer anmelden, um Probleme zu diagnostizieren.

Personalakte: Führen von Akteneinträgen (Beförderungen, Vermerke etc.) für jeden Mitarbeiter.

Formular-System: Einreichung und Verwaltung von Anträgen wie Urlaubsanträgen, Bewertungen etc.

Einsatzberichte: Erstellen und Verwalten von Einsatzberichten.

Aktivitäten-Log: Nachverfolgung aller wichtigen Aktionen im System.

💻 Technologie-Stack
Backend: PHP 8.2+ / Laravel 12+

Frontend: Blade, AdminLTE 3, JavaScript

Datenbank: MySQL

Wichtige Pakete:

spatie/laravel-permission: Für das Rollen- und Berechtigungssystem.

lab404/laravel-impersonate: Für die "Einloggen als"-Funktionalität.

🚀 Installation und Einrichtung
Folge diesen Schritten, um das Projekt lokal aufzusetzen.

1. Repository klonen

git clone [https://github.com/DEIN-BENUTZERNAME/DEIN-REPO-NAME.git](https://github.com/DEIN-BENUTZERNAME/DEIN-REPO-NAME.git)
cd DEIN-REPO-NAME

2. Abhängigkeiten installieren
Installiere alle PHP- und JavaScript-Abhängigkeiten.

# PHP-Pakete installieren
composer install

# JavaScript-Pakete installieren
npm install

3. Umgebungsvariablen-Datei erstellen
Kopiere die Beispiel-Datei und generiere einen neuen Anwendungsschlüssel.

cp .env.example .env
php artisan key:generate

4. .env-Datei konfigurieren
Öffne die .env-Datei und konfiguriere mindestens die folgenden Variablen, insbesondere deine Datenbank-Zugangsdaten:

APP_NAME="EMS Verwaltung"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deine_datenbank
DB_USERNAME=dein_benutzername
DB_PASSWORD=dein_passwort

5. Datenbank migrieren und Seeder ausführen
Erstelle die Datenbankstruktur und fülle sie mit den notwendigen Start-Daten (Rollen, Berechtigungen etc.).

php artisan migrate --seed

Dieser Befehl führt alle Migrationen und danach alle Seeder aus, inklusive des PermissionsSeeder.

6. Frontend-Assets kompilieren
Kompiliere die CSS- und JS-Dateien.

npm run build

7. Storage-Verknüpfung erstellen

php artisan storage:link

8. Server starten
Du kannst nun den lokalen Entwicklungsserver starten.

php artisan serve

Die Anwendung ist jetzt unter http://localhost:8000 erreichbar.

🔐 Admin-Zugang & Rollen
Standard-Admin: Nach dem Seeding hat der ems-director Zugriff auf alle administrativen Funktionen.

Super-Admin: Die Rolle Super-Admin besitzt ebenfalls alle Rechte, ist aber in der Benutzeroberfläche nicht sichtbar oder zuweisbar. Sie muss manuell über die Konsole (php artisan tinker) einem Entwickler-Account zugewiesen werden.

Lizenz
Dieses Projekt steht unter der MIT-Lizenz.