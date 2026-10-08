# Tests

Übersicht über die automatisierten Tests von rela (eHealth): wie man sie ausführt, wie sie aufgebaut sind und was sie absichern.

> **Beim Hinzufügen eines Tests bitte diese Datei ergänzen**, siehe [Neuen Test schreiben](#neuen-test-schreiben).

Stand 08.10.2026: **261 Testfälle in 36 Dateien**, Laufzeit rund 8 Sekunden.
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

```bash
vendor/bin/pint --test   # Code-Stil prüfen
vendor/bin/pint          # Code-Stil korrigieren
```

- **CI:** `.github/workflows/tests.yml` mit den Jobs `pint` und `phpunit`, bei Push auf `develop` und bei jedem Pull Request. Kein MySQL nötig, die Tests laufen mit `.env.example`.
- **Code-Stil:** Laravel Pint mit Preset `laravel` (`pint.json`). Der Git-Hook `.githooks/pre-commit` formatiert gestagte PHP-Dateien beim Commit (aktiviert durch `composer install`). VS Code formatiert PHP beim Speichern (`.vscode/settings.json`, Erweiterung `open-southeners.laravel-pint`).
- **Werte aus `.env.example`**, die Tests sonst stören, setzt `phpunit.xml` fest: `MAIL_FROM_ADDRESS`.

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
| Smoke | `Smoke/AllRoutesSmokeTest` | 5 | Jede GET-Route pro Rolle (Gast, Teilnehmende, Helfende, Lagerleitung, Admin) ohne Serverfehler |
| Smoke | `Smoke/PagesRenderTest` | 47 | Alle verlinkten Seiten pro Rolle mit 200, Navigation pro Rolle |
| Sicherheit | `Security/CampAccessTest` | 49 | Gesundheitsdaten nur für Helfende+ und nur im eigenen Lager |
| Sicherheit | `Security/ProfileTest` | 6 | Profil: nur eigenes Konto, nur E-Mail und Passwort |
| Sicherheit | `Security/CampSwitchTest` | 4 | Lagerwechsel nur mit aktiver Mitgliedschaft |
| Sicherheit | `Security/UserManagementTest` | 14 | Leitende verwalten: nur eigenes Lager, nie Admins, erlaubte Rollen |
| Sicherheit | `Security/CampManagementTest` | 6 | Lager bearbeiten/löschen nur als Lagerleitung dieses Lagers |
| Sicherheit | `Security/FileAccessTest` | 4 | Downloads nur aus `storage/app/files`, Gesundheitsblatt erstellen nur mit Formularfeldern |
| Sicherheit | `Security/QuestionAccessTest` | 3 | Individuelle Fragen nur im eigenen Lager, nur Formularfelder |
| Sicherheit | `Security/RemovedFeaturesTest` | 2 | Entfernter Cevi-DB-Import der Gesundheitsblätter bleibt entfernt |
| Sicherheit | `Security/AdminRoutesTest` | 8 | Wartungsrouten nur für Admins, gefährliche Routen entfernt |
| Models | `Models/RoleTest` | 1 | Rollen-Prüfungen liefern Booleans |
| Seeder | `Seeders/PermissionRoleSeederTest` | 1 | Admin-Passwort kommt aus der Config |
| Gesundheitsblatt | `HealthForm/HealthFormAccessTest` | 13 | Zugriff nur mit Lager- und persönlichem Code oder als Lagerleitung |
| Gesundheitsblatt | `User/UserTest` | 2 | Öffnen des Gesundheitsblatts mit Codes |
| Login | `Auth/LoginTest` | 3 | Login mit richtigen/falschen Daten |
| Login | `Auth/DemoLoginTest` | 4 | Demo-Logins mit und ohne Demo-Daten |
| Interventionen | `Intervention/InterventionRequestTest` | 5 | AJAX-Endpunkte mit fehlenden Parametern |
| Interventionen | `Intervention/InterventionStoreTest` | 5 | Speichern: nur Formularfelder, Nummern/Zuordnung/Bildpfad vom Server |
| Enums | `Enums/UserRoleTest` | 10 | Rollen: Werte = Tabelle `roles`, Rechte aus dem Enum, Vorauswahl im Formular |
| Enums | `Enums/TrafficLightTest` | 8 | Ampel: Werte = Tabelle `health_statuses`, Anzeige, Sortierung, Vorauswahl |
| Hilfen | `Helper/HealthFormLookupTest` | 5 | Suche nach persönlichem Code über `code_hash`, Nachtrag per Migration |
| Gesundheitsblatt | `HealthForm/HealthFormCreateTest` | 3 | Lagerleitung erstellt Blatt: Code, Gesundheitsinfo, Fragen |
| Gesundheitsblatt | `HealthForm/HealthFormImportTest` | 4 | Import der ausgefüllten Vorlage `public/files/vorlage.xlsx` |
| Gesundheitsblatt | `HealthForm/HealthFormExportTest` | 3 | Export nur mit Blättern des eigenen Lagers |
| Interventionen | `Intervention/InterventionWorkflowTest` | 5 | Folge-Interventionen, Abschliessen, Status (Ampel) |
| Lager | `Camp/CampDeleteTest` | 2 | Lager löschen: Daten und Uploads nur dieses Lagers, Zähler auf der Startseite |
| Gesundheitsblatt | `HealthForm/ParticipantFlowTest` | 6 | Ablauf der Teilnehmenden: Fehler führen zurück zum eigenen Formular, Schreibschutz abgeschlossener Blätter |
| Fragen | `Question/QuestionTest` | 2 | Neue Frage erscheint auch in bestehenden Gesundheitsblättern |
| Tasks | `Console/DailyTaskTest` | 3 | `task:daily` täglich 3 Uhr, setzt nur das Demo-Lager zurück |
| Gesundheitsblatt | `HealthForm/HealthFormUpdateTest` | 7 | Speichern und Abschliessen durch Teilnehmende, nur Formularfelder, Allergiepass |
| Gesundheitsinfos | `HealthInformation/HealthInformationRequestTest` | 7 | Suche nach Code, Protokoll hoch-/herunterladen, Teilnehmerliste nur Excel/CSV |
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

### Smoke

**[`AllRoutesSmokeTest`](Feature/Smoke/AllRoutesSmokeTest.php)**: liest alle GET-Routen aus dem Router und ruft jede pro Rolle auf, mit Standard-Objekten und Randfällen aus dem Szenario (Blatt ohne Gruppe, abgeschlossene Intervention). Erwartung: kein Status >= 500.

- Neue Routen werden automatisch mitgetestet. Fehlt ein Wert für einen Parameter, `routeParameters()` ergänzen.
- `EXCLUDED`: Wartungsrouten und signierte URL, mit Begründung.
- `KNOWN_ISSUES`: bekannte Serverfehler (aktuell keine). Der Test ist rot, wenn ein neuer Fehler auftritt **oder** ein bekannter behoben ist (dann Eintrag entfernen).

**[`PagesRenderTest`](Feature/Smoke/PagesRenderTest.php)**: alle Seiten, die Navigation (`components/navbar-*.blade.php`) und Dashboard einer Rolle verlinken.

| Test | Prüft |
|---|---|
| `test_linked_page_renders_for_role` | Pro Rolle und Seite Status 200, inkl. Randfällen (Patientenakte ohne Interventionen, abgeschlossenes Blatt, Blatt ohne Gruppe) |
| `test_navigation_shows_only_allowed_entries` | Jede Rolle sieht genau ihre Menüpunkte |
| `test_dashboard_hides_health_record_search_from_participants` | Teilnehmende sehen auf dem Dashboard weder Suche noch Liste, sondern einen Hinweis |
| `test_dashboard_shows_health_record_search_to_helpers` | Helfende sehen die Suche |

### Robustheit

**[`DemoLoginTest`](Feature/Auth/DemoLoginTest.php)**: `/loginLeiter` und `/loginLagerleiter` loggen mit `DemoCampSeeder` den Demo-User ein; ohne Demo-Daten 404 statt Absturz.

**[`InterventionRequestTest`](Feature/Intervention/InterventionRequestTest.php)**: DataTables ohne Filter, `addNew` ohne Parameter (422), `closeAjax`.

**[`HealthInformationRequestTest`](Feature/HealthInformation/HealthInformationRequestTest.php)**: Suche ohne Code, mit eigenem und fremdem Code; Protokoll-Download ohne Datei (404).

`HealthFormAccessTest` zusätzlich: `GET /healthform` ohne Codes leitet zur Startseite, Allergiepass-Download ohne Datei 404.

### Enums

Feste IDs stehen als Enums in `app/Enums` (früher `config/status.php`, gelöscht):

| Enum | Fälle (Wert) | Spalten (Cast) |
|---|---|---|
| `UserRole` | `Administrator` (1), `CampLeader` (2), `Helper` (3), `Participant` (4) | `users.role_id`, `camp_users.role_id` |
| `TrafficLight` | `Green` (10), `Yellow` (20), `Red` (30) | `interventions.health_status_id`, `health_information.health_status_id` |

- `User::isAdmin()/isManager()/isHelper()` kommen aus `UserRole` (`canManage()`, `canHelp()`); die Flags der Tabelle `roles` müssen dazu passen (Test).
- In Requests und URLs `->value`, in Models, Abfragen und Assertions das Enum. Vergleiche nur mit `===` oder Methoden, nie `<`/`>`/`==`.
- `components/forms/select.blade.php` wählt auch bei Enum-Werten den aktuellen Eintrag vor (Test für beide Enums).

**[`UserRoleTest`](Feature/Enums/UserRoleTest.php)**, **[`TrafficLightTest`](Feature/Enums/TrafficLightTest.php)**: Werte gegen die Tabellen, Rechte und Ampelfarben per Data Provider, Cast, Sortierung nach Dringlichkeit in der Teilnehmerübersicht, Vorauswahl im Formular.

**[`HealthFormLookupTest`](Feature/Helper/HealthFormLookupTest.php)**: `HealthForm.code` ist nicht deterministisch verschlüsselt; gesucht wird über `code_hash` (HMAC-SHA256 mit `APP_KEY`, gesetzt beim Speichern). `Helper::getHealthForm()` lädt nur noch das passende Blatt (vorher alle Blätter aller Lager, die Teilnehmerübersicht brauchte bei 500 Blättern ~7 s, jetzt ~0,3 s). Die Migration trägt den Hash für bestehende Blätter nach (`fillCodeHashes()`).

**[`HealthFormCreateTest`](Feature/HealthForm/HealthFormCreateTest.php)**: 6-stelliger Code, Gesundheitsinfo im selben Lager mit den Fragen des Lagers (`Helper::createHealthInformation`); bei „Teilnehmende füllen selbst aus“ zurück zur Liste.

**[`HealthFormImportTest`](Feature/HealthForm/HealthFormImportTest.php)**: Füllt die echte Vorlage aus und lädt sie hoch. Excel-Datum und Text-Datum, Ceviname fällt auf Vorname zurück, Abteilung über Kurzname; Zeilen ohne AHV oder Geburtstag werden übersprungen; ein zweiter Import derselben Liste erzeugt nichts Neues (vorher: verwaiste Gesundheitsinfos); importierte Blätter bekommen die Fragen des Lagers.

**[`HealthFormExportTest`](Feature/HealthForm/HealthFormExportTest.php)**: `HealthFormsExport` bekommt das Lager; vorher enthielt der Export die Blätter **aller** Lager (Namen, AHV, Codes).

**[`InterventionWorkflowTest`](Feature/Intervention/InterventionWorkflowTest.php)**: Folge-Interventionen nummeriert pro Master (`max_serial_number`), mehrere Nachträge auf einmal, Status des Masters = Status der neusten Folge-Intervention, Abschliessen des Masters schliesst offene Folge-Interventionen mit denselben Angaben.

**[`CampDeleteTest`](Feature/Camp/CampDeleteTest.php)**: Gesundheitsinfos, Blätter, Interventionen, Mitgliedschaften und Upload-Ordner des Lagers werden gelöscht, andere Lager bleiben; das Lager bleibt als abgeschlossen mit Anzahl Blätter für die Statistik der Startseite.

**[`ParticipantFlowTest`](Feature/HealthForm/ParticipantFlowTest.php)**: Scheitert die Validierung, leitet `HealthFormUpdateRequest` auf `/healthform?form=<id>`; das Blatt erscheint wieder mit Fehlern und den bisherigen Eingaben (`old()` in `components/forms/text*.blade.php`), auch wenn mehrere Blätter freigeschaltet sind. Ohne Freischaltung oder bei beendetem Lager: Startseite. Abgeschlossene Blätter sind nur lesbar, wenn das Lager „Abgeschlossene Blätter schliessen“ (`forms_finished`) gesetzt hat.

**[`QuestionTest`](Feature/Question/QuestionTest.php)**: Eine neue individuelle Frage wird allen bestehenden Gesundheitsblättern des Lagers (leer) hinzugefügt und erscheint im Formular der Teilnehmenden.

**[`DailyTaskTest`](Feature/Console/DailyTaskTest.php)**: Im Scheduler täglich um 3 Uhr (Prod-Cron ruft `schedule:run` auf). Setzt Code, Status und Daten des Demo-Lagers zurück, andere Lager bleiben unverändert; ohne Demo-Lager kein Absturz.

**[`HealthFormUpdateTest`](Feature/HealthForm/HealthFormUpdateTest.php)**: Teilnehmende nach Eingabe der Codes.

| Test | Prüft |
|---|---|
| `test_save_keeps_health_form_open` | „Speichern“ (`submit_btn=save`) lässt das Blatt offen |
| `test_close_with_privacy_agreement_finishes_health_form` | „Abschliessen“ (`submit_btn=close`) schliesst ab; war seit 04.03.2026 kaputt (Button-Wert `close`, Controller verglich mit dem Button-Text) |
| `test_close_without_privacy_agreement_is_rejected` | Abschliessen nur mit Datenschutz-Bestätigung |
| `test_fields_outside_the_form_are_ignored` | `camp_id`, `code`, `finish`, Dateipfade, `health_status_id` kommen nicht aus dem Formular |
| `test_answers_only_change_questions_of_own_health_form` | Antworten nur auf Fragen des eigenen Blatts |
| `test_allergy_pass_*` | Nur PDF, gespeichert als `Allergiepass.pdf` |

**[`InterventionStoreTest`](Feature/Intervention/InterventionStoreTest.php)**: Laufnummer aus dem Lager, `id`/`serial_number`/`user_id` nicht aus dem Formular, Folge- und Unter-Interventionen bleiben beim eigenen Gesundheitsblatt, Bildpfad nur aus echtem Upload.

### Sicherheit

**[`CampAccessTest`](Feature/Security/CampAccessTest.php)**: Gesundheitsinfos, Interventionen und Gesundheitsblätter im Dashboard.

| Test | Prüft |
|---|---|
| `test_participant_cannot_open_health_data_pages` | Eingeloggte Teilnehmende werden von allen Seiten mit Gesundheitsdaten weggeleitet |
| `test_helper_can_open_health_data_pages_of_own_camp` | Helfende sehen dieselben Seiten im eigenen Lager |
| `test_manager_cannot_open_pages_of_other_camp`, `test_admin_cannot_open_pages_of_other_camp` | Fremdes Lager: 403 auf Gesundheitsinfo, Interventionen, Druckansicht, Protokoll, Gesundheitsblatt |
| `test_helper_cannot_open_health_information_of_other_camp` | Dasselbe für Helfende |
| `test_user_without_camp_cannot_open_health_information` | Frisch registrierter User kommt nicht an Gesundheitsdaten |
| `test_code_search_*` | Code-Suche liefert nur Codes des eigenen Lagers und nur das Feld `code` |
| `test_helper_cannot_store/update/add_follow_up/update_sub_*` | Speichern von Interventionen: alle IDs aus dem Request müssen zum eigenen Gesundheitsblatt gehören |
| `test_helper_can_store_intervention_for_own_camp` | Normalfall funktioniert |
| `test_manager_cannot_reopen_health_form_of_other_camp`, `test_manager_cannot_upload_protocol_for_other_camp` | Aktionen der Lagerleitung nur im eigenen Lager |

**[`ProfileTest`](Feature/Security/ProfileTest.php)**: `PATCH /user/{user}` ändert nur das eigene Konto (sonst 403) und nur E-Mail und Passwort; `role_id`, `camp_id`, `demo` werden ignoriert. Das Profilformular sendet an diese Route (vorher an die Route der Lagerleitung, Helfende konnten ihr Passwort nicht ändern).

**[`CampSwitchTest`](Feature/Security/CampSwitchTest.php)**: `PATCH /camps/{camp}` wechselt nur in Lager mit aktiver Mitgliedschaft und ins Global-Camp; die Rolle kommt aus der Mitgliedschaft. Es wird keine Mitgliedschaft mehr nebenbei angelegt.

**[`UserManagementTest`](Feature/Security/UserManagementTest.php)**: Leitende verwalten (`dashboard/users`).

| Test | Prüft |
|---|---|
| `test_manager_can_update_member_including_password` | Lagerleitung darf Mitglieder bearbeiten, auch das Passwort |
| `test_manager_can_change_role_of_member_within_camp`, `test_role_change_does_not_touch_current_role_in_another_camp` | Rolle wird in der Mitgliedschaft gespeichert; die aktuelle Rolle nur, wenn die Person gerade in diesem Lager ist |
| `test_manager_cannot_grant_admin_role`, `test_add_cannot_grant_admin_role`, `test_store_creates_user_with_allowed_role_only` | Nie Administrator vergeben |
| `test_manager_cannot_edit_update_or_remove_user_of_other_camp` | Fremdes Lager: 403, nichts geändert |
| `test_manager_cannot_edit_or_update_admin`, `test_manager_cannot_edit_admin_of_own_camp` | Admins sind tabu |
| `test_store_ignores_other_fields` | Neue Person: nur Formularfelder |
| `test_search_*` | Suche nur mit exakter E-Mail, ohne Admins und Mitglieder, nur `id`, `username`, `email` |

**[`CampManagementTest`](Feature/Security/CampManagementTest.php)**: `dashboard/camps/{camp}` bearbeiten und löschen nur als Lagerleitung dieses Lagers (Mitgliedschaft, nicht aktuelle Rolle); nur Formularfelder (`global_camp`, `code`, `finish` usw. werden ignoriert); verantwortliche Person muss Lagerleitung des Lagers sein; beim Löschen wechseln nur Personen ins Global-Camp, die gerade in diesem Lager sind.

**[`FileAccessTest`](Feature/Security/FileAccessTest.php)**: `Helper::uploadedFilePath()` liefert nur Dateien, die wirklich in `storage/app/files` liegen. Vorher konnte über einen manipulierten Pfad (`../.env`) jede Datei des Servers heruntergeladen werden. Tests mit temporärem Speicher und einer Datei neben dem Upload-Ordner.

**[`QuestionAccessTest`](Feature/Security/QuestionAccessTest.php)**: `QuestionPolicy::manage`, Fragen nur im aktuellen Lager bearbeiten/löschen; `camp_id` kommt nie aus dem Formular.

**[`RemovedFeaturesTest`](Feature/Security/RemovedFeaturesTest.php)**: Der Cevi-DB-Import der Gesundheitsblätter (`POST dashboard/healthforms/import`) rief mit dem Server-Token `db.cevi.ch` auf und legte Blätter ohne Lager an. Route, Methode und Modal sind entfernt; der Test verhindert mit `Http::preventStrayRequests()` echte Anfragen nach aussen. Das Paket `ixudra/curl` ist entfernt.

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

### Gesundheitsdaten im Dashboard

| Rolle | Gesundheitsinfos, Interventionen, Gesundheitsblätter **im eigenen Lager** | **Fremdes Lager** |
|---|---|---|
| Teilnehmende (eingeloggt) | nein, nur Dashboard und eigenes Profil | nein |
| Helfende | ansehen, Interventionen erfassen und abschliessen | nein |
| Lagerleitung | alles (inkl. Gesundheitsblätter erfassen, importieren, öffnen) | nein |
| Admin | alles im aktuell gewählten Lager | nein, erst nach Wechsel ins Lager |

Umsetzung: Routen-Gruppen `helper`/`manager` in `routes/web.php`, Lager-Prüfung `Controller::authorizeCamp()`.

### Konten und Lager

Registrierung ist offen und jede Person kann ein eigenes Lager anlegen (wird dort Lagerleitung). Die Rolle Lagerleitung ist deshalb **nicht** vertrauenswürdig; alles ist auf das eigene Lager begrenzt.

| Aktion | Regel |
|---|---|
| Profil ändern | Nur eigenes Konto, nur E-Mail und Passwort |
| Lager wechseln | Nur mit aktiver Mitgliedschaft (`camp_users`) oder ins Global-Camp; Rolle aus der Mitgliedschaft |
| Leitende verwalten (bearbeiten inkl. Passwort, entfernen) | Lagerleitung: nur Mitglieder des aktuellen Lagers, nie Admins |
| Rollen vergeben | Nur Lagerleitung, Helfende, Teilnehmende (`UserRole::assignable()`) |
| Lager bearbeiten/löschen | Nur als Lagerleitung (oder Admin) dieses Lagers laut Mitgliedschaft |
| Personensuche | Nur exakte E-Mail-Adresse |

Umsetzung: `App\Policies\CampPolicy` (`manage`, `switchTo`), `App\Policies\UserPolicy` (`updateProfile`, `manage`), Form Requests in `app/Http/Requests`.

### Wartungsrouten `admin/run-*`

Nur eingeloggte, verifizierte Admins (`run-migrations`, `run-deployment`). Lagerleitung und Gäste werden umgeleitet. `run-migrations-seed` und `run-seeding` sind entfernt.

---

## Neuen Test schreiben

1. Ort wählen: `tests/Feature/Security` für Berechtigungen, `tests/Feature/Smoke` für seitenweite Prüfungen, sonst `tests/Feature/<Bereich>`.
1. Neue Routen mit Parametern: Wert in `AllRoutesSmokeTest::routeParameters()` ergänzen.
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
