<?php
/** Optional Bricks adapter for the independent document renderer. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Registers native elements and context-aware dynamic tags when Bricks is available. */
final class Bricks_Integration {
	/** Register optional integration without requiring the theme for plugin activation.
	 * @return void
	 */
	public static function boot(): void {
		if ( ! class_exists( '\Bricks\Elements' ) || ! class_exists( '\Bricks\Element' ) ) { return; }
		require_once BPP_DIR . 'includes/bricks/class-element.php';
		\Bricks\Elements::register_element( BPP_DIR . 'includes/bricks/document.php', '', __NAMESPACE__ . '\\Bricks_Document' );
		\Bricks\Elements::register_element( BPP_DIR . 'includes/bricks/collection.php', '', __NAMESPACE__ . '\\Bricks_Collection' );
		\Bricks\Elements::register_element( BPP_DIR . 'includes/bricks/filters.php', '', __NAMESPACE__ . '\\Bricks_Filters' );
		add_action( 'wp_enqueue_scripts', array( self::class, 'builder_assets' ) );
		add_filter( 'bricks/builder/i18n', array( self::class, 'category' ) );
		add_filter( 'bricks/dynamic_tags_list', array( self::class, 'tags' ) );
		add_filter( 'bricks/dynamic_data/render_tag', array( self::class, 'tag' ), 20, 3 );
		add_filter( 'bricks/dynamic_data/render_content', array( self::class, 'content' ), 20, 3 );
		add_filter( 'bricks/frontend/render_data', array( self::class, 'content' ), 20, 2 );
	}

	/** Load picker glyphs only in the Bricks editing interface. @return void */
	public static function builder_assets(): void {
		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			wp_enqueue_style( 'bpp-bricks-editor', BPP_URL . 'assets/bricks-editor.css', array(), BPP_VERSION );
		}
	}

	/** Name the native element category.
	 * @param array $labels Builder labels.
	 * @return array Labels including the plugin category.
	 */
	public static function category( array $labels ): array { $labels['bpp'] = 'PDF Presenter'; return $labels; }

	/** Resolve current post-loop context before falling back to a preview or page.
	 * @param int $fallback Builder preview post ID.
	 * @return int Current post object ID, or zero in non-post loops.
	 */
	public static function post_id( int $fallback = 0 ): int {
		if ( class_exists( '\Bricks\Query' ) && \Bricks\Query::is_looping() ) { return 'post' === \Bricks\Query::get_loop_object_type() ? absint( \Bricks\Query::get_loop_object_id() ) : 0; }
		return $fallback ?: absint( get_the_ID() );
	}

	/** Register useful first-document fields and a collection count for conditions.
	 * @param array $tags Existing dynamic tags.
	 * @return array Extended tag picker.
	 */
	public static function tags( array $tags ): array {
		foreach ( array( 'count'=>__( 'PDF count', 'builder-pdf-presenter' ), 'id'=>__( 'PDF attachment ID', 'builder-pdf-presenter' ), 'title'=>__( 'PDF title', 'builder-pdf-presenter' ), 'url'=>__( 'PDF URL', 'builder-pdf-presenter' ), 'reader_url'=>__( 'PDF reader URL', 'builder-pdf-presenter' ), 'cover'=>__( 'PDF cover', 'builder-pdf-presenter' ) ) as $key=>$label ) { $tags[] = array( 'name'=>'{bpp_' . $key . '}', 'label'=>$label, 'group'=>'PDF Presenter' ); }
		return $tags;
	}

	/** Resolve one tag with the same visibility checks as document cards.
	 * @param mixed $tag Requested tag, including optional braces.
	 * @param mixed $post Context supplied by Bricks.
	 * @param string $context Rendering context: text, image or link.
	 * @return mixed Original unknown tag, scalar value or image attachment array.
	 */
	public static function tag( $tag, $post, $context = 'text' ) {
		if ( ! is_string( $tag ) ) { return $tag; }
		$key = trim( $tag, '{}' );
		if ( ! in_array( $key, array( 'bpp_count', 'bpp_id', 'bpp_title', 'bpp_url', 'bpp_reader_url', 'bpp_cover' ), true ) ) { return $tag; }
		$id = self::post_id( $post instanceof \WP_Post ? $post->ID : 0 );
		if ( ! $id || ! Presenter::readable( $id ) ) { return 'bpp_count' === $key ? '0' : ''; }
		$entries = array_values( array_filter( Documents::get( $id ), static fn( $e ) => Presenter::valid_pdf( absint( $e['pdf'] ?? 0 ) ) ) );
		if ( 'bpp_count' === $key ) { return (string) count( $entries ); }
		if ( ! $entries ) { return ''; }
		$e = $entries[0]; $pdf = absint( $e['pdf'] );
		$title = ! empty( $e['title'] ) ? $e['title'] : get_the_title( $id );
		switch ( $key ) {
			case 'bpp_id': return (string) $pdf;
			case 'bpp_title': return esc_html( $title );
			case 'bpp_url': return esc_url( wp_get_attachment_url( $pdf ) );
			case 'bpp_reader_url': return esc_url( Presenter::reader_url( $id, $pdf, array_merge( Settings::resolve( $id, $e['options'] ?? array() ), array( 'document_title'=>$title ) ) ) );
			case 'bpp_cover':
				$cover = absint( $e['cover'] ?? 0 ) ?: absint( get_post_meta( $id, '_bpp_cover_id', true ) ) ?: $pdf;
				$image = wp_get_attachment_image_src( $cover, 'large' );
				return 'image' === $context ? ( $image ? array( $cover ) : array() ) : ( $image ? esc_url( $image[0] ) : '' );
		}
		return '';
	}

	/** Replace only owned tags inside content; native dynamic-data conditions also use these.
	 * @param mixed $content Bricks content.
	 * @param mixed $post Current context.
	 * @param string $context Rendering context.
	 * @return mixed Content with known scalar tags replaced.
	 */
	public static function content( $content, $post, $context = 'text' ) {
		if ( ! is_string( $content ) || false === strpos( $content, '{bpp_' ) ) { return $content; }
		return preg_replace_callback( '/\{bpp_(?:count|id|title|url|reader_url|cover)\}/', static function( $m ) use ( $post ) { return (string) self::tag( $m[0], $post, 'text' ); }, $content );
	}
}
