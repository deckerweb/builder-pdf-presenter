<?php
/** Pinned deckerweb components and host-owned update package validation. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Integrates the updater without publishing the host or creating a catalog entry. */
final class Components {
	const REPOSITORY = 'https://github.com/deckerweb/builder-pdf-presenter';

	/** Register the pinned updater after the host language has initialized.
	 * @return void
	 */
	public static function boot(): void {
		$class = require BPP_DIR . 'includes/deckerweb-github-release-updater-v2.php';
		if ( ! is_string( $class ) || ! class_exists( $class, false ) || ! defined( $class . '::SUPPORTS_HOST_TRANSLATIONS' ) ) { return; }
		$options = array( 'translate' => require BPP_DIR . 'includes/updater-translations.php' );
		$updater = new $class( BPP_FILE, self::REPOSITORY, 'Builder PDF Presenter', __( 'PDF cards and comfortable on-demand reading.', 'builder-pdf-presenter' ), array( 'icons' => array( 'svg' => BPP_URL . 'assets/branding/icon.svg', '1x' => BPP_URL . 'assets/branding/icon-128.png', '2x' => BPP_URL . 'assets/branding/icon-256.png' ) ), $options );
		$updater->register();
		add_filter( 'upgrader_source_selection', array( self::class, 'validate_package' ), 30, 4 );
	}

	/** Verify actual host identity, offered version and requirements before replacing files.
	 * @param string|\WP_Error $source Candidate extracted source directory.
	 * @param string $remote_source Upgrader extraction parent directory.
	 * @param mixed $upgrader Calling WordPress upgrader.
	 * @param array $context Core upgrade metadata, including the native bulk variant.
	 * @return string|\WP_Error Original directory or a fixed safe validation error.
	 */
	public static function validate_package( $source, string $remote_source, $upgrader, array $context = array() ) {
		if ( is_wp_error( $source ) || ( $context['plugin'] ?? '' ) !== plugin_basename( BPP_FILE ) || ( isset( $context['type'] ) && 'plugin' !== $context['type'] ) || ( isset( $context['action'] ) && 'update' !== $context['action'] ) ) { return $source; }
		if ( ! isset( $context['type'], $context['action'] ) && ! ( $upgrader instanceof \Plugin_Upgrader && true === $upgrader->bulk ) ) { return $source; }
		$file = trailingslashit( $source ) . 'builder-pdf-presenter.php';
		$error = new \WP_Error( 'bpp_package', __( 'The update package identity, version or requirements could not be verified. The installed plugin was kept.', 'builder-pdf-presenter' ) );
		if ( ! is_readable( $file ) || filesize( $file ) > 1048576 ) { return $error; }
		$headers = get_file_data( $file, array( 'name' => 'Plugin Name', 'version' => 'Version', 'uri' => 'Update URI', 'php' => 'Requires PHP', 'wp' => 'Requires at least', 'domain' => 'Text Domain' ) );
		$updates = get_site_transient( 'update_plugins' ); $offer = $updates->response[ plugin_basename( BPP_FILE ) ] ?? null;
		global $wp_version;
		if ( 'Builder PDF Presenter' !== $headers['name'] || 'builder-pdf-presenter' !== $headers['domain'] || self::REPOSITORY !== $headers['uri'] || ! $offer || $headers['version'] !== $offer->new_version || ! version_compare( $headers['version'], BPP_VERSION, '>' ) || ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/D', $headers['php'] ) || ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/D', $headers['wp'] ) || version_compare( PHP_VERSION, $headers['php'], '<' ) || version_compare( $wp_version, $headers['wp'], '<' ) ) { return $error; }
		return $source;
	}
}
