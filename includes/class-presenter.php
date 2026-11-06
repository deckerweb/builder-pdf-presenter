<?php
/** Render cards and authorize on-demand reading routes. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Provides shared rendering for shortcodes, the block and builder query loops. */
final class Presenter {
	/** Register presentation entry points and reader routing. @return void */
	public static function boot(): void {
		wp_register_style( 'bpp-editor', BPP_URL . 'assets/admin.css', array(), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/admin.css' ) );
		wp_register_style( 'bpp-frontend', BPP_URL . 'assets/frontend.css', array(), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/frontend.css' ) );
		wp_register_script( 'bpp-frontend', BPP_URL . 'assets/frontend.js', array(), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/frontend.js' ), true );
		wp_register_script( 'bpp-manager', BPP_URL . 'assets/collection.js', array( 'media-views' ), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/collection.js' ), true );
		wp_register_script( 'bpp-block', BPP_URL . 'block/editor.js', array( 'bpp-manager', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-api-fetch' ), BPP_VERSION . '.' . filemtime( BPP_DIR . 'block/editor.js' ), true );
		wp_set_script_translations( 'bpp-block', 'builder-pdf-presenter', BPP_DIR . 'languages' );
		register_block_type( BPP_DIR . 'block', array( 'render_callback' => array( self::class, 'block' ) ) );
		register_block_type( BPP_DIR . 'filter-block', array( 'render_callback'=>static function( $attrs ) { return '<div ' . get_block_wrapper_attributes( array( 'style'=>Block_Styles::variables( $attrs ) ) ) . '>' . Library::filters( $attrs ) . '</div>'; } ) );
		add_shortcode( 'builder_pdf_filters', array( Library::class, 'filters' ) );
		add_shortcode( 'builder_pdf_presenter', array( self::class, 'shortcode' ) );
		add_filter( 'query_vars', array( self::class, 'query_vars' ) );
		add_action( 'template_redirect', array( self::class, 'reader' ), 0 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'styles' ) );
	}

	/** Queue the small stylesheet early so cards rendered after wp_head are styled.
	 * @return void
	 */
	public static function styles(): void {
		// Cards may be created by arbitrary builder loops after the document head.
		global $post;
		if ( ! empty( Settings::stored()['enabled'] ) || ( $post && ( has_shortcode( $post->post_content, 'builder_pdf_presenter' ) || has_shortcode( $post->post_content, 'builder_pdf_filters' ) ) ) ) { wp_enqueue_style( 'bpp-frontend' ); }
	}

	/** Check both publication visibility and any post-password requirement.
	 * @param int $post_id Source contribution.
	 * @return bool Whether this visitor may see its document.
	 */
	public static function readable( int $post_id ): bool {
		$post = get_post( $post_id );
		return $post && ! post_password_required( $post ) && ( is_post_publicly_viewable( $post ) || current_user_can( 'read_post', $post_id ) );
	}

	/** Find a PDF assigned to a contribution without modifying its content.
	 * @param int $post_id Source contribution.
	 * @return int PDF attachment ID or zero.
	 */
	public static function pdf( int $post_id ): int {
		$id = absint( get_post_meta( $post_id, '_bpp_pdf_id', true ) );
		if ( $id ) { return self::valid_pdf( $id ) ? $id : 0; }
		$post = get_post( $post_id );
		return $post ? self::block_pdf( parse_blocks( $post->post_content ) ) : 0;
	}

	/** Look through saved blocks for a local PDF, including legacy PDF Poster blocks.
	 * @param array $blocks Parsed Gutenberg blocks.
	 * @return int First valid attachment ID.
	 */
	private static function block_pdf( array $blocks ): int {
		foreach ( $blocks as $block ) {
			$attrs = $block['attrs'] ?? array();
			$id = 0;
			if ( 'pdfp/pdfposter' === $block['blockName'] ) { $id = absint( $attrs['fileId'] ?? 0 ); }
			if ( 'builder-pdf-presenter/document' === $block['blockName'] ) { $id = absint( $attrs['attachmentId'] ?? 0 ); }
			if ( self::valid_pdf( $id ) ) { return $id; }
			$id = self::block_pdf( $block['innerBlocks'] ?? array() );
			if ( $id ) { return $id; }
		}
		return 0;
	}

	/** Validate a PDF selection against WordPress attachment metadata.
	 * @param int $id Candidate attachment ID.
	 * @return bool Whether it is an existing PDF attachment.
	 */
	public static function valid_pdf( int $id ): bool { return $id > 0 && 'attachment' === get_post_type( $id ) && 'application/pdf' === get_post_mime_type( $id ) && (bool) wp_get_attachment_url( $id ); }

	/** Render a shortcode in the current query-loop contribution.
	 * @param array|string $attrs Shortcode attributes.
	 * @return string Card markup or an empty string.
	 */
	public static function shortcode( $attrs ): string {
		$attrs = is_array( $attrs ) ? $attrs : array();
		return self::output( absint( $attrs['post_id'] ?? get_the_ID() ), $attrs );
	}

	/** Render the dynamic block, honoring its query-loop context.
	 * @param array $attrs Saved block attributes.
	 * @param string $content Unused dynamic block content.
	 * @param \WP_Block $block Current block with post context.
	 * @return string Wrapped document card.
	 */
	public static function block( array $attrs, string $content, \WP_Block $block ): string {
		$post_id = absint( $attrs['postId'] ?? 0 ) ?: absint( $block->context['postId'] ?? get_the_ID() );
		$attrs['pdf_id'] = absint( $attrs['attachmentId'] ?? 0 );
		$markup = self::output( $post_id, $attrs );
		return $markup ? '<div ' . get_block_wrapper_attributes( array( 'style' => Block_Styles::variables( $attrs ), 'id' => sanitize_html_class( $attrs['anchor'] ?? '' ) ) ) . '>' . $markup . '</div>' : '';
	}

	/** Render a single PDF, a contribution collection or a query of document contributions.
	 * @param int $post_id Current contribution used as source.
	 * @param array $attrs Explicit output options and optional independent collection.
	 * @return string Shared markup for blocks and shortcodes.
	 */
	public static function output( int $post_id, array $attrs = array() ): string {
		if ( ! self::readable( $post_id ) ) { return ''; }
		if ( ! empty( $attrs['pdf_id'] ) ) { return self::card( $post_id, absint( $attrs['pdf_id'] ), $attrs ); }
		if ( 'posts' === ( $attrs['source'] ?? '' ) || ! empty( $attrs['collection_id'] ) || ! empty( $attrs['orderby'] ) || ! empty( $attrs['page_size'] ) ) { return Library::output( $post_id, $attrs ); }
		$independent = 'custom' === ( $attrs['source'] ?? '' );
		$entries = $independent ? ( $attrs['documents'] ?? array() ) : Documents::get( $post_id );
		if ( $independent ) { $entries = Documents::clean( $entries, array(), 500 ); if ( is_wp_error( $entries ) ) { return ''; } }
		$html = ''; $multiple = count( $entries ) > 1;
		foreach ( $entries as $entry ) {
			if ( $multiple && empty( $entry['title'] ) ) { $entry['title'] = Documents::title( absint( $entry['pdf'] ) ); }
			$html .= self::card( $post_id, absint( $entry['pdf'] ), $attrs, $entry );
		}
		if ( ! $html ) { return current_user_can( 'edit_post', $post_id ) ? '<p class="bpp-empty">' . esc_html__( 'Add PDF documents in the contribution panel or choose a document in this block.', 'builder-pdf-presenter' ) . '</p>' : ''; }
		return $multiple ? self::grid( $html, Settings::resolve( $post_id, $attrs ) ) : $html;
	}

	/** Wrap ready-rendered cards in a responsive collection.
	 * @param string $html Already escaped document card markup.
	 * @param array $options Effective collection layout settings.
	 * @return string Collection wrapper with accessible navigation labels.
	 */
	public static function grid( string $html, array $options ): string {
		return '<div class="bpp-collection bpp-columns-' . esc_attr( $options['columns'] ) . ' bpp-collection-' . esc_attr( $options['design'] ) . '" data-bpp-previous="' . esc_attr__( 'Previous document', 'builder-pdf-presenter' ) . '" data-bpp-next="' . esc_attr__( 'Next document', 'builder-pdf-presenter' ) . '">' . $html . '</div>';
	}

	/** Build a signed URL for the first-party reader with serialized output settings.
	 * @param int $post_id Source contribution checked again by the reader.
	 * @param int $pdf_id PDF selected for this output.
	 * @param array $options Effective sanitized configuration.
	 * @return string Same-site reader URL.
	 */
	public static function reader_url( int $post_id, int $pdf_id, array $options ): string {
		$config = rtrim( strtr( base64_encode( wp_json_encode( $options ) ), '+/', '-_' ), '=' );
		$signature = hash_hmac( 'sha256', $post_id . '|' . $pdf_id . '|' . $config, wp_salt( 'auth' ) );
		return add_query_arg( array( 'bpp_reader' => $post_id, 'bpp_pdf' => $pdf_id, 'bpp_config' => $config, 'bpp_sig' => $signature ), home_url( '/' ) );
	}

	/** Render a lightweight card; no PDF.js or PDF document is loaded here.
	 * @param int $post_id Contribution supplying visibility and settings.
	 * @param int $pdf_id Optional explicit PDF attachment ID.
	 * @param array $overrides Optional output overrides.
	 * @param array $entry Document title, cover and individual options.
	 * @return string Escaped card markup.
	 */
	public static function card( int $post_id, int $pdf_id = 0, array $overrides = array(), array $entry = array() ): string {
		if ( ! self::readable( $post_id ) ) { return ''; }
		$pdf_id = $pdf_id ?: self::pdf( $post_id );
		if ( ! self::valid_pdf( $pdf_id ) ) { return ''; }
		$options = Settings::resolve( $post_id, array_replace( $entry['options'] ?? array(), $overrides ) );
		$url = wp_get_attachment_url( $pdf_id );
		$title = isset( $entry['title'] ) && '' !== $entry['title'] ? $entry['title'] : get_the_title( $post_id );
		$preview_id = absint( $entry['cover'] ?? 0 ) ?: absint( get_post_meta( $post_id, '_bpp_cover_id', true ) );
		$image = wp_get_attachment_image_src( $preview_id ?: $pdf_id, 'large' );
		if ( ! $preview_id && empty( wp_get_attachment_metadata( $pdf_id )['sizes'] ) ) { $image = false; }
		$open_url = 'file' === $options['mode'] ? $url : self::reader_url( $post_id, $pdf_id, array_merge( $options, array( 'document_title' => $title ) ) );
		wp_enqueue_script( 'bpp-frontend' );
		/* translators: %s: document contribution title. */
		$read_description = sprintf( __( 'Read %s', 'builder-pdf-presenter' ), $title );
		ob_start();
		?>
		<article class="bpp-card bpp-ratio-<?php echo esc_attr( $options['ratio'] ); ?> bpp-design-<?php echo esc_attr( $options['design'] ); ?>" data-bpp-key="<?php echo esc_attr( $post_id . '-' . ( $entry['key'] ?? $pdf_id ) ); ?>" data-bpp-title="<?php echo esc_attr( $title ); ?>" data-bpp-close="<?php esc_attr_e( 'Close reader', 'builder-pdf-presenter' ); ?>">
			<?php if ( $options['cover_link'] ) : ?><a class="bpp-cover" href="<?php echo esc_url( $open_url ); ?>" <?php if ( 'modal' === $options['mode'] ) : ?>data-bpp-open="1"<?php else : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?> aria-label="<?php echo esc_attr( $read_description ); ?>"><?php else : ?><div class="bpp-cover"><?php endif; ?>
			<?php if ( $image ) : ?><img src="<?php echo esc_url( $image[0] ); ?>" width="<?php echo absint( $image[1] ); ?>" height="<?php echo absint( $image[2] ); ?>" alt="" loading="lazy" decoding="async"><?php else : ?><span class="bpp-placeholder" aria-hidden="true">PDF</span><?php endif; ?>
			<?php echo $options['cover_link'] ? '</a>' : '</div>'; ?>
			<div class="bpp-info">
			<?php if ( $options['show_title'] ) : ?><h3 class="bpp-title"><?php echo esc_html( $title ); ?></h3><?php endif; ?>
			<?php if ( $options['show_size'] ) : $size = Library::media( $pdf_id )['size']; ?><p class="bpp-meta">PDF<?php if ( $size ) { echo ' · ' . esc_html( size_format( $size ) ); } ?></p><?php endif; ?>
			<div class="bpp-actions">
			<?php if ( $options['show_read'] ) : ?><a class="bpp-button bpp-read" href="<?php echo esc_url( $open_url ); ?>" <?php if ( 'modal' === $options['mode'] ) : ?>data-bpp-open="1"<?php else : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $options['read_label'] ); ?></a><?php endif; ?>
			<?php if ( $options['show_download'] ) : ?><a class="bpp-button bpp-download" href="<?php echo esc_url( $url ); ?>" download><?php echo esc_html( $options['download_label'] ); ?></a><?php endif; ?>
			</div></div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/** Register the read-only reader route parameters.
	 * @param array $vars Existing public query variables.
	 * @return array Query variables including the signed reader parameters.
	 */
	public static function query_vars( array $vars ): array { return array_merge( $vars, array( 'bpp_reader', 'bpp_pdf', 'bpp_config', 'bpp_sig' ) ); }

	/** Authorize and serve a reader document independently of the current theme.
	 * @return void
	 */
	public static function reader(): void {
		if ( ! get_query_var( 'bpp_reader' ) ) { return; }
		$post_id = absint( get_query_var( 'bpp_reader' ) );
		$pdf_id = absint( get_query_var( 'bpp_pdf' ) );
		$config = get_query_var( 'bpp_config' );
		$config = is_string( $config ) ? sanitize_text_field( $config ) : '';
		$sig = get_query_var( 'bpp_sig' );
		$sig = is_string( $sig ) ? sanitize_text_field( $sig ) : '';
		$expected = hash_hmac( 'sha256', $post_id . '|' . $pdf_id . '|' . $config, wp_salt( 'auth' ) );
		if ( strlen( $config ) > 6000 || ! hash_equals( $expected, $sig ) || ! self::readable( $post_id ) || ! self::valid_pdf( $pdf_id ) ) {
			wp_die( esc_html__( 'This document is unavailable or requires access to its source post.', 'builder-pdf-presenter' ), '', array( 'response' => 403 ) );
		}
		$options = array_replace( Settings::defaults(), Settings::clean( json_decode( (string) base64_decode( strtr( $config, '-_', '+/' ), true ), true ) ) );
		$document_url = wp_get_attachment_url( $pdf_id );
		$decoded = json_decode( (string) base64_decode( strtr( $config, '-_', '+/' ), true ), true );
		$watermark = Watermark::effective( $options );
		$title = is_string( $decoded['document_title'] ?? null ) ? sanitize_text_field( $decoded['document_title'] ) : get_the_title( $post_id );
		nocache_headers();
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: same-origin' );
		header( 'X-Content-Type-Options: nosniff' );
		wp_enqueue_style( 'bpp-reader', BPP_URL . 'assets/reader.css', array(), BPP_VERSION );
		wp_enqueue_script_module( 'bpp-reader', BPP_URL . 'assets/reader-loader.js', array(), BPP_VERSION );
		require BPP_DIR . 'includes/reader-view.php';
		exit;
	}
}
