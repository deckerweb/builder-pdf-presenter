<?php
/** Shared native Bricks controls. No Bricks Extras code is distributed. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Uses Bricks control/CSS generation, conditions and component property machinery. */
abstract class Bricks_Element extends \Bricks\Element {
	/** @var string Element picker category. */
	public $category = 'bpp';
	/** @var string Native icon-font fallback. */
	public $icon = 'bpp-icon-document';
	/** @var string Default wrapper element. */
	public $tag = 'div';
	/** Return searchable keywords. @return array Search terms. */
	public function get_keywords() { return array( 'pdf', 'document', 'reader', 'collection', 'presenter' ); }

	/** Define content and style control groups. @return void */
	public function set_control_groups() {
		foreach ( array( 'document'=>__( 'Document source', 'builder-pdf-presenter' ), 'display'=>__( 'Preview and document card', 'builder-pdf-presenter' ), 'actions'=>__( 'Reading and download buttons', 'builder-pdf-presenter' ) ) as $key=>$title ) { $this->control_groups[ $key ] = array( 'title'=>$title, 'tab'=>'content' ); }
		foreach ( array( 'card_style'=>__( 'Document card', 'builder-pdf-presenter' ), 'title_style'=>__( 'Title', 'builder-pdf-presenter' ), 'meta_style'=>__( 'File details', 'builder-pdf-presenter' ), 'button_style'=>__( 'Buttons', 'builder-pdf-presenter' ) ) as $key=>$title ) { $this->control_groups[ $key ] = array( 'title'=>$title, 'tab'=>'style' ); }
	}

	/** Register native controls, which also support responsive/global-class styles.
	 * @return void
	 */
	public function set_controls() {
		$this->controls['sourcePost'] = array( 'tab'=>'content', 'group'=>'document', 'label'=>__( 'Source post ID (optional)', 'builder-pdf-presenter' ), 'type'=>'text', 'hasDynamicData'=>true, 'description'=>__( 'Leave empty to use the first PDF assigned to the current post, or to each post inside a query loop. This is a real document, not sample content.', 'builder-pdf-presenter' ) );
		if ( 'bpp-document' === $this->name ) { $this->controls['pdfFile'] = array( 'tab'=>'content', 'group'=>'document', 'label'=>__( 'Select PDF', 'builder-pdf-presenter' ), 'type'=>'file' ); $this->controls['pdfId'] = array( 'tab'=>'content', 'group'=>'document', 'label'=>__( 'PDF attachment ID (optional)', 'builder-pdf-presenter' ), 'type'=>'text', 'hasDynamicData'=>true ); }
		if ( 'bpp-collection' === $this->name ) {
			foreach ( array( 'collection_id'=>__( 'Collection ID', 'builder-pdf-presenter' ), 'post_type'=>__( 'Content type slug', 'builder-pdf-presenter' ), 'page_size'=>__( 'PDFs per page', 'builder-pdf-presenter' ), 'search_fields'=>__( 'Search fields', 'builder-pdf-presenter' ) ) as $key=>$label ) { $this->controls[ $key ] = array( 'tab'=>'content','group'=>'document','type'=>'text','label'=>$label ); }
			$this->controls['show_count'] = array( 'tab'=>'content','group'=>'display','type'=>'select','label'=>__( 'Show result count', 'builder-pdf-presenter' ),'options'=>array( '1'=>__( 'Show / enable', 'builder-pdf-presenter' ),'0'=>__( 'Hide / disable', 'builder-pdf-presenter' ) ) );
			$this->controls['source'] = array( 'tab'=>'content','group'=>'document','type'=>'select','label'=>__( 'Document source', 'builder-pdf-presenter' ),'options'=>array( 'post'=>__( 'Documents assigned to this contribution', 'builder-pdf-presenter' ),'posts'=>__( 'Query document contributions', 'builder-pdf-presenter' ) ),'default'=>'post' );
			$this->controls['per_post'] = array( 'tab'=>'content','group'=>'document','type'=>'select','label'=>__( 'PDFs per source post', 'builder-pdf-presenter' ),'options'=>array( 'first'=>__( 'First available PDF', 'builder-pdf-presenter' ),'all'=>__( 'All assigned PDFs', 'builder-pdf-presenter' ) ),'default'=>'first' );
			$this->controls['orderby'] = array( 'tab'=>'content','group'=>'document','type'=>'select','label'=>__( 'Output sorting', 'builder-pdf-presenter' ),'options'=>Library::sorts() );
			$this->controls['order'] = array( 'tab'=>'content','group'=>'document','type'=>'select','label'=>__( 'Direction', 'builder-pdf-presenter' ),'options'=>array( 'ASC'=>__( 'Ascending', 'builder-pdf-presenter' ),'DESC'=>__( 'Descending', 'builder-pdf-presenter' ) ) );
		}
		foreach ( Settings::schema() as $key=>$field ) {
			if ( 'reader_watermark' === $key && ! Watermark::exceptions_allowed() ) { continue; }
			if ( 'columns' === $key || 'design' === $key || 'ratio' === $key || str_starts_with( $key, 'show_' ) || 'cover_link' === $key ) { $group='display'; } else { $group='actions'; }
			if ( 'bpp-document' === $this->name && 'columns' === $key ) { continue; }
			$options = array( ''=>__( 'Inherit', 'builder-pdf-presenter' ) );
			if ( 'bool' === $field['type'] ) { $options += array( '1'=>__( 'Show / enable', 'builder-pdf-presenter' ), '0'=>__( 'Hide / disable', 'builder-pdf-presenter' ) ); } else { $options += $field['choices'] ?? array(); }
			$this->controls[ $key ] = array( 'tab'=>'content', 'group'=>$group, 'label'=>$field['label'], 'type'=>'text' === $field['type'] ? 'text' : 'select', 'options'=>$options, 'placeholder'=>__( 'Inherit', 'builder-pdf-presenter' ), 'hasDynamicData'=>'text' === $field['type'] );
		}
		foreach ( array( 'title'=>array( 'title_style','.bpp-card .bpp-title' ), 'meta'=>array( 'meta_style','.bpp-card .bpp-meta' ), 'button'=>array( 'button_style','.bpp-card .bpp-button' ) ) as $key=>$data ) {
			$this->controls[ $key . 'Typography' ] = array( 'tab'=>'style', 'group'=>$data[0], 'label'=>__( 'Typography', 'builder-pdf-presenter' ), 'type'=>'typography', 'css'=>array( array( 'property'=>'font', 'selector'=>$data[1] ) ) );
		}
		foreach ( array( 'cardBackground'=>array( 'card_style','background-color','.bpp-card' ), 'buttonBackground'=>array( 'button_style','background-color','.bpp-card .bpp-read' ), 'buttonColor'=>array( 'button_style','color','.bpp-card .bpp-read' ), 'downloadBackground'=>array( 'button_style','background-color','.bpp-card .bpp-download' ), 'downloadColor'=>array( 'button_style','color','.bpp-card .bpp-download' ) ) as $key=>$data ) {
			$labels = array( 'cardBackground'=>__( 'Card background', 'builder-pdf-presenter' ), 'buttonBackground'=>__( 'Reading button background', 'builder-pdf-presenter' ), 'buttonColor'=>__( 'Reading button color', 'builder-pdf-presenter' ), 'downloadBackground'=>__( 'Download button background', 'builder-pdf-presenter' ), 'downloadColor'=>__( 'Download button color', 'builder-pdf-presenter' ) );
			$this->controls[ $key ] = array( 'tab'=>'style', 'group'=>$data[0], 'label'=>$labels[ $key ], 'type'=>'color', 'css'=>array( array( 'property'=>$data[1], 'selector'=>$data[2] ) ) );
		}
		foreach ( array( 'cardPadding'=>array( 'spacing','padding','.bpp-card .bpp-info',__( 'Card padding', 'builder-pdf-presenter' ) ), 'cardBorder'=>array( 'border','border','.bpp-card',__( 'Card border', 'builder-pdf-presenter' ) ), 'cardShadow'=>array( 'box-shadow','box-shadow','.bpp-card',__( 'Card shadow', 'builder-pdf-presenter' ) ), 'gridGap'=>array( 'number','gap','.bpp-collection',__( 'Grid gap', 'builder-pdf-presenter' ) ) ) as $key=>$data ) {
			$this->controls[ $key ] = array( 'tab'=>'style', 'group'=>'card_style', 'label'=>$data[3], 'type'=>$data[0], 'units'=>true, 'css'=>array( array( 'property'=>$data[1], 'selector'=>$data[2] ) ) );
		}
	}

	/** Render current document data through the independent core.
	 * @return void
	 */
	public function render() {
		$s = $this->settings;
		$id = ! empty( $s['sourcePost'] ) ? absint( $this->render_dynamic_data( (string) $s['sourcePost'] ) ) : Bricks_Integration::post_id( absint( $this->post_id ) );
		if ( ! $id ) { return; }
		$builder = ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) || ( function_exists( 'bricks_is_builder_call' ) && bricks_is_builder_call() );
		$attrs = array_intersect_key( $s, array_flip( array( 'collection_id','post_type','source','per_post','orderby','order','page_size','search_fields','show_count' ) ) );
		foreach ( Settings::schema() as $key=>$field ) { if ( isset( $s[ $key ] ) && is_scalar( $s[ $key ] ) ) { $attrs[ $key ] = 'text' === $field['type'] ? $this->render_dynamic_data( (string) $s[ $key ] ) : $s[ $key ]; } }
		if ( 'bpp-document' === $this->name ) {
			$pdf = ! empty( $s['pdfId'] ) ? absint( $this->render_dynamic_data( (string) $s['pdfId'] ) ) : absint( $s['pdfFile']['id'] ?? 0 );
			$entries = Documents::get( $id );
			$html = $pdf ? Presenter::card( $id, $pdf, $attrs ) : ( $entries ? Presenter::card( $id, absint( $entries[0]['pdf'] ), $attrs, $entries[0] ) : '' );
		} else { if ( empty( $attrs['collection_id'] ) ) { $attrs['collection_id'] = 'bricks-' . $this->id; } $html = Presenter::output( $id, $attrs ); }
		if ( $builder && $html ) { $tags = new \WP_HTML_Tag_Processor( $html ); while ( $tags->next_tag( 'a' ) ) { $tags->set_attribute( 'tabindex', '-1' ); $tags->set_attribute( 'aria-disabled', 'true' ); } $html = $tags->get_updated_html(); }
		$this->set_attribute( '_root', 'class', $builder ? 'bpp-bricks bpp-builder-preview' : 'bpp-bricks' );
		$this->set_attribute( '_root', 'data-bpp-previous', __( 'Previous document', 'builder-pdf-presenter' ) );
		$this->set_attribute( '_root', 'data-bpp-next', __( 'Next document', 'builder-pdf-presenter' ) );
		if ( \Bricks\Query::is_looping() ) { $group = implode( '-', array( \Bricks\Query::get_query_element_id(), \Bricks\Query::get_query_element_instance_id(), $this->id ) ); $this->set_attribute( '_root', 'data-bpp-query', sanitize_key( $group ) ); }
		$allowed = array( 'div' => array_merge( wp_kses_allowed_html( 'post' )['div'], array( 'data-*'=>true, 'aria-*'=>true, 'role'=>true, 'tabindex'=>true ) ) );
		echo wp_kses( '<div ' . $this->render_attributes( '_root' ) . '>', $allowed );
		if ( $builder && 'bpp-document' === $this->name && empty( $pdf ) ) { echo '<p class="bpp-source-hint">' . esc_html__( 'Source: current or selected post · first assigned PDF', 'builder-pdf-presenter' ) . '</p>'; }
		if ( $html ) { echo wp_kses_post( $html ); } elseif ( $builder ) { $this->render_element_placeholder( array( 'title'=>__( 'No PDF assigned to this source. Add a PDF to the post or select a file in Document source.', 'builder-pdf-presenter' ) ) ); }
		echo '</div>';
	}

	/** Queue the small shared assets through the native element lifecycle.
	 * @return void
	 */
	public function enqueue_scripts() { wp_enqueue_style( 'bpp-frontend' ); wp_enqueue_script( 'bpp-frontend' ); }
}
