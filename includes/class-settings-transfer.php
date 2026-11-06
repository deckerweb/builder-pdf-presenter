<?php
/** Portable settings without documents, file URLs or foreign media IDs. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Provides explicit administrator-only export and mapped settings replacement. */
final class Settings_Transfer {
	/** Register authenticated form actions. @return void */
	public static function boot(): void {
		add_action( 'admin_post_bpp_export_settings', array( self::class, 'download' ) );
		add_action( 'admin_post_bpp_import_settings', array( self::class, 'apply' ) );
	}
	/** Build the portable representation.
	 * @return array Versioned export without attachment IDs.
	 */
	public static function payload(): array {
		$settings = Settings::sanitize( Settings::stored() );
		$settings['watermark']['logo'] = 0;
		$settings['watermark']['enabled'] = false;
		return array( 'format'=>'builder-pdf-presenter-settings', 'version'=>1, 'settings'=>$settings );
	}
	/** Validate and map a portable document; never accept media identifiers.
	 * @param mixed $input Decoded JSON.
	 * @param array $mapping Explicit source-to-target content type mapping.
	 * @return array|\WP_Error Clean target settings or explanatory error.
	 */
	public static function prepare( $input, array $mapping ) {
		if ( ! is_array( $input ) || 'builder-pdf-presenter-settings' !== ( $input['format'] ?? '' ) || 1 !== ( $input['version'] ?? 0 ) || ! is_array( $input['settings'] ?? null ) ) { return new \WP_Error( 'bpp_import', __( 'This is not a supported settings export. No settings were changed.', 'builder-pdf-presenter' ) ); }
		$raw = $input['settings'];
		foreach ( array( 'enabled','types','limits','global','watermark' ) as $key ) { if ( isset( $raw[ $key ] ) && ! is_array( $raw[ $key ] ) ) { return new \WP_Error( 'bpp_import', __( 'This is not a supported settings export. No settings were changed.', 'builder-pdf-presenter' ) ); } }
		if ( isset( $raw['limit'] ) && ! is_numeric( $raw['limit'] ) ) { return new \WP_Error( 'bpp_import', __( 'This is not a supported settings export. No settings were changed.', 'builder-pdf-presenter' ) ); }
		$out = array( 'global'=>Settings::clean( $raw['global'] ?? array() ),'limit'=>$raw['limit'] ?? 20,'enabled'=>array(),'types'=>array(),'limits'=>array() );
		$targets = array_filter( $mapping, 'is_string' ); $targets = array_filter( $targets );
		if ( count( $targets ) !== count( array_unique( $targets ) ) ) { return new \WP_Error( 'bpp_mapping', __( 'Choose a different target for each source content type.', 'builder-pdf-presenter' ) ); }
		foreach ( $mapping as $source=>$target ) {
			if ( ! is_string( $source ) || ! is_string( $target ) || ! isset( Settings::types()[ $target ] ) ) { continue; }
			if ( in_array( $source, $raw['enabled'] ?? array(), true ) ) { $out['enabled'][] = $target; }
			$out['types'][ $target ] = Settings::clean( $raw['types'][ $source ] ?? array() );
			$out['limits'][ $target ] = absint( $raw['limits'][ $source ] ?? 0 );
		}
		$watermark = is_array( $raw['watermark'] ?? null ) ? $raw['watermark'] : array();
		$watermark['logo'] = 0; $watermark['enabled'] = false; $out['watermark'] = Watermark::sanitize( $watermark );
		return Settings::sanitize( $out );
	}
	/** Authorize and stream JSON without writing a file on the server. @return void */
	public static function download(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'builder-pdf-presenter' ), '', array( 'response'=>403 ) ); }
		check_admin_referer( 'bpp_transfer' );
		nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="builder-pdf-presenter-settings.json"' );
		echo wp_json_encode( self::payload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON response.
		exit;
	}
	/** Authorize an explicit settings replacement and report errors without mutation. @return void */
	public static function apply(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'builder-pdf-presenter' ), '', array( 'response'=>403 ) ); }
		check_admin_referer( 'bpp_transfer' );
		$raw = isset( $_POST['bpp_import'] ) && is_string( $_POST['bpp_import'] ) ? wp_unslash( $_POST['bpp_import'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON validated below.
		if ( strlen( $raw ) > 100000 ) { wp_die( esc_html__( 'Settings export is too large.', 'builder-pdf-presenter' ) ); }
		$map = isset( $_POST['bpp_mapping'] ) && is_array( $_POST['bpp_mapping'] ) ? map_deep( wp_unslash( $_POST['bpp_mapping'] ), 'sanitize_key' ) : array();
		$clean = self::prepare( json_decode( $raw, true ), $map );
		if ( is_wp_error( $clean ) ) { wp_die( esc_html( $clean->get_error_message() ) ); }
		update_option( 'bpp_settings', $clean, false );
		set_transient( 'bpp_settings_imported_' . get_current_user_id(), true, MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'options-general.php?page=builder-pdf-presenter#bpp-section-transfer' ) ); exit;
	}
	/** Render export and explicit mapped import outside the normal settings form. @return void */
	public static function panel(): void {
		?>
		<section id="bpp-section-transfer" class="bpp-panel"><h2><?php esc_html_e( 'Transfer settings', 'builder-pdf-presenter' ); ?></h2><p><?php esc_html_e( 'Export saved defaults only. PDFs, document assignments and media IDs stay on this website. Watermarks are disabled on import until their text or local logo is reviewed.', 'builder-pdf-presenter' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><?php wp_nonce_field( 'bpp_transfer' ); ?><input type="hidden" name="action" value="bpp_export_settings"><button class="button" type="submit"><?php esc_html_e( 'Export saved settings', 'builder-pdf-presenter' ); ?></button></form>
		<details><summary><?php esc_html_e( 'Import and replace saved settings', 'builder-pdf-presenter' ); ?></summary><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bpp-transfer-form"><?php wp_nonce_field( 'bpp_transfer' ); ?><input type="hidden" name="action" value="bpp_import_settings"><label><?php esc_html_e( 'Paste settings JSON', 'builder-pdf-presenter' ); ?><textarea name="bpp_import" rows="8" required maxlength="100000" class="large-text code"></textarea></label><div class="bpp-import-mapping" data-types="<?php echo esc_attr( wp_json_encode( array_map( static fn( $type )=>$type->labels->name, Settings::types() ) ) ); ?>" data-label="<?php esc_attr_e( 'Skip this content type', 'builder-pdf-presenter' ); ?>"></div><p><?php esc_html_e( 'This replaces saved plugin settings. Choose each target content type explicitly. Unmapped types are skipped. Documents and migration recovery data are not changed.', 'builder-pdf-presenter' ); ?></p><button type="submit" class="button" disabled><?php esc_html_e( 'Apply mapped settings', 'builder-pdf-presenter' ); ?></button></form></details></section>
		<?php
	}
}
