# Tests

Übersicht über die automatisierten Tests von rela (eHealth): wie man sie ausführt, wie sie aufgebaut sind und was sie absichern.

> **Beim Hinzufügen eines Tests bitte diese Datei ergänzen**, siehe [Neuen Test schreiben](#neuen-test-schreiben).

Stand 06.10.2026: **38 Testfälle in 10 Dateien**, Laufzeit rund 1–2 Sekunden.
Aktuelle Zahlen: `vendor/bin/phpunit --list-tests`

## Inhalt

- [Tests ausführen](#tests-ausführen)
- [Aufbau](#aufbau)
- [Übersicht aller Tests](#übersicht-aller-tests)
- [Fachliche Regeln, die die Tests festhalten](#fachliche-regeln-die-die-tests-festhalten)
- [Neuen Test schreiben](#neuen-test-schreiben)

---

## Tests ausführen

```bash
vendor/bin/sail artisan test --compact                      # alle Tests
vendor/bin/sail artisan test --compact tests/Feature/User   # ein Ordner / eine Datei
vendor/bin/sail artisan test --compact --filter test_name   # einzelne Tests
```

Ohne Sail gehen dieselben Befehle mit `php artisan test` bzw. `vendor/bin/phpunit`.

- **Datenbank:** immer SQLite im Arbeitsspeicher, nie die Datenbank aus der `.env` (`tests/TestCase.php` bricht sonst ab). Das gilt für **beide** Verbindungen: die Standard-Verbindung und `mysql_info` (Personendaten der Gesundheitsblätter).

---

## Aufbau

### Konfiguration

| Datei | Zweck |
|---|---|
| `phpunit.xml` | SQLite in-memory für beide Verbindungen, alle Umgebungswerte mit `force="true"`, `ADMIN_PASSWORD` für den Seeder |
| `tests/TestCase.php` | `RefreshDatabase` mit `TestingSeeder`, Transaktionen auf `sqlite` **und** `mysql_info`, Schutz vor echter DB, `withoutVite()` |
| `database/seeders/TestingSeeder.php` | Stammdaten: Gesundheitsstatus, Rollen, Admin (id 1), Global-Camp, ein paar Städte und Gruppen. Keine Demo-Daten. |

### Testdaten: Szenario

`tests/Support/CampScenario.php`: `$s = CampScenario::create();`

| Eigenschaft | Inhalt |
|---|---|
| `$s->camp` | Laufendes Lager mit eigenem Lager-Code |
| `$s->admin`, `$s->manager`, `$s->helper`, `$s->participant` | Je ein User pro Rolle, aktuelles Lager = `$s->camp`, mit `camp_users`-Eintrag. `$s->users()` liefert die Rollen-Map. |
| `$s->question` | Aktive Zusatzfrage des Lagers |
| `$s->openForm`, `$s->openInfo`, `$s->openCode` | Offenes Gesundheitsblatt (Personendaten, medizinischer Teil, persönlicher Code im Klartext) mit Antwort und zwei Interventionen |
| `$s->finishedForm`, `$s->finishedInfo`, `$s->finishedCode` | Abgeschlossenes Gesundheitsblatt |
| `$s->formWithoutGroup` | Randfall: Blatt ohne Gruppe |
| `$s->openIntervention`, `$s->closedIntervention` | Offene (gelb) und abgeschlossene (grün) Intervention zu `openInfo` |
| `$s->otherCamp`, `$s->otherManager`, `$s->otherForm`, `$s->otherInfo`, `$s->otherCode` | Fremdes Lager für Tests der Mandanten-Trennung |

`CampScenario::createHealthForm($camp, $attributes)` legt ein weiteres Gesundheitsblatt so an, wie die App es tut (HealthForm mit verschlüsseltem Code, HealthInformation mit Klartext-Code, Antworten auf die Fragen des Lagers).

### Factories

| Factory | Standard | States / Hilfen |
|---|---|---|
| `User::factory()` | Teilnehmende, verifiziert, kein Demo-User, Global-Camp, Passwort `password` | `admin()`, `manager()`, `helper()`, `unverified()`, `demo()`, `inCamp($camp)` (setzt aktuelles Lager und `camp_users`) |
| `Camp::factory()` | Laufendes Lager, zufälliger 4-stelliger Code | `finished()` |
| `CampUser::factory()` | Helfer in neuem Lager | |
| `HealthForm::factory()` | Personendaten, verschlüsselter Code, zufällige Gruppe. **Kein `camp_id`** | |
| `HealthInformation::factory()` | Klartext-Code, Status grün | |
| `Question::factory()`, `HealthInformationQuestion::factory()` | Aktive Frage / leere Antwort | |
| `Intervention::factory()` | **Erwartet `health_information_id`**, sonst zufällige bestehende | |
| `Help::factory()` | Hilfetext | |

---

## Übersicht aller Tests

| Bereich | Datei | Fälle | Prüft |
|---|---|---:|---|
| Grundlagen | `TestInfrastructureTest` | 7 | Test-DB, Seeder, Szenario, Seitenaufruf über den ganzen Stack |
| Sicherheit | `Security/AdminRoutesTest` | 8 | Wartungsrouten nur für Admins, gefährliche Routen entfernt |
| Models | `Models/RoleTest` | 1 | Rollen-Prüfungen liefern Booleans |
| Seeder | `Seeders/PermissionRoleSeederTest` | 1 | Admin-Passwort kommt aus der Config |
| Gesundheitsblatt | `HealthForm/HealthFormAccessTest` | 11 | Zugriff nur mit Lager- und persönlichem Code oder als Lagerleitung |
| Gesundheitsblatt | `User/UserTest` | 2 | Öffnen des Gesundheitsblatts mit Codes |
| Login | `Auth/LoginTest` | 3 | Login mit richtigen/falschen Daten |
| Registrierung | `Auth/RegistrationTest` | 3 | Registrierung, doppelte E-Mail, Passwort-Bestätigung |
| Beispiele | `ExampleTest` (Feature, Unit) | 2 | Laravel-Beispiele |

### Grundlagen

**[`TestInfrastructureTest`](Feature/TestInfrastructureTest.php)**: sichert ab, dass die Test-Umgebung selbst funktioniert.

| Test | Prüft |
|---|---|
| `test_both_connections_use_sqlite_in_memory` | Beide Verbindungen laufen auf SQLite in-memory |
| `test_testing_seeder_creates_master_data_without_demo_data` | Rollen, Status, Admin, Global-Camp, Städte, Gruppen vorhanden; kein Demo-Lager |
| `test_mysql_info_connection_is_reset_between_tests_*` | `mysql_info` wird pro Test zurückgesetzt, ist aber nicht leer |
| `test_scenario_links_health_form_and_health_information_by_code` | Code-Verknüpfung, Fragen, Interventionen im Szenario |
| `test_scenario_users_have_expected_roles` | Rollen der Szenario-User |
| `test_dashboard_renders_for_manager` | Dashboard rendert über den ganzen Stack |

### Sicherheit

**[`AdminRoutesTest`](Feature/Security/AdminRoutesTest.php)**: die Wartungsrouten `admin/run-*` führen Artisan-Befehle aus. Artisan ist im Test gefakt.

| Test | Prüft |
|---|---|
| `test_guest_cannot_run_maintenance_command` | Gäste werden umgeleitet, kein Befehl läuft |
| `test_manager_cannot_run_maintenance_command` | Lagerleitung wird auf `/dashboard` umgeleitet, kein Befehl läuft |
| `test_admin_can_run_maintenance_command` | Admin löst genau den erwarteten Befehl aus |
| `test_removed_route_does_not_exist_for_admins` | `run-migrations-seed` (`migrate:refresh`, löschte alle Daten) und `run-seeding` (erfundene Interventionen) gibt es nicht mehr, auch nicht für Admins |

### Models und Seeder

**[`RoleTest`](Feature/Models/RoleTest.php)**: `isAdmin()`, `isManager()`, `isHelper()` liefern `true`/`false` (Casts auf `Role`), nicht `1`/`0`.

**[`PermissionRoleSeederTest`](Feature/Seeders/PermissionRoleSeederTest.php)**: das Admin-Passwort kommt aus `config('app.admin_password')`. Mit `env()` direkt im Seeder wäre es bei gecachter Config `null`.

### Gesundheitsblatt

**[`HealthFormAccessTest`](Feature/HealthForm/HealthFormAccessTest.php)**: Zugriff auf Anzeigen, Druckansicht, Allergiepass und Speichern eines Gesundheitsblatts.

| Test | Prüft |
|---|---|
| `test_guest_without_codes_cannot_*` | Ohne Codes: 403 auf Anzeigen, Druckansicht, Allergiepass, Speichern (Daten unverändert) |
| `test_guest_with_correct_codes_can_open_and_update_health_form` | Mit beiden Codes: Formular, Anzeige, Druckansicht und Speichern funktionieren |
| `test_guest_with_wrong_personal_code_gets_no_access` | Falscher persönlicher Code: Fehlermeldung, kein Zugriff |
| `test_personal_code_does_not_work_with_code_of_other_camp` | Persönlicher Code nur zusammen mit dem Code *seines* Lagers |
| `test_codes_of_one_health_form_do_not_unlock_another` | Freischaltung gilt nur für das eine Blatt |
| `test_manager_of_same_camp_can_show_health_form` | Lagerleitung des eigenen Lagers darf |
| `test_manager_of_other_camp_cannot_show_health_form` | Lagerleitung eines fremden Lagers darf nicht |
| `test_helper_of_same_camp_cannot_show_health_form` | Helfende dürfen nicht |

**[`UserTest`](Feature/User/UserTest.php)**: Einstieg der Teilnehmenden.

| Test | Prüft |
|---|---|
| `test_participant_sees_health_form_with_correct_codes` | Mit Codes erscheint das Formular mit dem eigenen Namen |
| `test_participant_cannot_open_health_form_of_finished_camp` | Abgeschlossenes Lager: Fehlermeldung |

---

## Fachliche Regeln, die die Tests festhalten

### Zugriff auf ein Gesundheitsblatt

| Wer | Anzeigen / Druck / Allergiepass / Speichern |
|---|---|
| Gast ohne Codes | nein (403) |
| Gast mit Lager-Code + persönlichem Code (beide zum selben Blatt) | ja, nur dieses Blatt, solange die Session läuft |
| Lagerleitung des Lagers, zu dem das Blatt gehört | ja |
| Lagerleitung eines anderen Lagers | nein |
| Helfende, Teilnehmende (eingeloggt) | nein |

### Wartungsrouten `admin/run-*`

Nur eingeloggte, verifizierte Admins (`run-migrations`, `run-deployment`). Lagerleitung und Gäste werden umgeleitet. `run-migrations-seed` und `run-seeding` sind entfernt.

---

## Neuen Test schreiben

1. Ort wählen: `tests/Feature/Security` für Berechtigungen, `tests/Feature/Smoke` für seitenweite Prüfungen, sonst `tests/Feature/<Bereich>`.
2. Von `Tests\TestCase` erben, Testdaten mit `CampScenario` oder den Factories.
3. Bei Fehlern zuerst den Test (rot), dann den Fix.
4. Methoden `test_<englisch_snake_case>`, Erklärungen auf Deutsch.
5. Externe Dienste nie wirklich aufrufen (`Http::fake()`, `Mail::fake()`, Socialite-Mock).
6. Diese Datei ergänzen.

### Vorlage für einen neuen Eintrag

```markdown
**[`NeuerTest`](Feature/NeuerTest.php)**: wofür der Test zuständig ist.

| Test | Prüft |
|---|---|
| `test_...` | ... |
```
