<?php
/** Read-only conversion planning for the free PDF Embedder content formats. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Supplies one adapter to the existing explicit migration and recovery workflow. */
final class Embedder_Migration {

	/** Resolve a local, ordinary PDF without fetching or exposing protected files.
	 * @param array $attributes Original block or shortcode attributes.
	 * @return int|\WP_Error Verified attachment ID or a fixed skip reason.
	 */
	private static function resolve( array $attributes ) {
		$url = $attributes['url'] ?? '';
		if ( ! is_string( $url ) || preg_match( '~(?:securepdfs|%2fsecurepdfs|%252fsecurepdfs)~i', $url ) ) { return new \WP_Error( 'embedder_protected' ); }
		$id = absint( $attributes['pdfID'] ?? $attributes['pdfid'] ?? 0 );
		if ( $url ) {
			$resolved = Migration::resolve( array( 'attrs' => array( 'file' => $url ) ) );
			if ( ! $resolved || ( $id && $id !== $resolved ) ) { return new \WP_Error( 'embedder_unresolved' ); }
			$id = $resolved;
		}
		$file = $id ? get_attached_file( $id ) : '';
		if ( ! Presenter::valid_pdf( $id ) || ! $file || ! is_file( $file ) ) { return new \WP_Error( 'embedder_unresolved' ); }
		if ( preg_match( '~(?:^|/)securepdfs(?:/|$)~i', str_replace( '\\', '/', $file ) ) ) { return new \WP_Error( 'embedder_protected' ); }
		return $id;
	}

	/** Plan conversions without writing posts, settings, files or recovery data.
	 * A contribution is atomic: one unsupported reference leaves all its content intact.
	 * @param string $content Original contribution content.
	 * @return array Plan with content, ordered unique IDs, reference count and error code.
	 */
	public static function plan( string $content ): array {
		$ids = array(); $references = 0; $error = ''; $unmapped = false;
		$convert = static function( array $attributes ) use ( &$ids, &$references, &$error, &$unmapped ): int {
			++$references;
			$id = self::resolve( $attributes );
			if ( is_wp_error( $id ) ) { $error = $error ?: $id->get_error_code(); return 0; }
			$ids[] = $id;
			foreach ( $attributes as $key => $value ) { if ( ! in_array( strtolower( (string) $key ), array( 'url', 'pdfid' ), true ) ) { $unmapped = true; } }
			return $id;
		};
		$shortcodes = static function( string $html ) use ( $convert ): string {
			// Preserve examples, comments, markup attributes and script/style content.
			$chunks = preg_split( '~(<(?:pre|code|script|style)\b[^>]*>.*?</(?:pre|code|script|style)>|<!--.*?-->)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
			foreach ( $chunks as $index => &$chunk ) {
				if ( 1 === $index % 2 ) { continue; }
				$tokens = wp_html_split( $chunk );
				foreach ( $tokens as &$token ) {
					if ( str_starts_with( $token, '<' ) ) { continue; }
					$token = preg_replace_callback( '/' . get_shortcode_regex( array( 'pdf-embedder' ) ) . '/s', static function( array $match ) use ( $convert ): string {
						if ( '[' === $match[1] && ']' === $match[6] ) { return $match[0]; }
						$attributes = shortcode_parse_atts( $match[3] );
						$id = $convert( is_array( $attributes ) ? $attributes : array() );
						if ( ! $id ) { return $match[0]; }
						// Keep nested/enclosed content exactly where the source renderer placed it.
						return '[builder_pdf_presenter pdf_id="' . $id . '"]' . ( $match[5] ?? '' );
					}, $token );
				}
				unset( $token );
				$chunk = implode( '', $tokens );
			}
			unset( $chunk );
			return implode( '', $chunks );
		};
		$walk = static function( array &$blocks ) use ( &$walk, $convert, $shortcodes ): void {
			foreach ( $blocks as &$block ) {
				$name = $block['blockName'] ?? null;
				if ( 'pdfemb/pdf-embedder-viewer' === $name ) {
					$id = $convert( $block['attrs'] ?? array() );
					if ( $id ) { $block = array( 'blockName' => 'builder-pdf-presenter/document', 'attrs' => array( 'attachmentId' => $id ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ); }
					continue;
				}
				if ( in_array( $name, array( 'core/code', 'core/preformatted', 'core/html' ), true ) ) { continue; }
				if ( ! empty( $block['innerBlocks'] ) ) { $walk( $block['innerBlocks'] ); }
				// Text fragments are processed separately so nested block placeholders survive.
				foreach ( $block['innerContent'] as &$part ) { if ( is_string( $part ) ) { $part = $shortcodes( $part ); } }
				unset( $part );
				$block['innerHTML'] = implode( '', array_filter( $block['innerContent'], 'is_string' ) );
			}
		};
		$blocks = parse_blocks( $content ); $walk( $blocks );
		return array( 'content' => $error ? $content : serialize_blocks( $blocks ), 'ids' => array_values( array_unique( $ids ) ), 'references' => $references, 'error' => $error, 'unmapped' => $unmapped );
	}
}
