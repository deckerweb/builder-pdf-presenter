=== Builder PDF Presenter ===
Contributors: daveshine
Tags: pdf, documents, preview, shortcode, block
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight PDF preview cards with an on-demand reader, configurable display settings, blocks and shortcodes for any post type.

== Description ==

Fast PDF previews and an on-demand reader. Use one PDF per contribution or an ordered collection within a contribution, independently of page builders and custom-field plugins.

= At a glance =

* Sortable PDF collections, multiple upload, deduplication and per-document settings.
* Website and content type defaults, administrator limits and visible inheritance.
* Three card designs, Block Editor blocks, shortcodes and eight starter patterns.
* Modal, reader tab and original PDF modes. Local PDF.js assets.
* Preview recovery and migration from PDF Poster and PDF Embedder with reports and recovery.
* deckerweb Updater 2.3.0 and Plugin Library 1.2.0 included.

== Installation ==

1. Upload and activate the ZIP.
2. Open Settings > Builder PDF Presenter, choose editorial content types and save.
3. Add PDFs in the contribution panel.
4. Insert the block or [builder_pdf_presenter]. In Bricks, use the PDF document element within the post query loop or the PDF collection element.

== Frequently Asked Questions ==


= Do I need a page builder or custom-field plugin? =
No. The core works with Block Editor and shortcodes. Bricks integration is optional.

= Can one post contain several PDFs? =
Yes. Add an ordered collection to the post or directly to a block and display it as cards or a list.

= Can I create an archive across posts? =
Yes. Use the block’s contribution query or a native post Query Loop. The direct query can show the first or all PDFs per matching contribution and has a bounded count; use pagination for long archives.

= Why is a preview image missing? =
Automatic PDF previews depend on the server. Generate a missing preview where supported or select a custom cover.

= Is this a protected file or membership system? =
No. Button visibility is not file protection. Existing server permissions still apply.

= Does the watermark change downloads? =
No. It appears only in the embedded reader.

= Is there a Pro edition or reader tracking? =
No. There is no Pro edition, upselling or reader tracking by the plugin.

Complete FAQ: bundled docs/FAQ.md and the local Documentation link in the plugin settings.


== Changelog ==

= 1.0.0 =
* New: PDF cards with previews, a reader and downloads for individual documents and collections.
* New: Global defaults and individual settings for any post type.
* New: Block Editor, shortcodes and native Bricks integration, including metadata filters and sorting.
* New: Migration from PDF Poster and PDF Embedder with outcome reports and recovery.


== Third-party libraries ==

PDF.js 6.5.438 by Mozilla and contributors, https://mozilla.github.io/pdf.js/ and https://github.com/mozilla/pdf.js. Apache-2.0; license and font notices under vendor/pdfjs. Upstream source unmodified. Combined under GPLv3-compatible terms.

deckerweb Updater 2.3.0: https://github.com/deckerweb/deckerweb-updater. Plugin Library 1.2.0: https://github.com/deckerweb/deckerweb-plugin-library. By David Decker – DECKERWEB; included license notices apply. Components can contact their configured GitHub update/catalog providers; viewing PDFs uses local assets.

Tabler file-type-pdf icon: https://github.com/tabler/tabler-icons (MIT). License bundled in vendor/tabler/LICENSE.
