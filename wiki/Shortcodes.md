# Shortcodes

[Deutsch](Shortcodes-DE) · [Guide](User-guide)

Use shortcodes in the Classic Editor, a Shortcode block or suitable builder elements. Example IDs are placeholders for your own posts and Media Library. A readable source post is required; shortcodes do not bypass post visibility or passwords.

## [builder_pdf_presenter]

| Parameter | Meaning, values and default | Example |
|---|---|---|
| `post_id` | Source post ID; default current post. | `post_id="123"` |
| `pdf_id` | Explicit PDF attachment ID; default none. Overrides collection source. | `pdf_id="456"` |
| `source` | post (default) or posts (archive). custom is a block-only collection array, not a shortcode format. | `source="posts"` |
| `post_type` | Selectable content type slug for source="posts"; default post. | `post_type="programm"` |
| `collection_id` | Unique lowercase name, max. 64 characters: letters, digits, hyphen, underscore. Connects filters; auto-generated when absent. | `collection_id="newsletters"` |
| `per_post` | first (default): first available PDF per source post; all: every assigned PDF. | `per_post="all"` |
| `limit` | Legacy page-size fallback, 1–500; shortcode default 500, block default 12. Empty posts do not consume slots. | `limit="12"` |
| `page_size` | PDFs per page, 1–500; absent/0 uses limit or 500. Filtered before pagination. | `page_size="24"` |
| `orderby` | manual, title, media_title, filename, upload_date, size, date, post_title, menu_order. Default manual for collections; date for post archives. | `orderby="title"` |
| `order` | ASC or DESC. Default DESC for publication date, otherwise ASC. Stable ties follow saved source order. | `order="ASC"` |
| `show_count` | Result count: `1` (default), `0` hides it. | `show_count="0"` |
| `search_fields` | Comma-separated title,post_title,media_title,filename (all by default). Actual source post, not attachment parent; no PDF text. | `search_fields="title,filename"` |

### Display and reader: inherited defaults

Absent values follow: built-in → website → content type → post → document → explicit output parameter. Empty values inherit. Booleans accept `1`/`true` or `0`/`false`. The table lists built-in defaults; saved website defaults can differ.

| Parameter | Purpose / values | Built-in default | Example |
|---|---|---|---|
| `design` | Card design; `cover`, `compact`, `list` | `cover` | `design="cover"` |
| `columns` | Collection columns; `1`, `2`, `3`, `4` | `3` | `columns="1"` |
| `mode` | Open document; `modal`, `tab`, `file` | `modal` | `mode="modal"` |
| `ratio` | Preview frame; `a4`, `portrait`, `square`, `landscape`, `auto` | `a4` | `ratio="a4"` |
| `show_title` | Show title; `1`, `0` | `1` | `show_title="0"` |
| `show_read` | Show reading button; `1`, `0` | `1` | `show_read="0"` |
| `show_download` | Show download button; `1`, `0` | `1` | `show_download="0"` |
| `cover_link` | Make preview clickable; `1`, `0` | `1` | `cover_link="0"` |
| `show_size` | Show file size; `1`, `0` | `1` | `show_size="0"` |
| `read_label` | Reading button label; Text, max. 100 characters | `Read / Lesen` | `read_label="Read"` |
| `download_label` | Download button label; Text, max. 100 characters | `Download / Herunterladen` | `download_label="Download"` |
| `page_navigation` | Reader: page navigation; `1`, `0` | `1` | `page_navigation="0"` |
| `zoom` | Reader: zoom controls; `1`, `0` | `1` | `zoom="0"` |
| `search` | Reader: search on current page; `1`, `0` | `0` | `search="0"` |
| `print` | Reader: open PDF for printing; `1`, `0` | `0` | `print="0"` |
| `fullscreen` | Reader: fullscreen; `1`, `0` | `1` | `fullscreen="0"` |
| `reader_download` | Reader: download link; `1`, `0` | `1` | `reader_download="0"` |
| `reader_watermark` | Use reader watermark; `1`, `0` | `1` | `reader_watermark="0"` |
| `remember_page` | Reader: remember page during this visit; `1`, `0` | `1` | `remember_page="0"` |
| `initial_zoom` | Reader: initial zoom; `fit`, `1`, `1.25`, `1.5` | `fit` | `initial_zoom="fit"` |

## [builder_pdf_filters]

| Parameter | Meaning / default | Example |
|---|---|---|
| `target` | Required matching collection_id; unique on the page. | `target="newsletters"` |
| `layout` | horizontal (default) or vertical. | `layout="vertical"` |
| `show_search` | 1 (default); 0 hides search input. | `show_search="0"` |
| `show_sort` | 1 (default); 0 hides visitor sorting. | `show_sort="0"` |
| `show_size_filter` | 1 (default); 0 hides size filter. | `show_size_filter="0"` |
| `show_reset` | 1 (default); 0 hides the reset link. | `show_reset="0"` |

Search covers configured titles and file names, never PDF contents. Apply reloads the page with shareable GET parameters; visitor sorting is not saved and no search analytics are collected. Size ranges: under 1 MiB, 1 to under 5 MiB, 5 MiB or more, unknown; 1 MiB = 1,048,576 bytes. Unknown sizes are never fetched from PDF files and sort last.

## Complete examples

### Current post

```text
[builder_pdf_presenter]
```

### Specific source post

```text
[builder_pdf_presenter post_id="123"]
```

### Single PDF

```text
[builder_pdf_presenter post_id="123" pdf_id="456" mode="tab"]
```

### All PDFs of one post, sorted

```text
[builder_pdf_presenter post_id="123" collection_id="minutes" orderby="title" order="ASC" page_size="12"]
```

### Archive with filter bar

```text
[builder_pdf_filters target="newsletters"]
[builder_pdf_presenter source="posts" post_type="post" per_post="all" collection_id="newsletters" page_size="12" columns="3"]
```

### Override actions and reader

```text
[builder_pdf_presenter mode="modal" show_download="0" reader_download="0" zoom="1" search="1" read_label="Read now"]
```

Give each independently filtered collection its own name. Place filters above with content between them or in another column. Patterns supply a matching name; change both linked names when inserting multiple copies on one page. Use native Bricks filters for native Bricks post query loops.
