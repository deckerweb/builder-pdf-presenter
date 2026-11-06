<?php
/** Native multi-document element without an additional post query. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Displays the ordered collection assigned to the source post. */
final class Bricks_Collection extends Bricks_Element {
	/** @var string Stable element name. */
	public $name = 'bpp-collection';
	/** @var string Distinct collection glyph in the native element picker. */
	public $icon = 'bpp-icon-collection';
	/** Return the localized element label. @return string Label. */
	public function get_label() { return __( 'PDF collection', 'builder-pdf-presenter' ); }
}
