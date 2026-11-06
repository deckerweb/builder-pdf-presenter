# Fragen nach Themen

## Einstieg

### Ist das ein Flipbook oder ein Plugin mit Magazin-Effekten?
Nein. Im Mittelpunkt stehen einfache Dokumentpflege, schnelle Titelbild-Übersichten und komfortables Lesen. Blättereffekte gehören nicht dazu.

### Gibt es Pro-Version, Werbung oder Cloud-Synchronisierung?
Nein. Es gibt keine gesperrten Bezahlfunktionen, Werbung oder Cloud-Synchronisierung. Die Dokumente werden in der WordPress-Mediathek verwaltet.

### Brauche ich Bricks oder Meta Box?
Nein. Kern, Block-Editor-Block und Shortcodes arbeiten eigenständig. Native Bricks-Elemente stehen bei aktivem Bricks bereit. Ohne Bricks bleiben Dokumentzuordnungen und die anderen Ausgabewege erhalten; ein Bricks-Layout benötigt zum Rendern weiterhin Bricks.

## Dokumente im Alltag

### Was ist eine Sammlung?
Die PDFs, die einem Beitrag, einer Seite oder einem eigenständigen PDF-Block zugeordnet sind. Sie lassen sich sortieren und als Raster oder Liste darstellen. Eine Sammlung kann auch nur eine PDF enthalten.

### Wie entsteht ein Archiv aus mehreren Beiträgen?
Bei einem Heft je Beitrag: im Block „Dokumentbeiträge abfragen“ wählen oder einen nativen WordPress-/Bricks-Query-Loop verwenden. Die direkte Blockabfrage zeigt wahlweise das erste oder alle PDFs je Beitrag und filtert Metadaten vor der Paginierung. Im WordPress-Query-Loop verwendet der PDF-Block den jeweiligen Schleifenbeitrag als Quelle. „PDF-Dokument“ in Bricks zeigt dessen erstes PDF; „PDF-Sammlung“ zeigt eine Beitragssammlung oder ein Plugin-Archiv.

### Warum zeigt ein neues Bricks-Element schon ein PDF?
Ohne ausdrücklich ausgewählte Datei verwendet „PDF-Dokument“ das erste zugeordnete PDF des aktuellen Quellbeitrags beziehungsweise Schleifenbeitrags. Dies ist echter Inhalt, keine Beispieldatei. Das Einfügen des Elements legt keine zusätzliche Dokumentzuordnung an.

### Gehört „PDF Presenter · Block“ zu meiner Seite?
Nein. Die kompakte Kennzeichnung und „Bearbeiten/Vorschau“ sind Editor-Bedienelemente und erscheinen nicht auf der Website. Der eigentliche Dokumenttitel steht in der Karte darunter.

### Wie funktionieren Vorgaben und Ausnahmen?
Eingebaute Vorgaben → globale Vorgaben → Vorgaben je Inhaltstyp → Beitragseinstellungen → einzelne Dokument-/Ausgabeeinstellungen. „Übernehmen“ lässt den Wert aus der vorigen Ebene gelten. Zentrale Sammlungslimits und die Wasserzeichenrichtlinie bleiben unter Kontrolle des Administrators.

### Warum fehlt ein Titelbild?
WordPress benötigt eine geeignete PDF-Vorschau; alternativ lässt sich ein eigenes Bild auswählen. Automatische Erzeugung hängt von Bildverarbeitung und PDF-Unterstützung des Servers ab. „Fehlende Vorschauen erzeugen“ verwenden oder ein Titelbild manuell zuordnen. Bei Besucheraufrufen erzeugt das Plugin keine PDF-Bilder.

## Reader, Daten und Zugriff

### Warum PDF.js?
PDF.js ist eine Open-Source-PDF-Technik, die auch in Firefox eingesetzt wird. Die Reader-Skripte werden lokal ausgeliefert. Adobe und Google Docs werden für die Darstellung nicht benötigt und auch nicht als automatischer Ersatzdienst genutzt.

### Was passiert bei einem Reader-Ladefehler?
Eine verständliche Meldung verweist auf den Link zur Original-PDF. Die Zugriffsregeln der Datei gelten weiterhin. Ob der Browser das Original öffnet oder herunterlädt, hängt von Browser und Server-Headern ab.

### Sind Dokumente geschützt, wenn „Herunterladen“ ausgeblendet ist?
Nein. Ausgeblendete Bedienelemente sind weder Zugriffsschutz noch Kopierschutz. Original-URLs und Vorschaubilder behalten die bestehenden Server-Berechtigungen. Das Plugin stellt keinen geschützten Dokumentenspeicher und kein Mitgliedersystem bereit.

### Verändert ein Wasserzeichen die PDF?
Nein. Es wird im Reader darübergelegt. Original, Downloads und außerhalb des Readers geöffnete Dateien bleiben unverändert. Administratoren geben die Vorlage vor und können Ein-/Aus-Ausnahmen je Dokument erlauben. Redakteure können keine beliebigen Wasserzeichenlogos einsetzen.

### Werden Leser verfolgt oder PDFs in eine Cloud übertragen?
Das Plugin bietet keine Leserstatistik, Profilbildung oder Cloud-Synchronisierung. Der Reader verwendet lokale Dateien. Optionale deckerweb-Update-/Library-Funktionen können die separat dokumentierten Katalog-/Updateanfragen senden; dabei werden keine PDFs hochgeladen.

### Was speichert der Reader im Browser?
Die optionale Seiten-Erinnerung verwendet den Sitzungsspeicher, um die aktuelle Seite je Dokument während des Besuchs zu merken. Dies dient der Bedienung, nicht einer Statistik. Mit dem Ende der Sitzung verschwindet die Erinnerung.

## Verwaltung und Fehlerhilfe

### Was prüft „Website-Zustand“?
Lokale Reader-Dateien und die von WordPress gemeldete PDF-Bildverarbeitungsunterstützung. Es werden weder Dokumente hochgeladen noch alle Mediendateien durchsucht. Die Prüfung garantiert keine Konvertierung jeder PDF. Fehlt die Vorschauerzeugung, bleiben eigene Titelbilder nutzbar.

### Was machen die Schnellzugriffe in der Befehlspalette?
Sie öffnen Anleitung, Einstellungen oder die PDF-Poster-Migration, abhängig von Berechtigung und Verfügbarkeit. Sie führen keine Migration aus und speichern keine Änderungen.

### Wie funktioniert die Migration?
Unter Werkzeuge → PDF-Migration die gefundenen Beiträge prüfen und ausdrücklich migrieren. Vorher-/Nachher-Sicherungen und Ergebnisberichte ermöglichen eine Wiederherstellung je Beitrag. Spätere Änderungen können einen Konflikt auslösen und werden nicht still überschrieben. Das Werkzeug erscheint bei erkanntem PDF Poster beziehungsweise passenden Einbettungen/Sicherungen; vorhandene Mediathek-Dateien werden genutzt.

### Was gilt für Multisite und Deinstallation?
Einstellungen, Zuordnungen und Migrationsberichte gelten je Website. Deaktivierung und Deinstallation erhalten Dokumente und Einstellungen standardmäßig. Eingebundene Komponenten verwalten ihre gemeinsam benötigten temporären Daten gesondert. Normale Sicherungen von Website und Mediathek bleiben sinnvoll.

### Warum kann das Theme die Darstellung trotzdem beeinflussen?
Das Plugin bietet begrenzte, lesbare Standardstile. Block-Editor-Stiloptionen und native Bricks-Stile passen die jeweilige Ausgabe an. Sehr allgemeine oder wichtige Theme-Regeln können weiterhin eingreifen; beliebige fremde CSS-Regeln lassen sich nicht vollständig ausschließen.

## Suche und übertragbare Vorgaben

### Durchsucht die Sammlungssuche PDF-Inhalte?
Nein. Sie durchsucht die ausgewählten Anzeigetitel, tatsächlichen Quellbeitragstitel, Mediathektitel und Dateinamen. „Filter anwenden“ erfasst die gesamte zugängliche Sammlung vor der Seitennavigation. Das ist unabhängig von der optionalen Suche auf der aktuell angezeigten PDF-Seite im Reader. Such-URLs lassen sich teilen; das Plugin führt keine Suchstatistik.

### Warum ist eine PDF-Dateigröße unbekannt?
In älteren WordPress-Anhangsmetadaten kann die Byte-Größe fehlen. Das Plugin lädt oder analysiert dafür während eines Besucheraufrufs keine Datei. Unbekannte Größen haben einen eigenen Filter und stehen bei beiden Sortierrichtungen zuletzt.

### Überträgt der Vorgabenimport Dokumente auf eine andere Website?
Nein. Übertragen werden nur gespeicherte Anzeigevorgaben. Inhaltstypen müssen ausdrücklich zugeordnet werden; doppelte Zielzuordnungen werden abgewiesen. Medien-IDs und PDF-Zuordnungen werden nicht kopiert. Wasserzeichen sind nach dem Import aus und ihr Logo wird aus den importierten Vorgaben entfernt, bis der Administrator die lokale Vorlage geprüft hat. Der Import ersetzt Plugin-Vorgaben, keine Dokumente oder Wiederherstellungssicherungen der Migration.


[PDF-Poster- und PDF-Embedder-Migration: Ablauf, Grenzen und Wiederherstellung](Migration-de.md)

### Warum meldet der Reader einen MIME-Typ-Fehler für .mjs-Dateien?

Der Server liefert die PDF.js-Module möglicherweise als einfachen Text statt als JavaScript aus. Die MIME-Zuordnung für `.mjs` lässt sich in Apache, nginx oder der Hosting-Oberfläche korrigieren. Die [MIME-Fehlerhilfe](Guide-de.md#reader-fehlerhilfe-mime-typen-für-mjs) enthält Beispiele und Prüfschritte. Ist keine Lösung möglich, den Download-Button aktiviert lassen und Lesen gegebenenfalls ausblenden: Zugängliche Original-PDFs bleiben ohne Reader verfügbar.

