<?php
/** Shared metadata-only sorting, search and pagination for document libraries. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Builds accessible document results before pagination, without opening PDF files. */
final class Library {
	/** Available document and post sorting keys.
	 * @return array<string,string> Translated choices.
	 */
	public static function sorts(): array {
		return array( 'manual'=>__( 'Saved order', 'builder-pdf-presenter' ), 'title'=>__( 'Display title', 'builder-pdf-presenter' ), 'media_title'=>__( 'Media Library title', 'builder-pdf-presenter' ), 'filename'=>__( 'File name', 'builder-pdf-presenter' ), 'upload_date'=>__( 'PDF upload date', 'builder-pdf-presenter' ), 'size'=>__( 'File size', 'builder-pdf-presenter' ), 'date'=>__( 'Post publication date', 'builder-pdf-presenter' ), 'post_title'=>__( 'Source post title', 'builder-pdf-presenter' ), 'menu_order'=>__( 'Post menu order', 'builder-pdf-presenter' ) );
	}

	/** Read one attachment's existing metadata; never stat a remote or local PDF.
	 * @param int $id Attachment ID.
	 * @return array File name, numeric byte size or null, media title and upload date.
	 */
	public static function media( int $id ): array {
		$meta = wp_get_attachment_metadata( $id );
		$file = get_post_meta( $id, '_wp_attached_file', true );
		return array( 'filename'=>is_string( $file ) ? wp_basename( $file ) : '', 'size'=>isset( $meta['filesize'] ) && is_numeric( $meta['filesize'] ) ? max( 0, (int) $meta['filesize'] ) : null, 'media_title'=>get_the_title( $id ), 'upload_date'=>get_post_field( 'post_date', $id ) );
	}

	/** Resolve metadata from actual source posts, excluding inaccessible entries.
	 * @param int $post_id Output's source post.
	 * @param array $attrs Trusted saved output attributes.
	 * @return array Rows and source-post count for a helpful empty state.
	 */
	public static function rows( int $post_id, array $attrs ): array {
		$rows = array(); $sources = array( get_post( $post_id ) );
		if ( 'posts' === ( $attrs['source'] ?? '' ) ) {
			$type = sanitize_key( $attrs['post_type'] ?? 'post' );
			if ( ! isset( Settings::types()[ $type ] ) ) { return array( array(), 0 ); }
			// Read in batches so large archives do not create a single unbounded metadata query.
			$sources = array(); $page = 1;
			do {
				$query = new \WP_Query( array( 'post_type'=>$type, 'post_status'=>'publish', 'posts_per_page'=>200, 'paged'=>$page++, 'orderby'=>'ID', 'order'=>'ASC', 'no_found_rows'=>true, 'update_post_term_cache'=>false ) );
				foreach ( $query->posts as $source ) { if ( Presenter::readable( $source->ID ) ) { $sources[] = $source; } }
			} while ( 200 === count( $query->posts ) );
		}
		$assignments = array(); $attachment_ids = array();
		foreach ( $sources as $source ) {
			if ( ! $source || ! Presenter::readable( $source->ID ) ) { continue; }
			$entries = 'custom' === ( $attrs['source'] ?? '' ) ? Documents::clean( $attrs['documents'] ?? array(), array(), 500 ) : Documents::get( $source->ID );
			if ( is_wp_error( $entries ) ) { continue; }
			$assignments[ $source->ID ] = $entries;
			$attachment_ids = array_merge( $attachment_ids, array_map( 'absint', array_column( $entries, 'pdf' ) ), array_map( 'absint', array_column( $entries, 'cover' ) ) );
		}
		foreach ( array_chunk( array_unique( array_filter( $attachment_ids ) ), 200 ) as $batch ) { _prime_post_caches( $batch, false, true ); }
		foreach ( $sources as $source ) {
			if ( ! $source || ! isset( $assignments[ $source->ID ] ) ) { continue; }
			$entries = $assignments[ $source->ID ];
			$first = 'posts' === ( $attrs['source'] ?? '' ) && 'all' !== ( $attrs['per_post'] ?? 'first' );
			foreach ( $entries as $entry ) {
				$id = absint( $entry['pdf'] ?? 0 );
				if ( ! Presenter::valid_pdf( $id ) ) { continue; }
				$title = $entry['title'] ?? '';
				if ( '' === $title ) { $title = count( $entries ) > 1 && ! $first ? Documents::title( $id ) : get_the_title( $source ); }
				$entry['title'] = $title;
				$rows[] = array_merge( self::media( $id ), array( 'entry'=>$entry, 'post_id'=>$source->ID, 'title'=>$title, 'post_title'=>get_the_title( $source ), 'date'=>$source->post_date, 'menu_order'=>(int) $source->menu_order, 'manual'=>count( $rows ) ) );
				if ( $first ) { break; }
			}
		}
		return array( $rows, count( $sources ) );
	}

	/** Get one scalar GET value for an explicitly named output instance.
	 * @param string $name Parameter name.
	 * @param string $default Default for absent or malformed input.
	 * @return string Sanitized scalar value.
	 */
	private static function input( string $name, string $default = '' ): string {
		// Read-only filtering does not mutate state and requires no nonce.
		return isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET[ $name ] ) ), 0, 180 ) : $default; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query.
	}

	/** Apply visitor filters and stable sorting to the complete accessible dataset.
	 * @param array $rows Metadata rows.
	 * @param array $attrs Saved defaults.
	 * @param string $id Unique collection key.
	 * @return array Filtered, sorted rows before pagination.
	 */
	public static function select( array $rows, array $attrs, string $id ): array {
		$prefix = 'bpp_' . $id . '_';
		$term = remove_accents( mb_strtolower( self::input( $prefix . 'q' ) ) );
		$size = self::input( $prefix . 'size' );
		$fields = array_intersect( explode( ',', (string) ( $attrs['search_fields'] ?? 'title,post_title,media_title,filename' ) ), array( 'title','post_title','media_title','filename' ) );
		$rows = array_values( array_filter( $rows, static function( $row ) use ( $term, $size, $fields ) {
			if ( '' !== $term && ! str_contains( remove_accents( mb_strtolower( implode( ' ', array_intersect_key( $row, array_flip( $fields ) ) ) ) ), $term ) ) { return false; }
			return match ( $size ) { 'small'=>$row['size'] !== null && $row['size'] < 1048576, 'medium'=>$row['size'] !== null && $row['size'] >= 1048576 && $row['size'] < 5242880, 'large'=>$row['size'] !== null && $row['size'] >= 5242880, 'unknown'=>$row['size'] === null, default=>true };
		} ) );
		$default = ! empty( $attrs['orderby'] ) ? $attrs['orderby'] : ( 'posts' === ( $attrs['source'] ?? '' ) ? 'date' : 'manual' );
		$sort = self::input( $prefix . 'sort', (string) $default ) ?: (string) $default;
		if ( ! isset( self::sorts()[ $sort ] ) ) { $sort = 'manual'; }
		$order = self::input( $prefix . 'order' ) ?: ( ! empty( $attrs['order'] ) ? (string) $attrs['order'] : ( 'date' === $sort ? 'DESC' : 'ASC' ) );
		$sign = 'DESC' === strtoupper( $order ) ? -1 : 1;
		usort( $rows, static function( $a, $b ) use ( $sort, $sign ) {
			// Unknown sizes remain last in either direction.
			if ( 'size' === $sort && ( null === $a['size'] || null === $b['size'] ) ) { return ( null === $a['size'] ) <=> ( null === $b['size'] ); }
			$cmp = in_array( $sort, array( 'size','manual','menu_order' ), true ) ? $a[ $sort ] <=> $b[ $sort ] : strnatcasecmp( remove_accents( (string) $a[ $sort ] ), remove_accents( (string) $b[ $sort ] ) );
			return $cmp ? $sign * $cmp : $a['manual'] <=> $b['manual'];
		} );
		return $rows;
	}

	/** Render a complete filtered collection and server-side pagination.
	 * @param int $post_id Source post.
	 * @param array $attrs Saved output attributes.
	 * @return string Escaped markup.
	 */
	public static function output( int $post_id, array $attrs ): string {
		$id = substr( sanitize_key( $attrs['collection_id'] ?? '' ), 0, 64 ) ?: wp_unique_id( 'collection-' );
		[ $rows, $source_count ] = self::rows( $post_id, $attrs );
		$rows = self::select( $rows, $attrs, $id ); $total = count( $rows );
		$per_page = max( 1, min( 500, absint( $attrs['page_size'] ?? 0 ) ?: absint( $attrs['limit'] ?? 500 ) ) );
		$page_key = 'bpp_' . $id . '_page'; $pages = max( 1, (int) ceil( $total / $per_page ) );
		$page = max( 1, min( $pages, absint( self::input( $page_key, '1' ) ) ) ); $html = '';
		foreach ( array_slice( $rows, ( $page - 1 ) * $per_page, $per_page ) as $row ) { $html .= Presenter::card( $row['post_id'], absint( $row['entry']['pdf'] ), $attrs, $row['entry'] ); }
		if ( ! $html ) {
			$text = $total ? '' : __( 'No matching PDFs. Try another search or reset the filters.', 'builder-pdf-presenter' );
			if ( '' === self::input( 'bpp_' . $id . '_q' ) && '' === self::input( 'bpp_' . $id . '_size' ) ) { $text = $source_count ? __( 'No PDFs assigned yet. Add documents to the source posts.', 'builder-pdf-presenter' ) : __( 'No matching source posts found.', 'builder-pdf-presenter' ); }
			$html = '<p class="bpp-empty">' . esc_html( $text ) . '</p>';
		}
		/* translators: %d: number of accessible PDF documents after filtering. */
		$count_label = sprintf( __( 'Documents: %d', 'builder-pdf-presenter' ), $total );
		$out = '<section class="bpp-library" id="bpp-' . esc_attr( $id ) . '" aria-label="' . esc_attr__( 'PDF collection', 'builder-pdf-presenter' ) . '">' . ( '0' !== (string) ( $attrs['show_count'] ?? '1' ) ? '<p class="bpp-result-count" role="status">' . esc_html( $count_label ) . '</p>' : '' ) . Presenter::grid( $html, Settings::resolve( $post_id, $attrs ) );
		if ( $pages > 1 ) {
			$out .= '<nav class="bpp-pagination" aria-label="' . esc_attr__( 'PDF result pages', 'builder-pdf-presenter' ) . '">';
			$numbers = array();
			foreach ( array_unique( array_merge( array( 1,$pages ), range( max( 1, $page - 2 ), min( $pages, $page + 2 ) ) ) ) as $number ) { $numbers[] = $number; }
			sort( $numbers );
			foreach ( $numbers as $number ) { $out .= '<a href="' . esc_url( add_query_arg( $page_key, $number ) . '#bpp-' . $id ) . '"' . ( $number === $page ? ' aria-current="page"' : '' ) . '>' . absint( $number ) . '</a>'; }
			$out .= '</nav>';
		}
		return $out . '</section>';
	}

	/** Allow only the fixed form vocabulary generated by this plugin.
	 * @return array Safe markup for builder rendering.
	 */
	public static function allowed_forms(): array {
		$allowed = wp_kses_allowed_html( 'post' );
		foreach ( array( 'form','label','input','select','option','button' ) as $tag ) { $allowed[ $tag ] = array_fill_keys( array( 'class','id','method','action','type','name','value','maxlength','selected','for','aria-label','disabled','aria-disabled','tabindex' ), true ); }
		return $allowed;
	}

	/** Render a reusable GET filter form targeting an explicit collection ID.
	 * @param array|string $attrs Shortcode or block target and optional controls/layout attributes.
	 * @return string Accessible form; no PDF content search.
	 */
	public static function filters( $attrs ): string {
		$attrs = is_array( $attrs ) ? $attrs : array();
		$id = substr( sanitize_key( $attrs['target'] ?? '' ), 0, 64 );
		if ( ! $id ) { return current_user_can( 'edit_posts' ) ? '<p class="bpp-empty">' . esc_html__( 'Choose a target collection ID for PDF filters.', 'builder-pdf-presenter' ) . '</p>' : ''; }
		wp_enqueue_style( 'bpp-frontend' );
		$prefix = 'bpp_' . $id . '_'; $vertical = 'vertical' === ( $attrs['layout'] ?? '' );
		$permalink = get_permalink() ?: home_url( '/' ); $query = array();
		wp_parse_str( (string) wp_parse_url( $permalink, PHP_URL_QUERY ), $query );
		$query = array_merge( $query, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query preservation.
		ob_start(); ?>
		<form class="bpp-filters<?php echo $vertical ? ' bpp-filters-vertical' : ''; ?>" method="get" action="<?php echo esc_url( strtok( $permalink, '?' ) . '#bpp-' . $id ); ?>">
		<?php foreach ( $query as $key=>$value ) { if ( is_string( $key ) && is_string( $value ) && ! str_starts_with( $key, $prefix ) ) { ?><input type="hidden" name="<?php echo esc_attr( sanitize_key( $key ) ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $value ) ) ); ?>"><?php } } // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve read-only query state. ?>
		<?php if ( '0' !== (string) ( $attrs['show_search'] ?? '1' ) ) : ?><label><?php esc_html_e( 'Search titles and file names', 'builder-pdf-presenter' ); ?><input type="search" name="<?php echo esc_attr( $prefix . 'q' ); ?>" maxlength="180" value="<?php echo esc_attr( self::input( $prefix . 'q' ) ); ?>"></label><?php endif; ?>
		<?php if ( '0' !== (string) ( $attrs['show_sort'] ?? '1' ) ) : ?><label><?php esc_html_e( 'Sort by', 'builder-pdf-presenter' ); ?><select name="<?php echo esc_attr( $prefix . 'sort' ); ?>"><option value=""><?php esc_html_e( 'Collection default', 'builder-pdf-presenter' ); ?></option><?php foreach ( self::sorts() as $key=>$label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( self::input( $prefix . 'sort' ), $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Direction', 'builder-pdf-presenter' ); ?><select name="<?php echo esc_attr( $prefix . 'order' ); ?>"><option value=""><?php esc_html_e( 'Collection default', 'builder-pdf-presenter' ); ?></option><option value="ASC" <?php selected( self::input( $prefix . 'order' ), 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'builder-pdf-presenter' ); ?></option><option value="DESC" <?php selected( self::input( $prefix . 'order' ), 'DESC' ); ?>><?php esc_html_e( 'Descending', 'builder-pdf-presenter' ); ?></option></select></label><?php endif; ?>
		<?php if ( '0' !== (string) ( $attrs['show_size_filter'] ?? '1' ) ) : ?><label><?php esc_html_e( 'File size', 'builder-pdf-presenter' ); ?><select name="<?php echo esc_attr( $prefix . 'size' ); ?>"><?php foreach ( array( ''=>__( 'Any size', 'builder-pdf-presenter' ), 'small'=>__( 'Under 1 MiB', 'builder-pdf-presenter' ), 'medium'=>__( '1 to under 5 MiB', 'builder-pdf-presenter' ), 'large'=>__( '5 MiB or more', 'builder-pdf-presenter' ), 'unknown'=>__( 'Size unknown', 'builder-pdf-presenter' ) ) as $key=>$label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( self::input( $prefix . 'size' ), $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
		<button type="submit" class="bpp-button"><?php esc_html_e( 'Apply filters', 'builder-pdf-presenter' ); ?></button><?php if ( '0' !== (string) ( $attrs['show_reset'] ?? '1' ) ) : ?><a class="bpp-filter-reset" href="<?php echo esc_url( remove_query_arg( array_map( static fn( $key )=>$prefix . $key, array( 'q','size','sort','order','page' ) ) ) . '#bpp-' . $id ); ?>"><?php esc_html_e( 'Reset', 'builder-pdf-presenter' ); ?></a><?php endif; ?><small><?php esc_html_e( 'Searches document metadata, not PDF contents. Filters apply to the whole collection before pagination.', 'builder-pdf-presenter' ); ?></small></form>
		<?php return (string) ob_get_clean();
	}
}
