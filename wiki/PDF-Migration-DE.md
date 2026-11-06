# PDF-Migration

[English](PDF-migration) · [Überblick](Ueberblick)

## Entscheiden, ob der Wechsel passt

Unter Werkzeuge → PDF-Migration unterstützte Einbettungen prüfen und ausgewählte Beiträge ausdrücklich umstellen. Unterstützt werden PDF-Poster-Blöcke und PDF-Embedder-Blöcke/Shortcodes im normalen Beitragsinhalt. Vorhandene lokale Mediathek-PDFs werden genutzt, nicht hochgeladen, kopiert oder gelöscht. Für die Erkennung muss das Quellplugin nicht aktiv bleiben. Damit wiederhergestellte ursprüngliche Einbettungen funktionieren, muss es wieder verfügbar sein.

Auch PDF Embedder verwendet lokales PDF.js. Der Wechsel ist sinnvoll für Vorschaukarten, Sammlungen und Archive mit Lesen-/Download-Aktionen. Die Darstellung ändert sich: Aus Inline-Viewern werden Titelbild-Karten mit Lesen-Aktion gemäß den aktuellen Plugin-Vorgaben, etwa dem Modal-Reader. Breite, Höhe, Toolbarposition, Hover-Verhalten, Startseite und anfänglicher Zoom werden nicht übernommen. Ergebnis vor Entfernen des Quellplugins prüfen.

## Unterstützte und übersprungene Inhalte

- PDF Poster: unterstützte Blöcke; dessen Shortcodes weiterhin manuell prüfen.
- PDF Embedder: Blöcke `pdfemb/pdf-embedder-viewer` und echte Shortcodes `[pdf-embedder url="…"]`. Der Adapter wurde anhand der kostenlosen Version 5.0.2 entwickelt; andere Formate müssen geprüft werden.
- Die PDF muss eindeutig einer normalen lokalen Mediathek-PDF mit vorhandener Datei entsprechen. Widersprüchliche ID/URL-Angaben werden nicht erraten.
- Externe URLs, fehlende/mehrdeutige Anhänge und geschützte `securepdfs`-Dokumente werden übersprungen. Keine entfernten PDFs werden geladen, kein Schutz wird entfernt.
- HTML-/Codebeispiele, beliebige Builder-Metadaten, Templates und Premium-Sonderinhalte werden nicht automatisch umgestellt. Maskierte Shortcode-Beispiele bleiben erhalten.

Eine nicht unterstützte PDF-Referenz lässt den ganzen Beitrag unverändert. Mehrere unabhängige Beiträge eines Durchlaufs können unterschiedliche Ergebnisse haben. Der Bericht erklärt jeden Ausgang.

## Prüfen, umstellen und kontrollieren

1. Werkzeuge → PDF-Migration öffnen. Nach Änderungen an Quellinhalten „Erneut prüfen“ verwenden.
2. Gefundene Beiträge durchsehen. Auswählbare Einträge haben eindeutig zugeordnete lokale PDFs; andere zeigen den Grund. Aktivierung und Seitenaufruf starten keine Migration.
3. Bis zu 50 Beiträge auswählen und ausdrücklich umstellen. Sammlungslimits und Berechtigungen gelten weiterhin.
4. Ergebnisbericht, Quellseite und zugehörige Archive prüfen. Mehrere Einbettungen behalten Position und Reihenfolge; jede zeigt ihr eigenes Dokument statt wiederholt die ganze Beitragssammlung. Eindeutige Dokumente werden zusätzlich dem Beitrag für Archive zugeordnet. Vorhandene Zuordnungen bleiben erhalten.
5. Vor Deaktivierung/Entfernung des alten Plugins alle Quellseiten, Templates, Builder-Integrationen und übersprungenen Fälle kontrollieren. Das Werkzeug prüft Beitragsinhalte, nicht jeden möglichen Speicherort.

Beitragsstatus, Passwörter und weitere Beitragseinstellungen bleiben erhalten. Mediendateien behalten ihre Zugriffsregeln. Die Migration schafft keinen geschützten Speicher und keinen Kopierschutz.

## Wiederherstellung und spätere Änderungen

Das Werkzeug sichert den exakten ursprünglichen Beitragsinhalt und die vorherigen PDF-Zuordnungen. Unter Wiederherstellungssicherungen einzelne Beiträge zurücksetzen. Spätere Änderungen führen zu einem Konflikt und erfordern ausdrückliche Bestätigung zum Überschreiben. Die Wiederherstellung ersetzt den gesicherten Inhalt und die Sammlung, bei bestätigtem Überschreiben einschließlich späterer Änderungen; Mediendateien werden nicht gelöscht. Die letzten 20 Ergebnisberichte bleiben abrufbar. Allgemeine Website-Sicherungen bleiben sinnvoll.
