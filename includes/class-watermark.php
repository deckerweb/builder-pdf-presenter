<?php
/** Centrally managed, optional reader-only branding. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Keeps the watermark policy independent from output and builder overrides. */
final class Watermark {
	/** Validate the administrator's template without changing PDF files.
	 * @param mixed $input Untrusted settings.
	 * @return array Validated template.
	 */
	public static function sanitize( $input ): array {
		$a = is_array( $input ) ? $input : array();
		$logo = absint( $a['logo'] ?? 0 );
		if ( $logo && ( 'attachment' !== get_post_type( $logo ) || ! wp_attachment_is_image( $logo ) || ! in_array( get_post_mime_type( $logo ), array( 'image/png', 'image/jpeg', 'image/webp', 'image/gif' ), true ) ) ) { $logo = 0; }
		return array( 'enabled'=>! empty( $a['enabled'] ), 'allow_override'=>! empty( $a['allow_override'] ), 'text'=>mb_substr( sanitize_text_field( is_scalar( $a['text'] ?? '' ) ? (string) ( $a['text'] ?? '' ) : '' ), 0, 120 ), 'logo'=>$logo, 'position'=>in_array( $a['position'] ?? '', array( 'center', 'top', 'bottom' ), true ) ? $a['position'] : 'center', 'opacity'=>max( 5, min( 50, absint( $a['opacity'] ?? 15 ) ) ), 'size'=>max( 16, min( 72, absint( $a['size'] ?? 32 ) ) ) );
	}

	/** Check whether document-level switches should be offered. @return bool Whether exceptions are enabled. */
	public static function exceptions_allowed(): bool { $a = self::sanitize( Settings::stored()['watermark'] ?? array() ); return $a['enabled'] && $a['allow_override']; }

	/** Recheck current administrative policy even for previously signed reader links.
	 * @param array $options Effective document settings.
	 * @return array Active template or an empty array.
	 */
	public static function effective( array $options ): array {
		$a = self::sanitize( Settings::stored()['watermark'] ?? array() );
		if ( ! $a['enabled'] || ( ! $a['text'] && ! $a['logo'] ) || ( $a['allow_override'] && empty( $options['reader_watermark'] ) ) ) { return array(); }
		return $a;
	}

	/** Render the settings section inside the existing administrator form.
	 * @return void
	 */
	public static function fields(): void {
		$a = self::sanitize( Settings::stored()['watermark'] ?? array() );
		?>
		<section id="bpp-section-watermark" class="bpp-panel bpp-watermark-settings"><h2><?php esc_html_e( 'Reader watermark', 'builder-pdf-presenter' ); ?></h2>
		<p><?php esc_html_e( 'The watermark appears in the embedded reader only. Original files and downloaded PDFs remain unchanged.', 'builder-pdf-presenter' ); ?></p>
		<p><label><input type="checkbox" name="bpp_settings[watermark][enabled]" value="1" <?php checked( $a['enabled'] ); ?>> <?php esc_html_e( 'Enable the central watermark template', 'builder-pdf-presenter' ); ?></label></p>
		<div class="bpp-watermark-dependent"><p><label><input type="checkbox" name="bpp_settings[watermark][allow_override]" value="1" <?php checked( $a['allow_override'] ); ?>> <?php esc_html_e( 'Allow editors to turn this template on or off per document', 'builder-pdf-presenter' ); ?></label></p>
		<p><?php esc_html_e( 'When exceptions are disabled, the template applies to every reader. Editors cannot replace its text or logo.', 'builder-pdf-presenter' ); ?></p>
		<div class="bpp-fields"><label class="bpp-field"><?php esc_html_e( 'Watermark text', 'builder-pdf-presenter' ); ?><input type="text" maxlength="120" name="bpp_settings[watermark][text]" value="<?php echo esc_attr( $a['text'] ); ?>"></label>
		<label class="bpp-field"><?php esc_html_e( 'Position', 'builder-pdf-presenter' ); ?><select name="bpp_settings[watermark][position]">
		<?php foreach ( array( 'center'=>__( 'Center', 'builder-pdf-presenter' ), 'top'=>__( 'Top', 'builder-pdf-presenter' ), 'bottom'=>__( 'Bottom', 'builder-pdf-presenter' ) ) as $key=>$label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $a['position'] ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
		<label class="bpp-field"><?php esc_html_e( 'Opacity (%)', 'builder-pdf-presenter' ); ?><input type="number" min="5" max="50" name="bpp_settings[watermark][opacity]" value="<?php echo absint( $a['opacity'] ); ?>"></label>
		<label class="bpp-field"><?php esc_html_e( 'Text size (px)', 'builder-pdf-presenter' ); ?><input type="number" min="16" max="72" name="bpp_settings[watermark][size]" value="<?php echo absint( $a['size'] ); ?>"></label></div>
		<div class="bpp-media" data-bpp-type="image"><h4><?php esc_html_e( 'Watermark logo', 'builder-pdf-presenter' ); ?></h4><input type="hidden" name="bpp_settings[watermark][logo]" value="<?php echo absint( $a['logo'] ); ?>"><span class="bpp-media-name"><?php echo esc_html( $a['logo'] ? get_the_title( $a['logo'] ) : __( 'No file selected', 'builder-pdf-presenter' ) ); ?></span> <button type="button" class="button bpp-select"><?php esc_html_e( 'Select / replace', 'builder-pdf-presenter' ); ?></button> <button type="button" class="button bpp-remove" data-empty="<?php esc_attr_e( 'No file selected', 'builder-pdf-presenter' ); ?>"><?php esc_html_e( 'Remove selection', 'builder-pdf-presenter' ); ?></button></div>
		<div class="bpp-watermark-preview" aria-label="<?php esc_attr_e( 'Watermark preview', 'builder-pdf-presenter' ); ?>"><span class="bpp-watermark-preview-page">PDF</span><div class="bpp-watermark-sample"><img alt="" <?php if ( $a['logo'] ) : ?>src="<?php echo esc_url( wp_get_attachment_image_url( $a['logo'], 'medium' ) ); ?>"<?php else : ?>hidden<?php endif; ?>><span></span></div></div>
		</div></section>
		<?php
	}
}
