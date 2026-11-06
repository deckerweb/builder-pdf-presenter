# Shortcodes

[English](Shortcodes) · [Anleitung](Anleitung)

Shortcodes funktionieren im Classic Editor, im Shortcode-Block und in geeigneten Builder-Elementen. IDs in den Beispielen sind Platzhalter aus der eigenen Mediathek bzw. Website. Ein sichtbarer Quellbeitrag ist erforderlich; Shortcodes umgehen keine Beitragsberechtigungen oder Passwörter.

## [builder_pdf_presenter]

| Parameter | Bedeutung, Werte und Vorgabe | Beispiel |
|---|---|---|
| `post_id` | ID des Quellbeitrags; standardmäßig aktueller Beitrag. | `post_id="123"` |
| `pdf_id` | Explizite PDF-Mediathek-ID; standardmäßig keine. Hat Vorrang vor der Sammlung. | `pdf_id="456"` |
| `source` | post (Standard) oder posts (Archiv). custom verwendet ein Block-Array und ist kein Shortcode-Format. | `source="posts"` |
| `post_type` | Auswählbarer Inhaltstyp-Slug bei source="posts"; Standard post. | `post_type="programm"` |
| `collection_id` | Eindeutiger Kleinbuchstaben-Name, maximal 64 Zeichen, mit Ziffern, Bindestrich oder Unterstrich. Verbindet Filter; ohne Angabe automatisch erzeugt. | `collection_id="newsletters"` |
| `per_post` | first (Standard): erstes verfügbares PDF je Quellbeitrag; all: alle zugeordneten PDFs. | `per_post="all"` |
| `limit` | Bisheriger Ersatzwert für Seitengröße, 1–500; Shortcode-Standard 500, Block-Standard 12. Leere Beiträge belegen keine Plätze. | `limit="12"` |
| `page_size` | PDFs je Seite, 1–500; ohne Angabe/0 gilt limit oder 500. Filterung erfolgt vor Paginierung. | `page_size="24"` |
| `orderby` | manual, title, media_title, filename, upload_date, size, date, post_title, menu_order. Standard manual für Sammlungen, date für Beitragsarchive. | `orderby="title"` |
| `order` | ASC oder DESC. Standard DESC bei Veröffentlichungsdatum, sonst ASC. Gleiche Werte folgen stabil der gespeicherten Quellreihenfolge. | `order="ASC"` |
| `show_count` | Trefferzahl anzeigen: `1` (Standard), `0` ausblenden. | `show_count="0"` |
| `search_fields` | Kommagetrennt title,post_title,media_title,filename (Standard: alle). Tatsächlicher Quellbeitrag statt Datei-Elterneintrag; kein PDF-Text. | `search_fields="title,filename"` |

### Anzeige und Reader: vererbte Vorgaben

Ohne Wert gilt: Plugin-Vorgabe → Website → Inhaltstyp → Beitrag → Dokument → ausdrücklicher Ausgabeparameter. Leere Werte übernehmen. Boolesche Werte: `1`/`true` oder `0`/`false`. Die folgende Tabelle nennt die eingebauten Vorgaben; gespeicherte Website-Vorgaben können davon abweichen.

| Parameter | Zweck / Werte | Eingebaute Vorgabe | Beispiel |
|---|---|---|---|
| `design` | Kartendesign; `cover`, `compact`, `list` | `cover` | `design="cover"` |
| `columns` | Spalten; `1`, `2`, `3`, `4` | `3` | `columns="1"` |
| `mode` | Dokument öffnen; `modal`, `tab`, `file` | `modal` | `mode="modal"` |
| `ratio` | Vorschaurahmen; `a4`, `portrait`, `square`, `landscape`, `auto` | `a4` | `ratio="a4"` |
| `show_title` | Titel anzeigen; `1`, `0` | `1` | `show_title="0"` |
| `show_read` | Lesen-Button anzeigen; `1`, `0` | `1` | `show_read="0"` |
| `show_download` | Download-Button anzeigen; `1`, `0` | `1` | `show_download="0"` |
| `cover_link` | Vorschau verlinken; `1`, `0` | `1` | `cover_link="0"` |
| `show_size` | Dateigröße anzeigen; `1`, `0` | `1` | `show_size="0"` |
| `read_label` | Lesen-Beschriftung; Text, maximal 100 Zeichen | `Read / Lesen` | `read_label="Lesen"` |
| `download_label` | Download-Beschriftung; Text, maximal 100 Zeichen | `Download / Herunterladen` | `download_label="Herunterladen"` |
| `page_navigation` | Reader: Seitennavigation; `1`, `0` | `1` | `page_navigation="0"` |
| `zoom` | Reader: Zoom; `1`, `0` | `1` | `zoom="0"` |
| `search` | Reader: Textsuche nur auf aktueller PDF-Seite; `1`, `0` | `0` | `search="0"` |
| `print` | Reader: Original zum Drucken öffnen; `1`, `0` | `0` | `print="0"` |
| `fullscreen` | Reader: Vollbild; `1`, `0` | `1` | `fullscreen="0"` |
| `reader_download` | Reader: Download-Link; `1`, `0` | `1` | `reader_download="0"` |
| `reader_watermark` | Reader-Wasserzeichen (zentrale Admin-Regel bleibt maßgeblich); `1`, `0` | `1` | `reader_watermark="0"` |
| `remember_page` | Reader: Seite während dieses Besuchs merken; `1`, `0` | `1` | `remember_page="0"` |
| `initial_zoom` | Reader: anfänglicher Zoom; `fit`, `1`, `1.25`, `1.5` | `fit` | `initial_zoom="fit"` |

## [builder_pdf_filters]

| Parameter | Bedeutung / Vorgabe | Beispiel |
|---|---|---|
| `target` | Passende collection_id erforderlich; eindeutig auf der Seite. | `target="newsletters"` |
| `layout` | horizontal (Standard) oder vertical. | `layout="vertical"` |
| `show_search` | 1 (Standard); 0 blendet Suchfeld aus. | `show_search="0"` |
| `show_sort` | 1 (Standard); 0 blendet Besuchersortierung aus. | `show_sort="0"` |
| `show_size_filter` | 1 (Standard); 0 blendet Größenfilter aus. | `show_size_filter="0"` |
| `show_reset` | 1 (Standard); 0 blendet den Link zum Zurücksetzen aus. | `show_reset="0"` |

Die Suche erfasst nur konfigurierte Titel und Dateinamen, keinen PDF-Inhalt. „Anwenden“ lädt die Seite mit GET-Parametern neu; teilbare URLs, keine Speicherung von Besuchersortierungen und keine Suchstatistik. Größenbereiche: unter 1 MiB, 1 bis unter 5 MiB, ab 5 MiB und unbekannt; 1 MiB = 1.048.576 Byte. Unbekannte Größen werden nicht aus PDFs nachgeladen und beim Sortieren zuletzt angezeigt.

## Vollständige Beispiele

### Aktueller Beitrag

```text
[builder_pdf_presenter]
```

### Anderer Quellbeitrag

```text
[builder_pdf_presenter post_id="123"]
```

### Einzelne PDF

```text
[builder_pdf_presenter post_id="123" pdf_id="456" mode="tab"]
```

### Alle PDFs eines Beitrags, sortiert

```text
[builder_pdf_presenter post_id="123" collection_id="minutes" orderby="title" order="ASC" page_size="12"]
```

### Archiv mit Filterleiste

```text
[builder_pdf_filters target="newsletters"]
[builder_pdf_presenter source="posts" post_type="post" per_post="all" collection_id="newsletters" page_size="12" columns="3"]
```

### Abweichende Aktionen und Reader

```text
[builder_pdf_presenter mode="modal" show_download="0" reader_download="0" zoom="1" search="1" read_label="Jetzt lesen"]
```

Jede Sammlung benötigt einen eigenen Namen, wenn sie getrennt gefiltert werden soll. Die Filterleiste darf mit Zwischeninhalten darüber oder in einer anderen Spalte stehen. Kopierte Patterns erhalten einen vorbereiteten Namen; bei mehreren Kopien auf derselben Seite die zusammengehörigen Namen jeweils ändern. Für native Bricks-Beitragsschleifen die nativen Bricks-Filter verwenden.
