# FAQ by topic

## Getting started

### Is this a flipbook or magazine-effects plugin?
No. Builder PDF Presenter focuses on simple document management, fast cover overviews and comfortable reading. It does not simulate turning pages.

### Is there a Pro edition, advertising or cloud synchronization?
No. There are no paid feature locks, advertisements or cloud synchronization. Documents are managed in your WordPress Media Library.

### Do I need Bricks or Meta Box?
No. The core, Block Editor block and shortcodes work independently. Native Bricks elements are available when Bricks is active. If Bricks is switched off, document assignments and other output methods remain available; a Bricks layout itself still needs Bricks to render.

## Working with documents

### What is a collection?
The PDFs assigned to one post, page or independent PDF block. They can be reordered and presented as a grid or list, including a collection with just one document.

### How do I build an archive from several posts?
For one issue per post, use “Query document contributions” in the block, or a native WordPress/Bricks post Query Loop. The direct block query can display the first or all PDFs per post and applies metadata filters before pagination. In a WordPress Query Loop, the PDF block uses that loop post as its source; Bricks PDF document uses its first assigned PDF. Bricks PDF collection can display a post collection or a plugin archive.

### Why does a newly inserted Bricks element already show a PDF?
Without an explicit file, PDF document uses the first PDF assigned to the current source post, or the current loop post. This is actual content, not a demonstration file. Inserting the element does not create another document assignment.

### Is “PDF Presenter · Block” part of my page?
No. The compact identity and Edit/Preview switch are editor controls. They do not render on the website. Document titles belong to the cards below.

### How do defaults and overrides work?
Built-in defaults → website defaults → content type defaults → contribution settings → individual document/output settings. An empty “Inherit” value keeps the setting from the preceding scope. Central collection limits and watermark policy remain under administrator control.

### Why is my cover missing?
WordPress must have a suitable PDF preview, or you can select a custom image. Automatic generation depends on the server image editor and PDF support. Use “Generate missing previews” or assign a cover manually; the frontend never generates PDF images during a visitor request.

## Reader, privacy and access

### Why PDF.js?
PDF.js is open-source PDF technology also used in Firefox. The reader’s scripts are served locally. The plugin does not require Adobe or Google Docs to display a PDF and does not fall back to those services.

### Why does the reader show a MIME type error for .mjs files?

The server may be sending the PDF.js modules as plain text rather than JavaScript. Correct the MIME mapping for `.mjs` in Apache, nginx or your hosting panel. The [MIME troubleshooting guide](User-guide#reader-troubleshooting-mime-types-for-mjs) includes configuration examples and verification steps. If this cannot be fixed, keep the Download button enabled and optionally hide Read: accessible original PDFs remain available without the reader.

### What if the reader fails to load?
A clear error message directs visitors to the original PDF link. File access rules still apply. A browser may open or download the original depending on its capabilities and server headers.

### Are documents protected when I hide Download?
No. Hiding reader controls is not access control or copy protection. Original PDF URLs and cover images retain the server’s existing permissions. The plugin does not provide a protected document store or membership system.

### Does a watermark modify my PDF?
No. It is a reader overlay. The original, downloads and files opened outside the reader are unchanged. Administrators define the template and can allow per-document on/off exceptions; editors cannot supply arbitrary watermark logos.

### Does the plugin track readers or send PDFs to a cloud?
No reader statistics, profiling or cloud synchronization are provided. The PDF reader uses local assets. Optional deckerweb update/library functions may make the separately documented catalog or update requests; that does not upload the PDFs.

### What data is stored in my browser?
The optional visit-only page memory uses session storage to remember the current page per document. It is used for reader convenience, not analytics. Clearing the session clears that memory.

## Administration and troubleshooting

### What does Site Health check?
Local reader-file availability and whether WordPress reports PDF image-editor support. It does not upload documents, scan your entire library or guarantee that every PDF can be converted. Missing preview support is a recommendation; custom covers remain usable.

### What do the Command Palette shortcuts do?
They open documentation, settings or the PDF Poster migration page when the current user has permission and that page is available. They do not run migrations or save changes.

### How does migration work?
Under Tools → Switch from PDF Poster, review detected contributions and migrate explicitly. A before/after snapshot and operation report support per-contribution recovery. Later edits can cause a conflict and are not silently overwritten. The tool appears when PDF Poster or relevant embeddings/backups are detected; use the existing Media Library files.

### Does this work in Multisite, and what happens on uninstall?
Settings, assignments and migration reports are site-specific. Deactivation/uninstall preserve user documents and settings by default. The embedded components manage their shared temporary data separately. Keep normal backups of the website and Media Library.

### Why can a theme still affect the design?
The plugin provides namespaced, readable defaults. Block Editor style controls and native Bricks styling can customize each output. Extremely broad or important theme rules can still override styles; there is no guarantee against every third-party CSS rule.

## Search and portable defaults

### Does collection search read PDF contents?
No. It searches the selected display title, actual source post title, Media Library title and file name. Apply filters searches the whole accessible collection before pagination. This is separate from the reader’s optional search on its currently displayed PDF page. Search URLs are shareable; the plugin does not record search statistics.

### Why does a PDF have an unknown size?
The original WordPress attachment metadata may not contain a byte size. The plugin does not download or analyze a file to fill it during a visitor request. Unknown sizes have their own filter and sort last in either direction.

### Does settings import move documents to another website?
No. It transfers saved presentation defaults only. Content types must be explicitly mapped, and duplicate target mappings are rejected. Media IDs and PDF assignments are not copied. The watermark is disabled and its logo removed from the imported defaults until the administrator reviews the local template. Import replaces plugin defaults, never documents or migration recovery snapshots.


[PDF Poster and PDF Embedder migration: workflow, limitations and recovery](PDF-migration)
