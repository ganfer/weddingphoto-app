# Hochzeits-Galerie (weddingphoto-app)

Eine schlanke, token-geschützte Web-App zum gemeinschaftlichen Hochladen und Anzeigen von Fotos (z. B. auf Hochzeiten). Enthält eine Galerie mit Lightbox, Mehrfach-Upload, serverseitigen Endpunkten, Caching-Headern sowie optionaler Synchronisation mit Nextcloud per WebDAV.

### Funktionsumfang
- **Token-geschützter Zugriff**: Zugang via URL-Token, 7 Tage Session-Gültigkeit
- **Mehrfach-Upload**: Drag/Drop bzw. Dateiauswahl (Frontend) → `upload.php`
- **Galerie & Lightbox**: Grid-Galerie, Vollbild-Ansicht, Keyboard-Navigation → `gallery.js`
- **Bilder-Auslieferung**: Serverseitig via `img.php` (kein Direktzugriff auf `img/`)
- **Performanz**: `gallery.php` liefert ETag/Last-Modified und unterstützt 304-Responses
- **Sicherheit**: `.htaccess` verhindert Directory Listing und blockiert sensible Dateien
- **Nextcloud-Sync (optional)**: `sync-to-nextcloud.php` lädt neue Dateien zu Nextcloud hoch und hält den lokalen Cache aktuell

---

## Systemvoraussetzungen
- PHP ≥ 8.0 (u. a. wegen `str_starts_with`)
- PHP-Erweiterungen: `curl`, `simplexml`, `fileinfo`
- Webserver mit `.htaccess`-Unterstützung (empfohlen: Apache). Hinweis: Root-`.htaccess` verwendet teilweise ältere Direktiven (Apache 2.2). Unter Apache 2.4 ggf. `mod_access_compat` aktivieren oder Regeln anpassen.
- Schreibrechte für den Webserver auf die Ordner `upload/` und `img/`

---

## Installation
1. Projektdateien in das Webroot (oder Unterverzeichnis) deployen.
2. Ordner anlegen (falls nicht vorhanden) und Rechte setzen:
   - `img/` (Cache für Anzeige)
   - `upload/` (temporäre Uploads, wird nach Nextcloud-Upload geleert)
3. Konfiguration in `config/config.php` anpassen:
   - `ACCESS_TOKEN`: geheimer Zugriffstoken, den Besucher per Link/QR erhalten
   - `NEXTCLOUD_URL`: WebDAV-URL des Zielordners (endet mit `/`)
   - `NEXTCLOUD_USER` / `NEXTCLOUD_PASS`: WebDAV-Zugangsdaten
4. Webserver-Konfiguration prüfen:
   - `.htaccess` im Root schützt u. a. `token.php`, verhindert Direktzugriff auf `img/` und beschränkt `sync-to-nextcloud.php` auf localhost (127.0.0.1) und eine Beispiel-IP. Passen Sie die IPs bei Bedarf an.
   - `.htaccess` in `config/` blockiert direkten Zugriff auf Konfigurationsdateien.
5. Seite aufrufen mit Token, z. B.:
   - `https://example.org/index.php?token=DEIN_GEHEIMER_TOKEN`
   Nach erfolgreicher Prüfung wird der Token in der Session gespeichert (7 Tage) und aus der URL entfernt.

---

## Nutzung
- **Fotos hochladen**: Auf der Startseite Dateien auswählen und hochladen. Erfolgreiche Uploads erscheinen sofort oben in der Galerie.
- **Galerie ansehen**: Klick auf ein Bild öffnet die Lightbox. Navigation per Pfeiltasten Links/Rechts oder per Buttons.

---

## Ordner- und Dateiübersicht
```
/ (Webroot)
├─ index.php                # Startseite (lädt JS/CSS, prüft Zugriff)
├─ upload.php               # POST-Upload-Endpunkt
├─ gallery.php              # Liefert JSON-Liste der Bilder (mit ETag)
├─ img.php                  # Liefert einzelne Bilder (no-store)
├─ token.php                # Zugriffskontrolle via URL-Token + Session
├─ sync-to-nextcloud.php    # Optionaler Sync mit Nextcloud (WebDAV)
├─ access_denied.php        # 403-Seite bei fehlendem Zugriff
├─ .htaccess                # Sicherheit/Restriktionen (Root)
├─ /config
│  ├─ config.php            # Token & Nextcloud-Zugangsdaten
│  └─ .htaccess             # Blockiert Zugriff auf .php/.ini/.env
├─ /css
│  └─ style.css             # Basis-Styles für App & Lightbox
├─ /js
│  ├─ gallery.js            # Galerie + Lightbox-Logik
│  └─ upload.js             # Upload-Logik (Fetch → upload.php)
├─ /img                     # Cache für Anzeige (vom Server gelesen)
└─ /upload                  # Temporäre Uploads (werden nach Nextcloud-Upload entfernt)
```

---

## Konfiguration & Sicherheit
- **Token-Lebensdauer**: Standard 7 Tage. Anpassbar in `token.php` (`$_SESSION['token_expires']`).
- **HTTPS**: Unbedingt HTTPS verwenden; Token niemals im Klartext transportieren.
- **Beschränkung direkter Zugriffe**:
  - Root-`.htaccess` deaktiviert Directory Listing (`Options -Indexes`).
  - Direkter Zugriff auf `img/` ist untersagt. Bilder werden ausschließlich über `img.php` ausgeliefert.
  - `token.php` ist per `.htaccess` gesperrt und wird nur via `require` genutzt.
- **Upload-Validierung**:
  - Serverseitig werden Dateiendungen gefiltert (`jpg`, `jpeg`, `png`, `gif`).
  - Für zusätzliche Sicherheit empfiehlt sich eine Inhaltsprüfung (MIME/Exif) und ggf. eine Umwandlung/Neukodierung der Bilder.
- **PHP-Limits**: Passen Sie `upload_max_filesize`, `post_max_size` und `max_file_uploads` in Ihrer `php.ini` an.
- **Berechtigungen**: Webserver-Benutzer benötigt Schreibrechte auf `upload/` und `img/`.

---

## API-Endpunkte (intern vom Frontend genutzt)
- `GET gallery.php`
  - Antwort: JSON-Array `[{ "src": "img.php?file=…", "filename": "…" }, …]`
  - Header: `ETag`, `Last-Modified`, `Cache-Control: public, max-age=3600`
  - Liefert `304 Not Modified`, wenn Client aktuell ist.
- `GET img.php?file=DATEINAME`
  - Antwort: Bilddaten (korrekter `Content-Type`)
  - Header: `Cache-Control: no-store`, `Pragma: no-cache`, `Expires: 0`
- `POST upload.php`
  - Erwartet: `multipart/form-data` mit `files[]`
  - Antwort: `{ "uploaded": ["…", …] }` oder `{ "error": "…" }`

Alle Endpunkte sind durch `checkAccess()` geschützt und erfordern eine gültige Session (zuvor über URL-Token gesetzt).

---

## Nextcloud-Synchronisation (optional)
Das Skript `sync-to-nextcloud.php` übernimmt zwei Aufgaben:
1) **Uploads nach Nextcloud**: Dateien aus `upload/` werden via WebDAV `PUT` nach `NEXTCLOUD_URL` hochgeladen. Bei Erfolg werden lokale Dateien in `upload/` gelöscht.
2) **Cache aktualisieren**: Per WebDAV `PROPFIND` werden Bilder im Nextcloud-Ordner ermittelt. Neue Dateien werden in `img/` heruntergeladen (falls nicht vorhanden); Bilder, die in Nextcloud gelöscht wurden, werden aus `img/` entfernt.

### Ausführung
- **CLI (empfohlen, z. B. per Cron)**:
  - `php /pfad/zur/app/sync-to-nextcloud.php`
  - Beispiel-Cron: `*/5 * * * * php /var/www/html/sync-to-nextcloud.php >/dev/null 2>&1`
- **HTTP (lokal/netz-intern)**:
  - Root-`.htaccess` erlaubt per Default nur `127.0.0.1` und eine Beispiel-IP. Passen Sie dies an oder rufen Sie das Skript direkt per CLI auf.

### Anforderungen
- `NEXTCLOUD_URL` muss auf das Zielverzeichnis unter WebDAV zeigen, z. B. `https://nextcloud.example.org/remote.php/dav/files/USER/Photos/`
- Gültige WebDAV-Zugangsdaten (`NEXTCLOUD_USER`, `NEXTCLOUD_PASS`)

---

## Lokale Entwicklung
- Mit dem PHP-Built-in-Server starten (Hinweis: `.htaccess` wird hier nicht berücksichtigt):
  - `php -S localhost:8000 -t /pfad/zur/app`
- Für realistische Tests (inkl. `.htaccess`) Apache/Nginx verwenden.

---

## Fehlerbehebung
- **403 Zugriff verweigert**: Token fehlt/ist abgelaufen. Seite mit `?token=…` aufrufen; Session-Cookies erlauben.
- **Bilder werden nicht angezeigt**: Schreibrechte für `img/` prüfen; `fileinfo`-Extension aktiv; Aufruf erfolgt über `img.php` (Direktzugriff ist gesperrt).
- **Upload schlägt fehl**: Rechte auf `upload/` prüfen; PHP-Upload-Limits; Server-Error-Log kontrollieren.
- **Nextcloud-Fehler**: URL/Benutzer/Passwort prüfen; WebDAV erreichbar; HTTP-Statuscode im Skript-Output beachten.

---

## Datenschutz-Hinweis
Die Anwendung speichert Bilder auf dem Server und optional in Ihrer Nextcloud-Instanz. Informieren Sie Nutzer über Speicherort und Aufbewahrungsdauer und nutzen Sie ausschließlich gesicherte Verbindungen (HTTPS).