Emergency Medical Service Verwaltungssystem: Projektdokumentation
Das vorliegende Dokument beschreibt das Emergency Medical Service (EMS) Verwaltungssystem, eine mittels des Laravel Frameworks entwickelte Softwareanwendung. Die primäre Funktion dieser Applikation besteht in der systematischen Verwaltung von Personal, zugewiesenen Rollen, Personalakten sowie internen Formularen.

Funktionsumfang
Zentrales Dashboard: Bereitstellung einer konsolidierten Übersicht über systemrelevante Informationen und Statistiken zur Effizienzsteigerung.

Personalverwaltung: Module zur Erfassung, Modifikation und Verwaltung von Mitarbeiterprofilen.

Hierarchisches Berechtigungssystem:

Implementierung einer granularen Zugriffskontrolle, die auf einer definierten Ranghierarchie basiert, welche von Praktikanten bis zur Direktionsebene reicht.

Definition spezifischer Rollen für einzelne Abteilungen, einschließlich einer dedizierten Zuweisungslogik zur Wahrung der organisatorischen Integrität.

Etablierung einer "Super-Admin"-Rolle, die über uneingeschränkte Systemprivilegien verfügt, jedoch in der grafischen Benutzeroberfläche nicht sichtbar oder zuweisbar ist, um die Systemsicherheit zu maximieren.

Impersonierungsfunktion: Ermöglicht autorisierten Administratoren den temporären Zugriff auf Benutzerkonten zu Diagnose- und Supportzwecken.

Digitale Personalaktenführung: Systematische Erfassung und Archivierung von personalrelevanten Vorgängen und Dokumenten für jeden Mitarbeiter.

Digitalisiertes Formularwesen: Abwicklung interner Antragsverfahren, wie Urlaubsanträge oder Mitarbeiterbewertungen, über eine webbasierte Schnittstelle.

Einsatzberichterstattung: Modul zur Erstellung und Verwaltung von Einsatzprotokollen.

Aktivitätsprotokollierung: Lückenlose Aufzeichnung aller systemrelevanten Aktionen zur Gewährleistung der Nachvollziehbarkeit und Revision.

Technologische Grundlage
Backend: PHP 8.2+ / Laravel 12+

Frontend: Blade, AdminLTE 3, JavaScript

Datenbank: MySQL

Implementierte Kernbibliotheken:

spatie/laravel-permission: Zur Realisierung der Rollen- und Berechtigungslogik.

lab404/laravel-impersonate: Zur Implementierung der Impersonierungsfunktionalität.

Installations- und Inbetriebnahme-Anleitung
Die nachfolgenden Anweisungen beschreiben den Prozess zur Einrichtung einer lokalen Entwicklungsumgebung.

1. Klonen des Repositories

git clone [https://github.com/MrFirewall/EMS-Panel.git](https://github.com/MrFirewall/EMS-Panel.git)
cd EMS-Panel

2. Installation der Projektabhängigkeiten

# Installation der PHP-Abhängigkeiten via Composer
composer install

# Installation der JavaScript-Abhängigkeiten via NPM
npm install

3. Konfiguration der Umgebungsvariablen
Es ist erforderlich, die bereitgestellte Beispiel-Konfigurationsdatei zu duplizieren und einen applikationsspezifischen Sicherheitsschlüssel zu generieren.

cp .env.example .env
php artisan key:generate

4. Anpassung der Konfigurationsparameter
Die Datei .env ist zu editieren, um die Konfigurationsparameter, insbesondere die Zugangsdaten für die Datenbankverbindung, zu spezifizieren.

APP_NAME="EMS Verwaltung"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deine_datenbank
DB_USERNAME=dein_benutzername
DB_PASSWORD=dein_passwort

5. Datenbankmigration und Initialisierung
Die Ausführung des nachstehenden Befehls initiiert die Datenbankmigration zur Erstellung der erforderlichen Tabellenstruktur und führt anschließend die Seeder aus, um die Datenbank mit initialen Datensätzen zu befüllen.

php artisan migrate --seed

Anmerkung: Dieser Prozess umfasst sowohl die Schema-Migration als auch das Seeding mit fundamentalen Daten wie Rollen und Berechtigungen.

6. Kompilierung der Frontend-Assets
Die Frontend-Assets (CSS und JavaScript) müssen kompiliert werden.

npm run build

7. Erstellung des Storage-Symlinks

php artisan storage:link

8. Starten des Entwicklungsservers

php artisan serve

Nach erfolgreicher Ausführung der vorgenannten Schritte ist die Applikation unter der Adresse http://localhost:8000 erreichbar.

Administratorzugang und Rollenkonzept
Standard-Administrator: Nach der initialen Datenbankinitialisierung (Seeding) wird der Rolle ems-director voller administrativer Zugriff auf alle Systemfunktionen gewährt.

Super-Admin-Rolle: Eine zusätzliche Rolle namens Super-Admin existiert, welche äquivalente, allumfassende Berechtigungen besitzt. Diese Rolle ist jedoch innerhalb der Benutzeroberfläche weder sichtbar noch zuweisbar und muss einem Benutzerkonto manuell über die Kommandozeile (php artisan tinker) zugewiesen werden. Sie ist für Entwicklungs- und Wartungszwecke vorgesehen.

Lizenzierung
Die Nutzung dieser Software unterliegt den Bestimmungen der MIT-Lizenz.