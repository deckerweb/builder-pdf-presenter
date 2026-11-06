<?php
/** Native Gutenberg starter layouts without a page builder dependency. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Registers editable collection and query-loop starters using native block markup. */
final class Patterns {
	/** Register the pattern category and the layouts available in the inserter.
	 * @return void
	 */
	public static function boot(): void {
		register_block_pattern_category( 'builder-pdf-presenter', array( 'label' => 'Builder PDF Presenter' ) );
		foreach ( array( 2, 3, 4 ) as $columns ) {
			register_block_pattern( 'builder-pdf-presenter/collection-' . $columns, array(
				/* translators: %d: number of desktop columns. */
				'title' => sprintf( __( 'PDF collection: %d columns', 'builder-pdf-presenter' ), $columns ),
				'categories' => array( 'builder-pdf-presenter', 'gallery' ), 'description' => __( 'A responsive document layout with lightweight covers and an on-demand reader. Select PDFs after inserting.', 'builder-pdf-presenter' ), 'keywords' => array( 'PDF', __( 'Documents', 'builder-pdf-presenter' ), __( 'Archive', 'builder-pdf-presenter' ) ), 'blockTypes' => array( 'builder-pdf-presenter/document' ), 'viewportWidth' => 1200,
				'content' => '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2 class="wp-block-heading">' . esc_html__( 'Documents and publications', 'builder-pdf-presenter' ) . '</h2><!-- /wp:heading --><!-- wp:builder-pdf-presenter/document {"source":"custom","columns":"' . $columns . '","design":"cover"} /--></div><!-- /wp:group -->',
			) );
		}
		foreach ( array( 'compact' => __( 'Compact PDF cards', 'builder-pdf-presenter' ), 'list' => __( 'PDF document list', 'builder-pdf-presenter' ) ) as $design => $title ) {
			register_block_pattern( 'builder-pdf-presenter/' . $design, array( 'title' => $title, 'categories' => array( 'builder-pdf-presenter', 'gallery' ), 'description' => __( 'A responsive document layout with lightweight covers and an on-demand reader. Select PDFs after inserting.', 'builder-pdf-presenter' ), 'keywords' => array( 'PDF', __( 'Documents', 'builder-pdf-presenter' ), __( 'Archive', 'builder-pdf-presenter' ) ), 'blockTypes' => array( 'builder-pdf-presenter/document' ), 'viewportWidth' => 1200, 'content' => '<!-- wp:builder-pdf-presenter/document {"source":"custom","design":"' . $design . '","columns":"2"} /-->' ) );
		}
		register_block_pattern( 'builder-pdf-presenter/post-library', array( 'title' => __( 'Document contributions: query grid', 'builder-pdf-presenter' ), 'categories' => array( 'builder-pdf-presenter', 'gallery' ), 'description' => __( 'A responsive document layout with lightweight covers and an on-demand reader. Select PDFs after inserting.', 'builder-pdf-presenter' ), 'keywords' => array( 'PDF', __( 'Documents', 'builder-pdf-presenter' ), __( 'Archive', 'builder-pdf-presenter' ) ), 'blockTypes' => array( 'builder-pdf-presenter/document' ), 'viewportWidth' => 1200, 'content' => '<!-- wp:query {"query":{"perPage":12,"postType":"post","order":"desc","orderBy":"date","inherit":false}} --><div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} --><!-- wp:builder-pdf-presenter/document {"show_title":"1"} /--><!-- /wp:post-template --><!-- wp:query-pagination --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-numbers /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination --><!-- wp:query-no-results --><!-- wp:paragraph --><p>' . esc_html__( 'No document contributions found.', 'builder-pdf-presenter' ) . '</p><!-- /wp:paragraph --><!-- /wp:query-no-results --></div><!-- /wp:query -->' ) );
		foreach ( array( 'horizontal', 'vertical' ) as $layout ) {
			$id = 'library-' . substr( wp_generate_uuid4(), 0, 8 );
			$filter = '<!-- wp:builder-pdf-presenter/filters ' . wp_json_encode( array( 'target'=>$id,'layout'=>$layout ) ) . ' /-->';
			$collection = '<!-- wp:builder-pdf-presenter/document ' . wp_json_encode( array( 'source'=>'custom','collection_id'=>$id,'columns'=>'3' ) ) . ' /-->';
			$content = 'horizontal' === $layout ? $filter . $collection : '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"28%"} --><div class="wp-block-column" style="flex-basis:28%">' . $filter . '</div><!-- /wp:column --><!-- wp:column {"width":"72%"} --><div class="wp-block-column" style="flex-basis:72%">' . $collection . '</div><!-- /wp:column --></div><!-- /wp:columns -->';
			register_block_pattern( 'builder-pdf-presenter/filters-' . $layout, array( 'title'=>'horizontal' === $layout ? __( 'PDF library with filter bar', 'builder-pdf-presenter' ) : __( 'PDF library with sidebar filters', 'builder-pdf-presenter' ),'categories'=>array( 'builder-pdf-presenter','gallery' ),'description'=>__( 'A named PDF collection with matching search and filter controls. Add PDFs after inserting.', 'builder-pdf-presenter' ),'viewportWidth'=>1200,'keywords'=>array( 'PDF', __( 'Search', 'builder-pdf-presenter' ), __( 'Archive', 'builder-pdf-presenter' ) ),'blockTypes'=>array( 'builder-pdf-presenter/document','builder-pdf-presenter/filters' ),'content'=>$content ) );
		}

	}
}
