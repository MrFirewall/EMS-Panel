# Emergency Medical Service Verwaltungssystem

## Projektdokumentation
Das vorliegende Dokument beschreibt das **Emergency Medical Service (EMS) Verwaltungssystem**, eine mittels des **Laravel Frameworks** entwickelte Softwareanwendung. Die primäre Funktion dieser Applikation besteht in der systematischen Verwaltung von Personal, zugewiesenen Rollen, Personalakten sowie internen Formularen.

---

## Funktionsumfang

### Zentrales Dashboard
Bereitstellung einer konsolidierten Übersicht über systemrelevante Informationen und Statistiken zur Effizienzsteigerung.

### Personalverwaltung
Module zur Erfassung, Modifikation und Verwaltung von Mitarbeiterprofilen.

### Hierarchisches Berechtigungssystem
- Implementierung einer granularen Zugriffskontrolle, basierend auf einer definierten Ranghierarchie (von Praktikanten bis zur Direktionsebene).
- Definition spezifischer Rollen für einzelne Abteilungen mit dedizierter Zuweisungslogik zur Wahrung der organisatorischen Integrität.
- Etablierung einer **Super-Admin-Rolle**, die über uneingeschränkte Systemprivilegien verfügt, jedoch in der GUI weder sichtbar noch zuweisbar ist, um die Systemsicherheit zu maximieren.

### Impersonierungsfunktion
Ermöglicht autorisierten Administratoren den temporären Zugriff auf Benutzerkonten zu Diagnose- und Supportzwecken.

### Digitale Personalaktenführung
Systematische Erfassung und Archivierung von personalrelevanten Vorgängen und Dokumenten für jeden Mitarbeiter.

### Digitalisiertes Formularwesen
Abwicklung interner Antragsverfahren (z. B. Urlaubsanträge oder Mitarbeiterbewertungen) über eine webbasierte Schnittstelle.

### Einsatzberichterstattung
Modul zur Erstellung und Verwaltung von Einsatzprotokollen.

### Aktivitätsprotokollierung
Lückenlose Aufzeichnung aller systemrelevanten Aktionen zur Gewährleistung der Nachvollziehbarkeit und Revision.

---

## Technologische Grundlage

**Backend:** PHP 8.2+ / Laravel 12+  
**Frontend:** Blade, AdminLTE 3, JavaScript  
**Datenbank:** MySQL

### Implementierte Kernbibliotheken
- **spatie/laravel-permission**: Rollen- und Berechtigungslogik
- **lab404/laravel-impersonate**: Impersonierungsfunktionalität
- **SocialiteProviders/Cfx.re**: Zur Authentifizierung über das Cfx.re-Netzwerk

---

## Installations- und Inbetriebnahme-Anleitung

### 1. Klonen des Repositories
```bash
git clone https://github.com/MrFirewall/EMS-Panel.git
cd EMS-Panel
```

### 2. Installation der Projektabhängigkeiten
```bash
# PHP-Abhängigkeiten installieren
composer install

# JavaScript-Abhängigkeiten installieren
npm install
```

### 3. Konfiguration der Umgebungsvariablen
```bash
cp .env.example .env
php artisan key:generate
php artisan cfx:keys
```

### 4. Anpassung der Konfigurationsparameter
Bearbeite die `.env`-Datei und trage deine spezifischen Werte ein:
```env
APP_NAME="EMS Verwaltung"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deine_datenbank
DB_USERNAME=dein_benutzername
DB_PASSWORD=dein_passwort

CFX_APP_NAME="EMS Verwaltung"
CFX_REDIRECT_URL="https://DEINE_DOMAIN/login/cfx/callback"
CFX_PUBLIC_KEY="${APP_KEY_PATH}/cfx-public.key"
CFX_PRIVATE_KEY="${APP_KEY_PATH}/cfx-private.key"
```

### 5. Datenbankmigration und Initialisierung
```bash
php artisan migrate --seed
```
> Dieser Prozess umfasst sowohl die Schema-Migration als auch das Seeding mit grundlegenden Rollen und Berechtigungen.

#### Hinweis zum `/database/seeders/PermissionsSeeder.php`
Um dem ersten Benutzer automatisch die **Super-Admin-Rolle** zuzuweisen, kann folgender Codeabschnitt am Ende des Seeders eingefügt werden:

```php
// Optional: Weise die Super-Admin Rolle einem bestimmten User zu (z.B. User mit ID 1)
$user = User::find(1);
if ($user) {
    $user->assignRole('Super-Admin');
}
```

---

### 6. Kompilierung der Frontend-Assets
```bash
npm run build
```

### 7. Erstellung des Storage-Symlinks
```bash
php artisan storage:link
```

### 8. Starten des Entwicklungsservers
```bash
php artisan serve
```
Die Applikation ist anschließend unter **http://localhost:8000** erreichbar.

---

## Administratorzugang und Rollenkonzept

### Standard-Administrator
Nach dem Seeding-Prozess erhält die Rolle **ems-director** vollen administrativen Zugriff auf alle Systemfunktionen.

### Super-Admin-Rolle
Eine zusätzliche Rolle namens **Super-Admin** existiert mit äquivalenten, umfassenden Berechtigungen. Diese Rolle ist in der Benutzeroberfläche weder sichtbar noch zuweisbar und kann nur über die Kommandozeile (z. B. via `php artisan tinker`) vergeben werden.

Diese Rolle dient ausschließlich Entwicklungs- und Wartungszwecken.

---

## Lizenzierung
Diese Software wird unter den Bedingungen der **MIT-Lizenz** bereitgestellt.

## Vorlagen für Berichte erstellen

# Ort der Vorlagen /storage/app/templates/vorlagen.txt

# 1. Das Skript ausführen, um die Konfigurationsdatei neu zu schreiben
php artisan import:report-templates

# 2. Den Konfigurations-Cache von Laravel leeren, damit die Änderungen live gehen
php artisan config:clear