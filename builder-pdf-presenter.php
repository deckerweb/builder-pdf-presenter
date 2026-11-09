<?php
/**
 * Plugin Name: Builder PDF Presenter
 * Plugin URI: https://deckerwebplugins.com/en/plugins/builder-pdf-presenter/
 * Description: Fast PDF preview cards and an on-demand PDF reader for any post type, blocks and shortcodes.
 * Version: 1.0.1
 * Requires at least: 6.9
 * Requires PHP: 8.3
 * Author: David Decker – DECKERWEB
 * Author URI: https://deckerwebplugins.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: builder-pdf-presenter
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/builder-pdf-presenter
 * GitHub Plugin URI: https://github.com/deckerweb/builder-pdf-presenter
 *
 * Copyright © 2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace Deckerweb\PDFPresenter;

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'BPP_VERSION', '1.0.1' );
define( 'BPP_FILE', __FILE__ );
define( 'BPP_DIR', plugin_dir_path( __FILE__ ) );
define( 'BPP_URL', plugin_dir_url( __FILE__ ) );

require_once BPP_DIR . 'includes/deckerweb-plugin-library/bootstrap.php';
deckerweb_library_register_v2( BPP_FILE, array(), BPP_DIR . 'includes/deckerweb-plugin-library' );
require_once BPP_DIR . 'includes/class-components.php';
require_once BPP_DIR . 'includes/class-settings.php';
require_once BPP_DIR . 'includes/class-block-styles.php';
require_once BPP_DIR . 'includes/class-watermark.php';
require_once BPP_DIR . 'includes/class-bricks.php';
require_once BPP_DIR . 'includes/class-presenter.php';
require_once BPP_DIR . 'includes/class-library.php';
require_once BPP_DIR . 'includes/class-admin.php';
require_once BPP_DIR . 'includes/class-settings-transfer.php';
require_once BPP_DIR . 'includes/class-diagnostics.php';
require_once BPP_DIR . 'includes/class-documents.php';
require_once BPP_DIR . 'includes/class-maintenance.php';
require_once BPP_DIR . 'includes/class-patterns.php';
require_once BPP_DIR . 'includes/class-embedder-migration.php';
require_once BPP_DIR . 'includes/class-migration.php';

/** Load translations and register the independent plugin services. @return void */
function boot(): void {
	load_plugin_textdomain( 'builder-pdf-presenter', false, dirname( plugin_basename( BPP_FILE ) ) . '/languages' );
	Components::boot();
	Presenter::boot();
	Maintenance::boot();
	Patterns::boot();
	Migration::boot();
	if ( is_admin() ) { Admin::boot(); Settings_Transfer::boot(); Diagnostics::boot(); }
}
add_action( 'init', __NAMESPACE__ . '\\boot' );

add_action( 'init', array( Bricks_Integration::class, 'boot' ), 20 );
