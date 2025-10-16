# 🚑 Emergency Medical Service Verwaltungssystem

## 📘 Projektdokumentation

Das **Emergency Medical Service (EMS) Verwaltungssystem** ist eine mit dem **Laravel Framework** entwickelte Softwareanwendung zur systematischen Verwaltung von Personal, Bürgerakten (Krankenakten), Rollen und internen Formularen.

---

## ⚙️ Funktionsumfang

### 🧭 Zentrales Dashboard
Bereitstellung einer konsolidierten Übersicht über systemrelevante Informationen und Statistiken zur Effizienzsteigerung.

### 👨‍⚕️ Personalverwaltung
Module zur Erfassung, Modifikation und Verwaltung von Mitarbeiterprofilen.

### 🩺 Bürgerakten-Verwaltung (Krankenakten)
System zur Erfassung und Verwaltung von Bürgerdaten inklusive Suchfunktion.  
Zentrale Detailansicht ("Krankenakte") pro Bürger, die eine chronologische Übersicht aller zugeordneten Einsatzberichte darstellt.

### 🚨 Einsatzberichterstattung mit Vorlagen
Modul zur Erstellung und Verwaltung von Einsatzprotokollen. Um die Konsistenz und Geschwindigkeit bei der Berichterstellung zu erhöhen, können Administratoren Text-Vorlagen definieren. Diese Vorlagen stehen den Benutzern beim Ausfüllen eines Berichts zur Verfügung und können per Klick eingefügt werden.

### 🧩 Hierarchisches Berechtigungssystem
Implementierung einer granularen Zugriffskontrolle, basierend auf einer definierten Ranghierarchie.  
Definition spezifischer Rollen für einzelne Abteilungen mit dedizierter Zuweisungslogik.  
Etablierung einer Super-Admin-Rolle, die über uneingeschränkte Systemprivilegien verfügt, jedoch in der GUI weder sichtbar noch zuweisbar ist.

### 🕵️‍♂️ Impersonierungsfunktion
Ermöglicht autorisierten Administratoren den temporären Zugriff auf Benutzerkonten zu Diagnose- und Supportzwecken.

### 📂 Digitale Personalaktenführung
Systematische Erfassung und Archivierung von personalrelevanten Vorgängen und Dokumenten für jeden Mitarbeiter.

### 🧾 Digitalisiertes Formularwesen
Abwicklung interner Antragsverfahren (z. B. Urlaubsanträge oder Mitarbeiterbewertungen) über eine webbasierte Schnittstelle.

### 🧠 Aktivitätsprotokollierung
Lückenlose Aufzeichnung aller systemrelevanten Aktionen zur Gewährleistung der Nachvollziehbarkeit und Revision.

---

## 🧑‍💻 Technologische Grundlage

- **Backend:** PHP 8.2+ / Laravel 12+
- **Frontend:** Blade, AdminLTE 3 (inkl. Dark Mode & Preloader), JavaScript
- **Datenbank:** MySQL

---

## 🧱 Implementierte Kernbibliotheken

- **spatie/laravel-permission:** Rollen- und Berechtigungslogik  
- **lab404/laravel-impersonate:** Impersonierungsfunktionalität  
- **SocialiteProviders/Cfx.re:** Authentifizierung über das Cfx.re-Netzwerk  

---

## 🌟 Highlights & Besondere Features

### 🎨 Dynamisches Frontend
- **Dark Mode:** Nutzerpräferenz wird im `localStorage` gespeichert.
- **Preloader:** Animierte EKG-Linie als Ladeanimation für professionelles Erscheinungsbild.

### 🩺 Bürgerakten als "Krankenakte"
Das System ermöglicht die Führung einer digitalen Akte für jeden Bürger. Die Detailansicht aggregiert automatisch alle Einsatzberichte, in denen der Bürger als Patient erfasst wurde. Dies schafft eine chronologische "Krankenakte" zur Nachverfolgung der medizinischen Vorgeschichte.

---

## 🧩 Installations- und Inbetriebnahme-Anleitung

### 1️⃣ Repository klonen
```bash
git clone https://github.com/MrFirewall/EMS-Panel.git
cd EMS-Panel
```

### 2️⃣ Abhängigkeiten installieren
```bash
# PHP-Abhängigkeiten
composer install

# JavaScript-Abhängigkeiten (optional)
# npm install
```

### 3️⃣ Umgebungsvariablen konfigurieren
```bash
cp .env.example .env
php artisan key:generate
php artisan cfx:keys
```

### 4️⃣ Konfiguration anpassen
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

### 5️⃣ Datenbankmigration & Seeding
```bash
php artisan migrate --seed
```

**Hinweis:** Um dem ersten Benutzer automatisch die Super-Admin-Rolle zuzuweisen, kann folgender Code in `/database/seeders/PermissionsSeeder.php` ergänzt werden:

```php
// Optional: Weise die Super-Admin Rolle einem bestimmten User zu (z.B. User mit ID 1)
$user = \App\Models\User::find(1);
if ($user) {
    $user->assignRole('Super-Admin');
}
```

### 6️⃣ Frontend-Assets kompilieren (optional)
```bash
npm run build
```

### 7️⃣ Storage-Symlink erstellen
```bash
php artisan storage:link
```

### 8️⃣ Entwicklungsserver starten
```bash
php artisan serve
```
Die Applikation ist anschließend unter [http://localhost:8000](http://localhost:8000) erreichbar.

---

## 🔐 Administratorzugang & Rollenkonzept

### 👨‍💼 Standard-Administrator
Nach dem Seeding-Prozess erhält die Rolle `ems-director` vollen administrativen Zugriff.

### 🛡️ Super-Admin-Rolle
Eine zusätzliche Rolle namens `Super-Admin` existiert mit umfassenden Berechtigungen. Diese Rolle ist in der Benutzeroberfläche weder sichtbar noch zuweisbar und kann nur über die Kommandozeile (z. B. `php artisan tinker`) vergeben werden.  
Dient ausschließlich Entwicklungs- und Wartungszwecken.

---

## 🧾 Vorlagen für Berichte erstellen

1️⃣ **Vorlagendatei erstellen/bearbeiten:**  
`/storage/app/templates/vorlagen.txt`

2️⃣ **Vorlagen importieren:**
```bash
php artisan import:report-templates
```

3️⃣ **Cache leeren:**
```bash
php artisan config:clear
```

---

## 📜 Lizenzierung

Diese Software wird unter den Bedingungen der **MIT-Lizenz** bereitgestellt.

