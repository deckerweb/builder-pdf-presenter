<?php
/** Targeted WordPress diagnostics and navigation, without background document scans. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Exposes useful site health information and permission-aware editor commands. */
final class Diagnostics {
	/** Register read-only, administrator-initiated integrations. @return void */
	public static function boot(): void {
		add_filter( 'site_status_tests', array( self::class, 'tests' ) );
		add_filter( 'debug_information', array( self::class, 'info' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'commands' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'commands' ), 100 );
	}

	/** Add two local checks; do not fetch remote files or inspect customer documents.
	 * @param array $tests Core Site Health test registry.
	 * @return array Registry including local reader and preview support checks.
	 */
	public static function tests( array $tests ): array {
		$tests['direct']['bpp_reader'] = array( 'label'=>__( 'PDF Presenter reader files', 'builder-pdf-presenter' ), 'test'=>array( self::class, 'reader' ) );
		$tests['direct']['bpp_previews'] = array( 'label'=>__( 'PDF preview generation', 'builder-pdf-presenter' ), 'test'=>array( self::class, 'previews' ) );
		return $tests;
	}

	/** Describe local reader asset availability. @return array Core Site Health result. */
	public static function reader(): array {
		$ok = true;
		foreach ( array( 'assets/reader-loader.js', 'assets/reader.js', 'vendor/pdfjs/build/pdf.mjs', 'vendor/pdfjs/build/pdf.worker.mjs' ) as $file ) { $ok = $ok && is_readable( BPP_DIR . $file ); }
		return self::result( 'bpp_reader', $ok, $ok ? __( 'PDF Presenter reader files are available', 'builder-pdf-presenter' ) : __( 'PDF Presenter reader files are incomplete', 'builder-pdf-presenter' ), __( 'These local files provide the on-demand reader. If files are missing, reinstall the complete plugin package. Original PDFs remain available independently.', 'builder-pdf-presenter' ) );
	}

	/** Test the server image editor capability without rendering a PDF. @return array Core result. */
	public static function previews(): array {
		$ok = wp_image_editor_supports( array( 'mime_type'=>'application/pdf' ) );
		return self::result( 'bpp_previews', $ok, $ok ? __( 'The server supports PDF preview generation', 'builder-pdf-presenter' ) : __( 'Automatic PDF preview generation is unavailable', 'builder-pdf-presenter' ), __( 'Existing covers can still be used and a custom cover can be selected for each document. Automatic generation depends on the server image editor and PDF support; individual files may still fail.', 'builder-pdf-presenter' ) );
	}

	/** Build a consistent diagnostic result.
	 * @param string $test Stable test identifier.
	 * @param bool $ok Whether the optional capability or required files are available.
	 * @param string $label Translated summary.
	 * @param string $description Translated explanation and remedy.
	 * @return array Site Health result, without paths or document information.
	 */
	private static function result( string $test, bool $ok, string $label, string $description ): array {
		return array( 'test'=>$test, 'status'=>$ok ? 'good' : ( 'bpp_reader' === $test ? 'critical' : 'recommended' ), 'label'=>$label, 'badge'=>array( 'label'=>'PDF Presenter', 'color'=>'blue' ), 'description'=>'<p>' . esc_html( $description ) . '</p>', 'actions'=>'' );
	}

	/** Add non-sensitive information to the existing WordPress debug export.
	 * @param array $info Core debug groups.
	 * @return array Extended information; no customer titles, URLs or paths.
	 */
	public static function info( array $info ): array {
		$info['bpp'] = array( 'label'=>'Builder PDF Presenter', 'fields'=>array( 'version'=>array( 'label'=>__( 'Version', 'builder-pdf-presenter' ), 'value'=>BPP_VERSION ), 'reader'=>array( 'label'=>__( 'Reader', 'builder-pdf-presenter' ), 'value'=>'Local PDF.js' ), 'bricks'=>array( 'label'=>__( 'Bricks integration', 'builder-pdf-presenter' ), 'value'=>class_exists( '\Bricks\Element' ) ? __( 'Available', 'builder-pdf-presenter' ) : __( 'Not active', 'builder-pdf-presenter' ) ) ) );
		return $info;
	}

	/** Queue commands only where WordPress provides its command palette.
	 * @param string $hook Optional current admin hook supplied by WordPress.
	 * @return void
	 */
	public static function commands( string $hook = '' ): void {
		if ( ! current_user_can( 'edit_posts' ) || ! wp_script_is( 'wp-commands', 'registered' ) || ( ! did_action( 'enqueue_block_editor_assets' ) && ! wp_script_is( 'wp-commands', 'enqueued' ) ) ) { return; }
		wp_enqueue_script( 'bpp-commands', BPP_URL . 'assets/commands.js', array( 'wp-commands', 'wp-data', 'wp-dom-ready' ), BPP_VERSION, true );
		$items = array( array( 'name'=>'bpp/documentation', 'label'=>__( 'PDF Presenter: documentation', 'builder-pdf-presenter' ), 'url'=>BPP_URL . ( str_starts_with( determine_locale(), 'de_' ) ? 'docs/index-de.html' : 'docs/index-en.html' ) ) );
		if ( current_user_can( 'manage_options' ) ) {
			$items[] = array( 'name'=>'bpp/settings', 'label'=>__( 'PDF Presenter: settings', 'builder-pdf-presenter' ), 'url'=>admin_url( 'options-general.php?page=builder-pdf-presenter' ) );
			if ( Migration::available() ) { $items[] = array( 'name'=>'bpp/migration', 'label'=>__( 'PDF Presenter: PDF migration and recovery', 'builder-pdf-presenter' ), 'url'=>Migration::url() ); }
		}
		wp_localize_script( 'bpp-commands', 'bppCommands', $items );
	}
}
