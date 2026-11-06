<?php
/** Ordered document collections independent of field frameworks. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Validates collection entries and preserves stable document identities during replacements. */
final class Documents {
	/** Read collection data, falling back to the original single-PDF assignment.
	 * @param int $post_id Contribution containing the document collection.
	 * @return array Ordered document entries, including unavailable entries for editor diagnostics.
	 */
	public static function get( int $post_id ): array {
		$saved = get_post_meta( $post_id, '_bpp_documents', true );
		if ( is_array( $saved ) ) { return $saved; }
		$id = Presenter::pdf( $post_id );
		return $id ? array( array( 'key' => 'legacy-' . $id, 'pdf' => $id, 'title' => '', 'cover' => absint( get_post_meta( $post_id, '_bpp_cover_id', true ) ), 'options' => array() ) ) : array();
	}

	/** Validate collection data and refuse excess additions without truncating existing entries.
	 * @param mixed $input Candidate collection rows.
	 * @param array $existing Previously stored collection rows.
	 * @param int $limit Administrator-defined collection limit.
	 * @return array|\WP_Error Validated entries or a recoverable validation error.
	 */
	public static function clean( $input, array $existing, int $limit ) {
		if ( ! is_array( $input ) || count( $input ) > 500 ) { return new \WP_Error( 'bpp_collection', __( 'The document collection could not be read.', 'builder-pdf-presenter' ) ); }
		if ( count( $input ) > max( count( $existing ), $limit ) ) { return new \WP_Error( 'bpp_limit', __( 'The collection limit was reached. Existing documents were kept.', 'builder-pdf-presenter' ) ); }
		$known = array_column( $existing, null, 'key' ); $seen = array(); $keys = array(); $out = array();
		foreach ( $input as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$id = absint( $row['pdf'] ?? 0 ); $key = sanitize_key( is_scalar( $row['key'] ?? null ) ? (string) $row['key'] : '' );
			if ( isset( $seen[ $id ] ) ) { continue; }
			if ( ! Presenter::valid_pdf( $id ) && ( ! isset( $known[ $key ] ) || (int) $known[ $key ]['pdf'] !== $id ) ) { return new \WP_Error( 'bpp_pdf', __( 'Only PDF attachments can be added. The previous collection was kept.', 'builder-pdf-presenter' ) ); }
			if ( ! $key || isset( $keys[ $key ] ) ) { $key = wp_generate_uuid4(); }
			$seen[ $id ] = true; $keys[ $key ] = true;
			$cover = absint( $row['cover'] ?? 0 );
			$out[] = array( 'key' => $key, 'pdf' => $id, 'title' => is_scalar( $row['title'] ?? '' ) ? mb_substr( sanitize_text_field( (string) ( $row['title'] ?? '' ) ), 0, 180 ) : '', 'cover' => wp_attachment_is_image( $cover ) ? $cover : 0, 'options' => Settings::clean( $row['options'] ?? array() ) );
		}
		return $out;
	}

	/** Return a clean suggested title without changing the Media Library attachment.
	 * @param int $pdf_id PDF attachment.
	 * @return string Human-readable title suggestion.
	 */
	public static function title( int $pdf_id ): string {
		$title = get_the_title( $pdf_id );
		return trim( preg_replace( '/[ _]+/', ' ', preg_replace( '/\.pdf$/i', '', $title ) ) );
	}

	/** Count plugin-owned post assignments, without inspecting foreign builder data.
	 * @param int $pdf_id Media attachment ID.
	 * @return int Number of collection assignments in accessible editable posts.
	 */
	public static function usage( int $pdf_id ): int {
		static $counts = null;
		if ( null === $counts ) {
			$counts = array();
			$page = 1;
			do {
				$query = new \WP_Query( array( 'post_type'=>array_keys( Settings::types() ), 'post_status'=>array( 'publish','private','draft','pending','future' ), 'posts_per_page'=>200, 'paged'=>$page++, 'orderby'=>'ID','order'=>'ASC','no_found_rows'=>true,'update_post_term_cache'=>false ) );
				foreach ( $query->posts as $post ) { if ( ! current_user_can( 'edit_post', $post->ID ) ) { continue; } foreach ( self::get( $post->ID ) as $entry ) { $id = absint( $entry['pdf'] ); $counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1; } }
			} while ( 200 === count( $query->posts ) );
		}
		return $counts[ $pdf_id ] ?? 0;
	}

	/** Decorate documents for the editor without exposing private files to visitors.
	 * @param array $entries Persisted collection rows.
	 * @return array Editor data with preview, availability and upload dates.
	 */
	public static function editor_data( array $entries ): array {
		foreach ( $entries as &$entry ) {
			$id = absint( $entry['pdf'] ); $image = wp_get_attachment_image_src( absint( $entry['cover'] ?? 0 ) ?: $id, 'medium' );
			$entry = array_merge( $entry, Library::media( $id ) ); $entry['uses'] = self::usage( $id );
			$entry['available'] = Presenter::valid_pdf( $id ); $entry['preview'] = $image ? $image[0] : '';
			$entry['suggestion'] = self::title( $id ); $entry['date'] = get_post_field( 'post_date', $id );
		}
		return $entries;
	}
}
