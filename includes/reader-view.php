<?php
/** Isolated reader template. Variables are validated by Presenter::reader(). */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?php echo esc_html( $title ); ?> — Builder PDF Presenter</title>
<?php wp_print_styles( 'bpp-reader' ); ?>
</head>
<body class="bpp-reader" data-bpp-url="<?php echo esc_url( $document_url ); ?>" data-bpp-remember="<?php echo $options['remember_page'] ? '1' : '0'; ?>" data-bpp-zoom="<?php echo esc_attr( $options['initial_zoom'] ); ?>">
<header class="reader-toolbar" aria-label="<?php esc_attr_e( 'PDF reader controls', 'builder-pdf-presenter' ); ?>">
<span class="reader-title"><?php echo esc_html( $title ); ?></span>
<?php if ( $options['page_navigation'] ) : ?>
<div class="reader-group"><button type="button" id="previous" aria-label="<?php esc_attr_e( 'Previous page', 'builder-pdf-presenter' ); ?>">←</button><label for="page"><?php esc_html_e( 'Page', 'builder-pdf-presenter' ); ?></label><input id="page" type="number" min="1" value="1"><span id="page-total"></span><button type="button" id="next" aria-label="<?php esc_attr_e( 'Next page', 'builder-pdf-presenter' ); ?>">→</button></div>
<?php endif; ?>
<?php if ( $options['zoom'] ) : ?>
<div class="reader-group"><button type="button" id="zoom-out" aria-label="<?php esc_attr_e( 'Zoom out', 'builder-pdf-presenter' ); ?>">−</button><button type="button" id="fit"><?php esc_html_e( 'Fit width', 'builder-pdf-presenter' ); ?></button><button type="button" id="zoom-in" aria-label="<?php esc_attr_e( 'Zoom in', 'builder-pdf-presenter' ); ?>">+</button></div>
<?php endif; ?>
<?php if ( $options['search'] ) : ?>
<form id="search-form" class="reader-group"><label class="screen-reader-text" for="search"><?php esc_html_e( 'Search on current page', 'builder-pdf-presenter' ); ?></label><input id="search" type="search" placeholder="<?php esc_attr_e( 'Search on current page', 'builder-pdf-presenter' ); ?>"><button><?php esc_html_e( 'Find', 'builder-pdf-presenter' ); ?></button></form>
<?php endif; ?>
<?php if ( $options['fullscreen'] ) : ?><button type="button" id="fullscreen"><?php esc_html_e( 'Fullscreen', 'builder-pdf-presenter' ); ?></button><?php endif; ?>
<?php if ( $options['print'] ) : ?><a href="<?php echo esc_url( $document_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open PDF for printing', 'builder-pdf-presenter' ); ?></a><?php endif; ?>
<?php if ( $options['reader_download'] ) : ?><a href="<?php echo esc_url( $document_url ); ?>" download><?php echo esc_html( $options['download_label'] ); ?></a><?php endif; ?>
<a href="<?php echo esc_url( $document_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open original PDF', 'builder-pdf-presenter' ); ?></a>
</header>
<p id="reader-status" role="status" aria-live="polite" data-loading="<?php esc_attr_e( 'Loading document…', 'builder-pdf-presenter' ); ?>" data-error="<?php esc_attr_e( 'The PDF could not be loaded. Open the original PDF using the link above.', 'builder-pdf-presenter' ); ?>" data-loaded="<?php esc_attr_e( 'Page loaded', 'builder-pdf-presenter' ); ?>" data-matches="<?php esc_attr_e( 'Matches on this page:', 'builder-pdf-presenter' ); ?>" data-fullscreen-error="<?php esc_attr_e( 'Fullscreen is unavailable in this browser.', 'builder-pdf-presenter' ); ?>"><?php esc_html_e( 'Loading document…', 'builder-pdf-presenter' ); ?></p>
<main id="reader-stage" tabindex="0" aria-label="<?php esc_attr_e( 'PDF document', 'builder-pdf-presenter' ); ?>"><div id="reader-page"><canvas id="pdf-canvas" aria-hidden="true"></canvas><div id="pdf-text" class="textLayer"></div><?php if ( $watermark ) : ?><div class="bpp-watermark bpp-watermark-<?php echo esc_attr( $watermark['position'] ); ?>" aria-hidden="true" data-opacity="<?php echo absint( $watermark['opacity'] ); ?>" data-size="<?php echo absint( $watermark['size'] ); ?>"><?php if ( $watermark['logo'] ) : ?><img src="<?php echo esc_url( wp_get_attachment_image_url( $watermark['logo'], 'medium' ) ); ?>" alt=""><?php endif; ?><span><?php echo esc_html( $watermark['text'] ); ?></span></div><?php endif; ?></div></main>
<noscript><p><a href="<?php echo esc_url( $document_url ); ?>"><?php esc_html_e( 'Open original PDF', 'builder-pdf-presenter' ); ?></a></p></noscript>
<?php wp_script_modules()->print_enqueued_script_modules(); ?>
</body></html>
