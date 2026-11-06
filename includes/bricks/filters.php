<?php
/** Bricks filter controls for plugin-owned PDF collections. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Uses the same server-side metadata filters as the Block Editor and shortcode. */
final class Bricks_Filters extends \Bricks\Element {
	/** @var string Stable element name. */
	public $name = 'bpp-filters';
	/** @var string Native picker category. */
	public $category = 'bpp';
	/** @var string Native filter glyph. */
	public $icon = 'ti-filter';
	/** Return element label. @return string Translated label. */
	public function get_label() { return __( 'PDF filters and search', 'builder-pdf-presenter' ); }
	/** Set native, component-compatible controls. @return void */
	public function set_controls() {
		$this->controls['filterTypography'] = array( 'tab'=>'style','type'=>'typography','label'=>__( 'Typography', 'builder-pdf-presenter' ),'css'=>array( array( 'property'=>'font','selector'=>'.bpp-filters label' ) ) );
		$this->controls['filterBackground'] = array( 'tab'=>'style','type'=>'color','label'=>__( 'Card background', 'builder-pdf-presenter' ),'css'=>array( array( 'property'=>'background-color','selector'=>'.bpp-filters' ) ) );
		$this->controls['target'] = array( 'tab'=>'content','type'=>'text','label'=>__( 'Target collection ID', 'builder-pdf-presenter' ),'description'=>__( 'Targets a plugin PDF collection. For native post query loops, use native Bricks filters instead.', 'builder-pdf-presenter' ) );
		$this->controls['layout'] = array( 'tab'=>'content','type'=>'select','label'=>__( 'Layout', 'builder-pdf-presenter' ),'options'=>array( 'horizontal'=>__( 'Horizontal', 'builder-pdf-presenter' ),'vertical'=>__( 'Vertical', 'builder-pdf-presenter' ) ),'default'=>'horizontal' );
		foreach ( array( 'show_reset'=>__( 'Show reset link', 'builder-pdf-presenter' ), 'show_search'=>__( 'Search', 'builder-pdf-presenter' ),'show_sort'=>__( 'Sorting', 'builder-pdf-presenter' ),'show_size_filter'=>__( 'File size filter', 'builder-pdf-presenter' ) ) as $key=>$label ) {
			$this->controls[ $key ] = array( 'tab'=>'content','label'=>$label,'type'=>'select','options'=>array( '1'=>__( 'Show / enable', 'builder-pdf-presenter' ),'0'=>__( 'Hide / disable', 'builder-pdf-presenter' ) ),'default'=>'1' );
		}
	}
	/** Render safely through the shared filter form. @return void */
	public function render() {
		$builder = ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) || ( function_exists( 'bricks_is_builder_call' ) && bricks_is_builder_call() );
		$this->set_attribute( '_root','class', $builder ? 'bpp-bricks-filters bpp-builder-preview' : 'bpp-bricks-filters' );
		$html = Library::filters( $this->settings );
		if ( $builder ) { $tags = new \WP_HTML_Tag_Processor( $html ); while ( $tags->next_tag() ) { if ( in_array( $tags->get_tag(), array( 'INPUT','SELECT','BUTTON' ), true ) ) { $tags->set_attribute( 'disabled', true ); } if ( 'A' === $tags->get_tag() ) { $tags->set_attribute( 'tabindex', '-1' ); $tags->set_attribute( 'aria-disabled', 'true' ); } } $html = $tags->get_updated_html(); }

		echo '<div ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Native Bricks escaped attributes.
		echo wp_kses( $html, Library::allowed_forms() );
		echo '</div>';
	}
	/** Queue shared card/filter styles. @return void */
	public function enqueue_scripts() { wp_enqueue_style( 'bpp-frontend' ); }
}
