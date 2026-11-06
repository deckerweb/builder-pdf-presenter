<?php
/** Native single-document element for Bricks query loops and templates. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Displays one document from the current post or an explicit attachment. */
final class Bricks_Document extends Bricks_Element {
	/** @var string Stable element name. */
	public $name = 'bpp-document';
	/** Return the localized element label. @return string Label. */
	public function get_label() { return __( 'PDF document', 'builder-pdf-presenter' ); }
}
