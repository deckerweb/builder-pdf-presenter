# Builder PDF Presenter

![Builder PDF Presenter](https://raw.githubusercontent.com/wiki/deckerweb/builder-pdf-presenter/images/branding/banner-github-de.png)

[English](Overview)

## Kurzvorstellung

PDFs mit wenig Aufwand veröffentlichen: leichte Titelbild-Karten zum Stöbern und ein komfortabler Reader, der bei Bedarf öffnet. Für Vereinszeitschriften, Protokolle, Gemeindebriefe und Dokumentarchive in beliebigen geeigneten Inhaltstypen.

**Deine Daten in deinem WordPress. Punkt.** Kostenlos, ohne Werbung, Lesertracking oder Pro-Version.

Version **1.0.1** · WordPress **6.9+** · PHP **8.3+**

[Einstieg](#einstieg) · [Anleitung](Anleitung) · [Fragen nach Themen](Fragen) · [Anwendungsbeispiele](Beispiele)

## Inhalt

- [Auf einen Blick](#auf-einen-blick)
- [Einstieg](#einstieg)
- [Feature Highlights](#feature-highlights)
- [Häufige Fragen](#häufige-fragen)
- [Änderungsverlauf](#änderungsverlauf)
- [Zum Projekt](#zum-projekt)
- [Hilfe, Sicherheit und Unterstützung](#hilfe-sicherheit-und-unterstützung)

## Auf einen Blick

- Einzelne PDF oder sortierbare Sammlung je Beitrag, Seite oder eigenständigem Block.
- Drei Kartendesigns: Titelbild, kompakt und Dokumentliste; responsive Raster.
- Lokaler PDF.js-Reader im Modal oder neuen Tab; direkter Dateiaufruf ebenfalls möglich.
- Einstellbare Lesen-/Download-Buttons, Beschriftungen, Titelbilder und Reader-Funktionen.
- Globale Vorgaben mit Ausnahmen je Inhaltstyp, Beitrag und Dokument.
- Block-Editor-Block, acht Patterns, Shortcodes und drei native Bricks-Elemente.
- Optionales zentrales Reader-Wasserzeichen; ausdrückliche PDF-Poster-Migration mit Wiederherstellung.
- Lokale Website-Zustand-Prüfungen und berechtigungsabhängige Schnellzugriffe in der Befehlspalette.

## Einstieg

1. Plugin-ZIP unter Plugins → Plugin hinzufügen → Plugin hochladen installieren und aktivieren.
2. Einstellungen → Builder PDF Presenter öffnen. Inhaltstypen mit PDF-Verwaltung auswählen und globale Vorgaben festlegen.
3. Im Beitrag PDFs aus der Mediathek wählen oder hochladen. Reihenfolge und Anzeigetitel bei Bedarf anpassen.
4. Den Block „Builder PDF Presenter“ einfügen und zugeordnete Dokumente verwenden. Alternativ direkt im Block eine eigene Sammlung erstellen, `[builder_pdf_presenter]` oder ein natives Bricks-Element verwenden.
5. Seite ansehen und Lesen/Download testen. Änderungen vor dem Verlassen der Einstellungsseite speichern.

**PDFs einbinden:** Unter Einstellungen → Builder PDF Presenter → PDFs einbinden werden die Einbindungswege erklärt. `[builder_pdf_presenter]` funktioniert im Shortcode-Block oder Classic Editor; die [Shortcode-Referenz](Shortcodes-DE) enthält alle Parameter mit Beispielen. Alternativ stehen Block-Editor-Blöcke/Patterns und native Bricks-Elemente bereit.

**Von einem anderen PDF-Plugin wechseln:** Unter Werkzeuge → PDF-Migration unterstützte PDF-Poster-/PDF-Embedder-Inhalte prüfen und ausgewählte Beiträge umstellen. Die [Migrationsanleitung](PDF-Migration-DE) erklärt unterstützte Formate, Ergebnisberichte und Wiederherstellung.

Builder PDF Presenter unterstützt den WordPress-Block-Editor (Gutenberg): Dokumente werden mit den Blöcken und Patterns des Plugins eingebunden. Im Classic Editor erfolgt die Einbindung über Shortcodes. Ist Bricks Builder installiert und aktiv, stehen zusätzlich eigene native Elemente zur Verfügung.

Die native Bricks-Integration wurde mit Bricks 2.4.5 getestet.

![Eine PDF-Sammlung mit Titelbild-Karten, Lesen- und Download-Buttons, Metadatenfiltern und Seitennavigation.](https://raw.githubusercontent.com/wiki/deckerweb/builder-pdf-presenter/images/library-grid-de.jpg)

*Eine PDF-Sammlung mit Titelbild-Karten, Lesen- und Download-Buttons, Metadatenfiltern und Seitennavigation.*

## Feature Highlights

### Schnelle Übersicht, Reader bei Bedarf

Das Raster zeigt normale Titelbilder. Es startet nicht für jede Karte einen eigenen PDF.js-Reader. Der Reader lädt erst beim Öffnen eines Dokuments. Große Original-PDFs können weiterhin Ladezeit benötigen; die Übersicht komprimiert die Dateien nicht.

![Der lokale PDF.js-Reader öffnet sich bei Auswahl eines Dokuments im Modal.](https://raw.githubusercontent.com/wiki/deckerweb/builder-pdf-presenter/images/reader-modal-de.jpg)

*Der lokale PDF.js-Reader öffnet sich bei Auswahl eines Dokuments im Modal.*

### Einfache Dokumentpflege

Mehrere PDFs auswählen oder hochladen, sortieren, Anzeigetitel bearbeiten, Dateien ersetzen und Titelbilder zuweisen. Administratoren begrenzen die Anzahl. Ein niedrigeres Limit entfernt keine vorhandenen Einträge.

### Einheitliche Vorgaben und passende Ausnahmen

Übliche Einstellungen an einer Stelle pflegen. Inhaltstypen, Beiträge und Dokumente können übernehmbare Werte überschreiben. Die feste Speicherleiste zeigt ungespeicherte Änderungen; nach einem Speicherfehler bleiben die Eingaben erhalten.

### Block-Editor und native Bricks-Abläufe

Block-Editor-Farb-, Typografie-, Abstands- und weitere unterstützte Stiloptionen gestalten den jeweiligen Block. Die kompakte Leiste „PDF Presenter · Block“ gehört nur zum Editor. In Bricks arbeitet „PDF-Dokument“ innerhalb von Beitragsabfragen, „PDF-Sammlung“ zeigt die Dokumente eines Beitrags. Native Stile, Dynamic Tags und Conditions bleiben nutzbar.

### Zentrales Reader-Branding

Administratoren aktivieren eine gemeinsame Wasserzeichenvorlage mit Text/Logo und erlauben bei Bedarf Ein-/Aus-Ausnahmen je Dokument. Die Einblendung betrifft nur den Reader; Originale und Downloads bleiben unverändert.

### Bewusst überschaubar

Keine Flipbook-Effekte, Pro-Version, Werbung, Leserstatistik oder Cloud-Synchronisierung. Ausgeblendete Download-Buttons sind kein Kopierschutz. Dokumentdateien und Titelbilder behalten die bestehenden Zugriffsregeln des Servers.

### Sortieren und wiederfinden

Benannte Sammlungen lassen sich nach Titeln und Dateinamen durchsuchen, sortieren und nach Dateigröße filtern. Die Suche erfasst die gesamte zugängliche Sammlung vor der Seitennavigation, keine PDF-Inhalte. Fertige Patterns verbinden Raster und Filterleiste, auch in einer Sidebar.

## Häufige Fragen

### Welche Editoren werden unterstützt?
Builder PDF Presenter unterstützt den WordPress-Block-Editor (Gutenberg): Dokumente werden mit den Blöcken und Patterns des Plugins eingebunden. Im Classic Editor erfolgt die Einbindung über Shortcodes. Ist Bricks Builder installiert und aktiv, stehen zusätzlich eigene native Elemente zur Verfügung.

### Kann ein Beitrag mehrere PDFs enthalten?
Ja. Eine sortierbare Sammlung im Beitrag oder direkt im Block anlegen und als Karten oder Liste anzeigen.

### Kann ich ein Archiv über mehrere Beiträge erstellen?
Ja. Die Beitragsabfrage des Blocks oder einen nativen Query Loop verwenden. Die Plugin-Abfrage zeigt das erste oder alle PDFs je Quellbeitrag und filtert alle zugänglichen Ergebnisse vor der Paginierung.

### Warum fehlt ein Vorschaubild?
Automatische PDF-Vorschauen hängen vom Server ab. Eine fehlende Vorschau erzeugen, sofern unterstützt, oder ein eigenes Titelbild auswählen.

### Ist das ein Dateischutz- oder Mitgliedersystem?
Nein. Button-Sichtbarkeit schützt keine Datei. Vorhandene Server-Berechtigungen gelten weiterhin.

### Verändert das Wasserzeichen die Downloads?
Nein. Es erscheint ausschließlich im eingebetteten Reader.

### Gibt es eine Pro-Version oder Lesertracking?
Nein. Es gibt keine Pro-Version, kein Upselling und kein Lesertracking durch das Plugin.

[Alle Fragen und Antworten lesen →](Fragen)

## Änderungsverlauf

### 1.0.1 · 2026-11-09

- **Behoben:** deckerweb Library auf 1.3.0 und Updater auf 2.4.0 aktualisiert: normale Updates unabhängig vom Katalog, begrenzte Downloads und strengere Paketprüfung.

### 1.0.0 · 2026-11-06

- Neu: PDF-Karten mit Vorschau, Reader und Download für einzelne Dokumente und Sammlungen.
- Neu: Globale Vorgaben und individuelle Einstellungen für beliebige Inhaltstypen.
- Neu: Block-Editor, Shortcodes und native Bricks-Integration einschließlich Metadatenfiltern und Sortierung.
- Neu: Migration von PDF Poster und PDF Embedder mit Ergebnisbericht und Wiederherstellung.


## Zum Projekt

Seit Jahren brauchten Kunden aus Vereinen und Gemeinden eine einfache PDF-Veröffentlichung. Fehlende Funktionen, Upselling und umständliche Bedienung standen immer wieder im Weg. Ein Kundenprojekt im Herbst 2026 war schließlich der Anlass für meine eigene Lösung.

— David Decker · [Die Entstehungsgeschichte](Entstehung)

## Hilfe, Sicherheit und Unterstützung

[Anleitung](Anleitung) · [Fragen nach Themen](Fragen) · [Plugin-Website](https://deckerwebplugins.com/de/plugins/builder-pdf-presenter/)

Sicherheitslücken vertraulich melden; dazu die [Sicherheitsrichtlinie](Help-and-security) beachten. Keine Kundendateien, Zugangsdaten oder Exploit-Details öffentlich in Hilfeanfragen teilen.

Das Projekt unterstützen: [Ko-fi](https://ko-fi.com/deckerweb) · [Buy Me a Coffee](https://buymeacoffee.com/daveshine) · [PayPal](https://paypal.me/deckerweb).

© 2026 David Decker – DECKERWEB. Plugin-Lizenz: [GPL v2 oder höher](https://www.gnu.org/licenses/gpl-2.0.html).
PDF.js: Apache-2.0, siehe vendor/pdfjs/LICENSE. Eingebundene deckerweb-Komponenten behalten ihre Lizenzhinweise. Eigene PDF-Presenter-UI-Symbole von David Decker – DECKERWEB. Verbliebene Tabler-Hinweise: vendor/tabler/LICENSE.


Marketinggrafiken: eigene Vektorgrafiken von David Decker – DECKERWEB. Schriftzüge aus Bricolage Grotesque und Manrope unter SIL OFL 1.1; Lizenztexte unter .github/assets/licenses.
