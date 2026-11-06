<?php
/** Gutenberg-local style values; no global design settings are stored. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Converts optional block styles into narrowly scoped CSS variables. */
final class Block_Styles {
	/** Return allowlisted CSS variables for the native block wrapper.
	 * @param array $attrs Saved block attributes.
	 * @return string Sanitized inline declarations, escaped by the wrapper API.
	 */
	public static function variables( array $attrs ): string {
		$vars = array();
		$custom = is_array( $attrs['cardStyles'] ?? null ) ? $attrs['cardStyles'] : array();
		$map = array( 'titleSize'=>'title-size', 'titleWeight'=>'title-weight', 'titleLineHeight'=>'title-line-height', 'titleColor'=>'title-color', 'metaSize'=>'meta-size', 'metaColor'=>'meta-color', 'buttonSize'=>'button-size', 'buttonColor'=>'button-color', 'buttonBackground'=>'button-background', 'downloadColor'=>'download-color', 'downloadBackground'=>'download-background', 'cardBackground'=>'card-background', 'cardRadius'=>'card-radius', 'cardPadding'=>'card-padding', 'borderColor'=>'border-color' );
		foreach ( $map as $key => $suffix ) {
			$value = $custom[ $key ] ?? '';
			if ( is_string( $value ) && preg_match( '/^(?:#[a-fA-F0-9]{3,8}|[0-9.]+(?:px|em|rem|%)?|var\(--[a-zA-Z0-9_-]+\)|(?:rgb|rgba|hsl|hsla)\([0-9.,%\s]+\)|transparent)$/D', $value ) ) { $vars[] = '--bpp-' . $suffix . ':' . $value; }
		}
		$size = $attrs['style']['typography']['fontSize'] ?? '';
		if ( ! empty( $attrs['fontSize'] ) ) { $size = 'var(--wp--preset--font-size--' . sanitize_title( $attrs['fontSize'] ) . ')'; }
		if ( is_string( $size ) && preg_match( '/^(?:[0-9.]+(?:px|em|rem|%)|var\(--[a-zA-Z0-9_-]+\))$/D', $size ) ) { $vars[] = '--bpp-base-size:' . $size; }
		foreach ( array( 'text'=>'text-color', 'background'=>'card-background' ) as $key => $suffix ) {
			$value = $attrs['style']['color'][ $key ] ?? '';
			$preset = $attrs[ $key . 'Color' ] ?? '';
			if ( is_string( $preset ) && $preset ) { $value = 'var(--wp--preset--color--' . sanitize_title( $preset ) . ')'; }
			if ( is_string( $value ) && preg_match( '/^(?:#[a-fA-F0-9]{3,8}|var\(--[a-zA-Z0-9_-]+\)|(?:rgb|rgba|hsl|hsla)\([0-9.,%\s]+\))$/D', $value ) ) { $vars[] = '--bpp-' . $suffix . ':' . $value; if ( 'text' === $key && empty( $custom['metaColor'] ) ) { $vars[] = '--bpp-meta-color:' . $value; } }
		}
		$gap = $attrs['style']['spacing']['blockGap'] ?? '';
		if ( is_string( $gap ) && str_starts_with( $gap, 'var:preset|spacing|' ) ) { $gap = 'var(--wp--preset--spacing--' . sanitize_title( substr( $gap, 19 ) ) . ')'; }
		if ( is_string( $gap ) && preg_match( '/^(?:[0-9.]+(?:px|em|rem|%)|var\(--[a-zA-Z0-9_-]+\))$/D', $gap ) ) { $vars[] = '--bpp-grid-gap:' . $gap; }
		$line = $attrs['style']['typography']['lineHeight'] ?? '';
		if ( empty( $custom['titleLineHeight'] ) && is_scalar( $line ) && preg_match( '/^[0-9.]+(?:em|px|%)?$/D', (string) $line ) ) { $vars[] = '--bpp-title-line-height:' . $line; }
		return implode( ';', $vars );
	}
}
