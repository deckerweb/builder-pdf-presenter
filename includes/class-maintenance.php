<?php
/** Bounded editor operations for preview recovery and collection validation. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Keeps media recovery in authenticated administration and validates saved custom block collections. */
final class Maintenance {
	/** Register authenticated media operations and server-side block validation.
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_ajax_bpp_block_preview', array( self::class, 'block_preview' ) );
		add_action( 'wp_ajax_bpp_preview', array( self::class, 'preview' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'validate_blocks' ), 10, 2 );
	}

	/** Render an authenticated block preview without loading a PDF or writing data.
	 * @return void JSON containing rendered cards, context and an explicit reader test link.
	 */
	public static function block_preview(): void {
		check_ajax_referer( 'bpp_block_preview' );
		$id = absint( $_POST['post_id'] ?? 0 );
		if ( ! current_user_can( 'edit_post', $id ) ) { wp_send_json_error( array( 'message' => __( 'Permission denied.', 'builder-pdf-presenter' ) ), 403 ); }
		$raw = isset( $_POST['attributes'] ) && is_string( $_POST['attributes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['attributes'] ) ) : '';
		if ( strlen( $raw ) > 262144 ) { wp_send_json_error( array( 'message' => __( 'The document collection could not be read.', 'builder-pdf-presenter' ) ), 400 ); }
		$attrs = json_decode( $raw, true );
		if ( ! is_array( $attrs ) ) { wp_send_json_error( array( 'message' => __( 'The document collection could not be read.', 'builder-pdf-presenter' ) ), 400 ); }

		$schema = \WP_Block_Type_Registry::get_instance()->get_registered( 'builder-pdf-presenter/document' )->attributes;
		foreach ( $attrs as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) { unset( $attrs[ $key ] ); continue; }
			if ( is_wp_error( rest_validate_value_from_schema( $value, $schema[ $key ], $key ) ) ) { wp_send_json_error( array( 'message' => __( 'The document collection could not be read.', 'builder-pdf-presenter' ) ), 400 ); }
		}

		$source = absint( $attrs['postId'] ?? 0 ) ?: $id;
		if ( ! current_user_can( 'read_post', $source ) ) { wp_send_json_error( array( 'message' => __( 'Permission denied.', 'builder-pdf-presenter' ) ), 403 ); }
		$attrs['pdf_id'] = absint( $attrs['attachmentId'] ?? 0 );
		$context = Admin::editor_config( $source );
		$context['limit'] = Settings::limit( $id );
		$context['defaults'] = Settings::resolve( $source, $attrs );
		foreach ( Settings::clean( $attrs ) as $key => $value ) { $context['origins'][ $key ] = __( 'Block settings', 'builder-pdf-presenter' ); }
		$entries = 'custom' === ( $attrs['source'] ?? '' ) ? Documents::clean( $attrs['documents'] ?? array(), array(), 500 ) : Documents::get( $source );
		if ( is_wp_error( $entries ) ) { wp_send_json_error( array( 'message' => $entries->get_error_message() ), 400 ); }
		if ( $attrs['pdf_id'] ) { $entries = array( array( 'pdf' => $attrs['pdf_id'] ) ); }
		if ( empty( $attrs['pdf_id'] ) && ( 'posts' === ( $attrs['source'] ?? '' ) || ! empty( $attrs['collection_id'] ) || ! empty( $attrs['orderby'] ) ) ) {
			[ $rows ] = Library::rows( $source, $attrs );
			$rows = Library::select( $rows, $attrs, sanitize_key( $attrs['collection_id'] ?? 'preview' ) );
			$entries = array();
			if ( $rows ) { $source = $rows[0]['post_id']; $entries = array( $rows[0]['entry'] ); }
		}

		$reader = '';
		foreach ( $entries as $entry ) {
			$pdf = absint( $entry['pdf'] ?? 0 );
			if ( Presenter::valid_pdf( $pdf ) && current_user_can( 'read_post', $pdf ) ) { $options = Settings::resolve( $source, array_replace( $entry['options'] ?? array(), $attrs ) ); $options['document_title'] = ! empty( $entry['title'] ) ? $entry['title'] : get_the_title( $source ); $reader = Presenter::reader_url( $source, $pdf, $options ); break; }
		}
		wp_send_json_success( array( 'html' => '<div style="' . esc_attr( Block_Styles::variables( $attrs ) ) . '">' . Presenter::output( absint( $attrs['postId'] ?? 0 ) ?: $id, $attrs ) . '</div>', 'reader' => $reader, 'context' => $context ) );
	}

	/** Regenerate one explicitly requested PDF preview without deleting usable existing metadata.
	 * @return void JSON response; only upload-capable editors may invoke the operation.
	 */
	public static function preview(): void {
		check_ajax_referer( 'bpp_preview' );
		$post_id = absint( $_POST['post_id'] ?? 0 ); $pdf = absint( $_POST['pdf_id'] ?? 0 );
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'edit_post', $pdf ) || ! Presenter::valid_pdf( $pdf ) ) { wp_send_json_error( array( 'message' => __( 'This document cannot be updated with your permissions.', 'builder-pdf-presenter' ) ), 403 ); }
		$file = get_attached_file( $pdf );
		if ( ! $file || ! is_file( $file ) ) { wp_send_json_error( array( 'message' => __( 'The PDF file is unavailable on this server. Select a custom cover instead.', 'builder-pdf-presenter' ) ), 400 ); }
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $pdf, $file );
		if ( empty( $metadata['sizes']['full'] ) ) { wp_send_json_error( array( 'message' => __( 'The server could not generate a preview. Check PDF image processing support or select a custom cover.', 'builder-pdf-presenter' ) ), 400 ); }
		wp_update_attachment_metadata( $pdf, $metadata );
		$image = wp_get_attachment_image_src( $pdf, 'medium' );
		wp_send_json_success( array( 'preview' => $image ? $image[0] : '' ) );
	}

	/** Validate independent block collections against current administrator limits.
	 * @param array $data Prepared post data with slashed content.
	 * @param array $postarr Original post request containing the contribution ID.
	 * @return array Post data with invalid collection edits replaced by the previous valid selection.
	 */
	public static function validate_blocks( array $data, array $postarr ): array {
		if ( empty( $data['post_content'] ) || ! str_contains( $data['post_content'], 'builder-pdf-presenter/document' ) ) { return $data; }
		$post_id = absint( $postarr['ID'] ?? 0 ); $old = $post_id ? get_post_field( 'post_content', $post_id ) : '';
		$before = parse_blocks( $old ); $blocks = parse_blocks( wp_unslash( $data['post_content'] ) );
		$walk = static function( array &$items, array $prior ) use ( &$walk, $post_id ): void {
			foreach ( $items as $i => &$item ) {
				if ( 'builder-pdf-presenter/document' === ( $item['blockName'] ?? '' ) && 'custom' === ( $item['attrs']['source'] ?? '' ) ) {
					$previous = 'builder-pdf-presenter/document' === ( $prior[ $i ]['blockName'] ?? '' ) ? ( $prior[ $i ]['attrs']['documents'] ?? array() ) : array();
					$clean = Documents::clean( $item['attrs']['documents'] ?? array(), $previous, Settings::limit( $post_id ) );
					if ( is_wp_error( $clean ) && get_current_user_id() ) { set_transient( 'bpp_save_error_' . get_current_user_id(), $clean->get_error_message(), 120 ); }
					$item['attrs']['documents'] = is_wp_error( $clean ) ? $previous : $clean;
				}
				if ( ! empty( $item['innerBlocks'] ) ) { $walk( $item['innerBlocks'], $prior[ $i ]['innerBlocks'] ?? array() ); }
			}
		};
		$walk( $blocks, $before );
		$data['post_content'] = wp_slash( serialize_blocks( $blocks ) );
		return $data;
	}
}
