<p align="center"><img src="public/img/logo.png" width="400"></p>
<p align="center"><img src="public/img/photogrid.jpg"></p>

## Online Gesundheits-Datenbank für J+S-Lager

Das Gesundheits-Tool bietet viele Funktionen, welche dir eine Übersicht über deine Teilnehmenden bei einem J+S-Lager geben:

- Erstelle dein Lager, importiere deine Teilnehmende und erstelle die Gesundheitsblätter, alles Online. Die Teilnehmenden füllen die Gesundheitsblätter Online aus.
- Jeder Teilnehmende sieht nur das eigene Gesundheitsblatt. Die Leitenden sehen nur einen Code und keine zusätzlichen Personenbezogenen Daten.
- Importiere alle Leitende und Teilnehmende direkt aus der Cevi-DB inklusive Profilbild, Benutzernahmen und E-Mail.
- Erfasse für alle Teilnehmenden bestimmte Interventionen (Patientenüberwachung, Verabreichte Medikation, 1.Hilfe Leistungen, ...) und behalte den Überblick.
- Falls gewünscht können die Gesundheitsblätter als PDF heruntergeladen werden.
- Das Tool kann sowohl auf einem Laptop wie auch auf dem Smartphone bedient werden und kann so auch für eine rasche Intervention genutzt werden.


## Lokale Installation

Das Tool ist ein PHP-Projekt basierend auf dem Framework [Laravel](https://laravel.com/). Um es lokal auszuführen brauchst du einen [Docker Container](https://docs.docker.com/).

Um das Tool lokal bei dir benutzen zu können musst du den Quellcode herunterladen und mittels [Laravel Sail](https://laravel.com/docs/9.x/sail) starten:

```bash
# clone the GitRepo
git clone https://github.com/jeromesigg/Gesundheits-DB.git
cd Gesundheits-DB

# install the dependencies
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs
    
cp .env.example .env

# launch the application
./vendor/bin/sail up

# initialize the database
./vendor/bin/sail artisan migrate --seed
```

Anschliessend kannst du dein Tool unter [http://localhost](http://localhost) aufrufen.
## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
