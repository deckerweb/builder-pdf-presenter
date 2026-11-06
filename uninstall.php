<?php
/** Retain user documents and settings, clean only owned/shared temporary component data. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
(static function(): void {
 delete_transient( 'bpp_migration_discovery' );
 $main = __DIR__ . '/builder-pdf-presenter.php';
 $updater_class = require __DIR__ . '/includes/deckerweb-github-release-updater-v2.php';
 if ( is_string( $updater_class ) && is_callable( array( $updater_class, 'clear_repository_cache' ) ) ) { $updater_class::clear_repository_cache( 'https://github.com/deckerweb/builder-pdf-presenter' ); }
 require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
 deckerweb_library_uninstall_v3( $main );
})();
