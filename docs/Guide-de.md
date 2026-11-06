# Anleitung

[English](Guide.md) · [Überblick](README-de.md) · [Fragen nach Themen](FAQ-de.md)

## Den passenden Ablauf wählen

**Ein Beitrag mit einem oder mehreren Dokumenten:** Unter Einstellungen → Builder PDF Presenter den Inhaltstyp aktivieren. PDFs im Dokumentbereich hinzufügen und den Block mit „Diesem Beitrag zugeordnete Dokumente“ einfügen. Eine Zuordnung fügt die PDF nicht automatisch in andere Beiträge ein.

**Ein eigenständiger Block:** „Eigene PDF-Sammlung“ wählen und Dateien auswählen oder hochladen. Diese Zuordnungen gehören zum Block. Dokumente dort sortieren und Anzeigetitel festlegen. Das Entfernen eines Eintrags aus der Sammlung löscht nicht die Mediathek-Datei.

**Ein Archiv über mehrere Beiträge:** „Dokumentbeiträge abfragen“ wählen, Inhaltstyp und erstes oder alle PDFs auswählen. Leere Zuordnungen belegen keine Ergebnisplätze. Sammlungs-ID und Seitengröße verbinden Filter und Seitennavigation.

## Einstellungen und Speichern

Die linke Navigation springt zu Inhalt/Pflege, Karten, Buttons, Reader, Inhaltstyp-Ausnahmen, Wasserzeichen und Vorschau. Alles bleibt ein Formular; ein Bereichswechsel behält die Eingaben. Auf kleinen Bildschirmen erscheint die Navigation oben.

Die feste untere Leiste zeigt gespeicherte oder ungespeicherte Änderungen. Ein Punkt neben „Änderungen speichern“ kennzeichnet ebenfalls offene Eingaben. Speichern erfolgt ohne Seitenwechsel; bei einem Fehler bleiben die Felder für einen erneuten Versuch erhalten. Beim Verlassen mit offenen Änderungen warnt der Browser, soweit unterstützt. Ohne JavaScript bleibt das normale WordPress-Formular nutzbar.

Die Vererbung läuft von eingebauten Vorgaben über globale und Inhaltstyp-Vorgaben zu Beitrag und Dokument/Ausgabe. „Übernehmen“ verwendet die vorherige Ebene. Sammlungslimits und Wasserzeichenrichtlinie bleiben administrative Vorgaben. Ein niedrigeres Limit entfernt keine vorhandenen Dokumente.

## Block-Editor gestalten

Die Leiste „PDF Presenter · Block“ ist nur für die Bedienung im Editor da, keine Seitenüberschrift. „Bearbeiten“ öffnet die Dokumentpflege, „Vorschau“ zeigt die Karten. „Reader testen“ öffnet einen ausdrücklichen Testlink. Block-Stile gestalten die Ausgabe, nicht die Werkzeugleiste. Das Theme bestimmt mit, welche nativen Block-Editor-Stilbereiche angeboten werden.

Titelbild-Karten, kompakte Karten oder Dokumentliste verwenden. Einheitliche Rahmen oder Originalproportionen wählen. Titel und Dateiangaben sollen Dokumente unterscheidbar machen. Sehr lange Buttontexte in schmalen Spalten vermeiden.

## Bricks

**PDF-Dokument:** Im nativen Beitrags-Query-Loop einsetzen oder eine PDF ausdrücklich auswählen. Ohne Datei gilt das erste zugeordnete PDF des aktuellen/gewählten Quellbeitrags. Der Hinweis im Builder erklärt die Quelle. Das Element ist keine Dateikopie.

**PDF-Sammlung:** Alle zugeordneten Dokumente eines Quellbeitrags anzeigen. Die inneren Spalten steuert das Plugin. Alternativ eine Plugin-Abfrage über Inhaltstypen wählen. Dafür sind die Plugin-Filter vorgesehen.

Für gleich hohe Abfragekarten die Kinder des äußeren Rasters strecken und die PDF-Elemente im Schleifen-Container mitstrecken lassen. Die native Plugin-Hülle füllt diese verfügbare Höhe; Buttons bleiben unten. Keine feste Pixelhöhe nötig. Abweichende eigene Elternausrichtungen können das Ergebnis weiterhin verändern.

Dynamic Tags: `{bpp_count}`, `{bpp_id}`, `{bpp_title}`, `{bpp_url}`, `{bpp_reader_url}`, `{bpp_cover}`. Außer der Anzahl beziehen sie sich auf das erste gültige Dokument. Eine Bedingung wie `{bpp_count} > 0` am PDF-Element innerhalb der Beitragsschleife setzen. Native Bricks-Stile, Klassen und Component-Eigenschaftsverbindungen bleiben nutzbar. Ohne Bricks pausiert nur diese Integration; der Kern behält Zuordnungen.

## Shortcodes und Classic Editor

Unter Einstellungen → Builder PDF Presenter → PDFs einbinden stehen kopierbare Einbindungsbeispiele. Einen Shortcode-Block oder den Classic Editor verwenden. Die [eigene Shortcode-Referenz](Shortcodes-de.md) erklärt alle Parameter und Einsatzfälle.

```
[builder_pdf_presenter]
[builder_pdf_presenter post_id="123"]
[builder_pdf_presenter pdf_id="456" mode="tab" show_title="0"]
```

IDs sind Beitrags-/Anhang-IDs der eigenen Website. Standardmäßig gilt der aktuelle Beitrag. Öffnungsarten: `modal`, `tab` (lokaler Reader in neuem Tab), `file` (Original-PDF). Sichtbarkeit verwendet `1`/`0`; Texte lassen sich mit `read_label` und `download_label` überschreiben.

## Reader und Wasserzeichen

Administratoren wählen Navigation, Zoom, Suche auf aktueller Seite, Vollbild und Dateilinks. Die Suche betrifft die gerenderte Seite, nicht das gesamte Archiv. Die Seitenerinnerung gilt während der Browsersitzung. Der Original-PDF-Link bleibt bei Reader-Problemen als Ausweg.

Unter Wasserzeichen die zentrale Vorlage aktivieren, Text/Logo, Position, Deckkraft und Größe festlegen. Redakteure können sie nur bei ausdrücklich erlaubten Ausnahmen je Dokument ausschalten. Kein PDF-Bearbeitungs- oder Schutzfeature: Original, Downloads und außerhalb des Readers geöffnete Dateien werden nicht gestempelt.

## Migration und Wiederherstellung

Werkzeuge → PDF-Migration erscheint bei erkanntem Quellplugin, passenden Inhalten oder Wiederherstellungssicherungen. PDF-Poster-/PDF-Embedder-Quellen prüfen, ausdrücklich migrieren und Bericht ansehen. Das alte Plugin erst nach Prüfung der neuen Ausgabe entfernen. Die [Migrationsanleitung](Migration-de.md) erklärt unterstützte Formate, Grenzen, Ergebnisberichte und Wiederherstellung. Normale Website-Sicherungen beibehalten.

## Fehlerhilfe und Daten

Werkzeuge → Website-Zustand informiert über lokale Reader-Dateien und Server-PDF-Vorschauen. Ohne Konvertierungsunterstützung lassen sich eigene Titelbilder verwenden. Die Befehlspalette bietet passende Navigationszugriffe nach Berechtigung, keine direkt ausgeführten Importe.

Daten liegen in Mediathek, Beitragsmetadaten und websitebezogenen Optionen. Deaktivierung/Deinstallation erhalten Dokumente und Einstellungen standardmäßig. Es gibt keine Leserprofile. Optionale deckerweb-Library-/Updater-Anfragen versorgen Kataloge/Updates, kein PDF-Hosting; die enthaltene Komponenten-Dokumentation und Bedienelemente erläutern dies.

[Anwendungsbeispiele](Examples-de.md) · [Fragen nach Themen](FAQ-de.md)

## Sortierung, Suche und Filter

Sammlungen behalten zunächst ihre gespeicherte Reihenfolge. „Reihenfolge auf Sammlung anwenden“ sortiert die Einträge einmalig; anschließend den Beitrag speichern. „Ausgabesortierung“ im Block oder Bricks-Element sortiert diese Darstellung dauerhaft, ohne die redaktionelle Reihenfolge zu ändern. Besuchersortierung gilt nur für die aktuelle URL.

Für ein Archiv die Quelle „Dokumentbeiträge abfragen“ wählen, Inhaltstyp angeben und erstes oder alle PDFs je Beitrag auswählen. Veröffentlichungsdatum, Beitragstitel und menu_order beziehen sich auf Beiträge; Upload-Datum auf die PDF-Mediathekdatei. menu_order ist nur sinnvoll, wenn Werte an den Beiträgen gepflegt sind.

Für Filter eine eindeutige Sammlungs-ID wie `newsletters` vergeben. Im Block bzw. Bricks-Element „PDF-Filter und Suche“ dieselbe Ziel-ID eintragen. Die beiden Filter-Patterns stellen passende Kombinationen bereit, auch mit Sidebar. Suche, Sortierung und Größe werden über „Filter anwenden“ vor der Seitennavigation auf alle zugänglichen Dokumente angewendet. Keine PDF-Volltextsuche. Bekannte Dateigrößen kommen aus WordPress-Metadaten; unbekannte Größen bleiben unbekannt. Keine PDF-Analyse beim Seitenaufruf.

Native Bricks-Beitragsschleifen verwenden weiterhin native Bricks-Abfragen und -Filter. Das zusätzliche Filterelement ist für Plugin-Sammlungen gedacht, nicht für fremde Post Loops.

## Komfort und übertragbare Vorgaben

Entfernen löscht nur die Zuordnung; „Entfernen rückgängig“ stellt sie vor dem Speichern wieder her. Neue, zwischenzeitlich hinzugefügte Einträge bleiben erhalten. Ersetzen ändert nur diese Zuordnung, nicht eine mehrfach verwendete Datei. Die angegebene Mehrfachverwendung zählt Plugin-Zuordnungen in bearbeitbaren Quellbeiträgen, keine beliebigen fremden Builder-Einbettungen.

Unter „PDFs einbinden“ stehen kopierbare Beispiele und Links. Unter „Vorgaben übertragen“ gespeicherte Einstellungen exportieren oder JSON einfügen und Quell-Inhaltstypen ausdrücklich zuordnen. Der Import ersetzt die Plugin-Vorgaben, nicht Dokumente. Keine PDF-Dateien, Zuordnungen oder Medien-IDs werden übertragen. Wasserzeichen bleiben nach Import aus, bis Text und lokales Logo geprüft sind. Vor Änderungen an der Einstellungsseite zuerst speichern, wenn diese mit exportiert werden sollen.




## Reader-Fehlerhilfe: MIME-Typen für .mjs

PDF.js verwendet JavaScript-Module. Liefert der Server `pdf.mjs` oder `pdf.worker.mjs` als `text/plain` oder `application/octet-stream`, kann der Browser sie mit einem Fehler zur strikten MIME-Typ-Prüfung ablehnen. Das betrifft die Hosting-Konfiguration; andere Reader-Fehler können andere Ursachen haben. In den Netzwerkwerkzeugen des Browsers den tatsächlichen Antwortheader `Content-Type` beider Dateien unter `wp-content/plugins/builder-pdf-presenter/vendor/pdfjs/build/` prüfen. Erforderlich ist ein JavaScript-MIME-Typ wie `text/javascript` oder `application/javascript`. HTTP 200 allein bestätigt das nicht.

### Apache

Im betroffenen Virtual Host oder in `.htaccess`, sofern der Hoster `AddType` erlaubt (`AllowOverride FileInfo`), ergänzen:

```apache
AddType application/javascript .mjs
```

Vorhandene Regeln erhalten und die Zeile außerhalb der automatisch erzeugten WordPress-Markierungen einfügen. Sind `.htaccess`-Anpassungen nicht erlaubt, den Hoster ansprechen. Siehe [Apache-Dokumentation zu AddType](https://httpd.apache.org/docs/current/mod/mod_mime.html#addtype).

### nginx

Die Serveradministration ergänzt `mjs` beim JavaScript-Eintrag der wirksamen MIME-Tabelle, häufig `mime.types`. Aus einem bestehenden Eintrag `application/javascript js;` wird beispielsweise:

```nginx
application/javascript js mjs;
```

Verwendet der vorhandene Eintrag `text/javascript`, diesen Typ beibehalten und `mjs` ergänzen. Alle anderen Zuordnungen erhalten: Die vollständige `types`-Tabelle nicht durch diese einzelne Zeile ersetzen und `js` nicht doppelt zuordnen. nginx liest keine `.htaccess`. Vor dem Neuladen über die übliche Serververwaltung die Konfiguration mit `nginx -t` prüfen. Siehe [nginx-Dokumentation zu types](https://nginx.org/en/docs/http/ngx_http_core_module.html#types).

### Hosting-Oberflächen wie Plesk

Bei der betroffenen Domain **Apache und nginx → MIME-Typen** öffnen, einen eigenen Wert wählen und `application/javascript .mjs` ergänzen. Bestehende Einträge erhalten und die Änderung übernehmen. Die Aktivierung kann einige Minuten dauern. Bezeichnungen und Verfügbarkeit hängen vom Hoster ab. Liefert nginx die Dateien direkt aus, muss auch dessen wirksame MIME-Zuordnung stimmen. Siehe [Plesk-Anleitung für MIME-Typen](https://support.plesk.com/hc/en-us/articles/12377348720023-How-to-configure-MIME-types-for-a-domain-in-Plesk).

### Prüfen und Downloads weiter anbieten

Nach der Änderung betroffene Browser-/Proxy-Caches aktualisieren, beide Modulantworten erneut prüfen und den Reader öffnen. Das Plugin ändert die Serverkonfiguration nicht automatisch.

Ist die Einstellung nicht änderbar, den **Download-Button aktivieren** und den **Lesen-Button gegebenenfalls ausblenden**, bis der Reader funktioniert. Ein zugängliches Original-PDF lässt sich weiterhin ohne PDF.js herunterladen. Alternativ kann das Original-PDF direkt in einem neuen Tab geöffnet werden; Browser und Server bestimmen, ob es angezeigt oder heruntergeladen wird. Vorhandene Zugriffsbeschränkungen gelten weiterhin. Für diesen Ausweichweg ist kein externer Google- oder Adobe-Viewer erforderlich.
