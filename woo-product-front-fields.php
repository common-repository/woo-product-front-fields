<?php

/**
 * Plugin Name: Woocommerce Product Front Fields
 * Plugin URI: bt.product.ua
 * Description: Allows to add custom fields to the Woocommerce product page.
 * Version: 1.0.0
 * Author: Alex Posidelov 
 * Text Domain: wpf
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

if ( ! function_exists( 'wpf_woocommerce_plugin_notice' ) ) {
  function wpf_woocommerce_plugin_notice() {
  ?>
  <div class="notice notice-warning">
    <p><?php echo __( '<strong>Woocommerce Product Front Fields</strong> requires <strong>WooCommerce</strong> to be activated', 'wpf' ); ?></p>
  </div>
  <?php
  }
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WPF_VERSION', '1.0.0' );

function activate_wpf() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpf-activator.php';
  WPF_Activator::activate();
}

function deactivate_wpf() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpf-deactivator.php';
  WPF_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_wpf' );
if ( defined( 'WPF_REMOVE_ALL_DATA' ) && true === WPF_REMOVE_ALL_DATA ) {
  register_deactivation_hook( __FILE__, 'deactivate_wpf' );
}

// check if woocommerce plugin has been activated
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  if ( ! function_exists( 'WC' ) ) {
    add_action( 'admin_notices', 'wpf_woocommerce_plugin_notice' );
  }
  return;
}

require plugin_dir_path( __FILE__ ) . 'includes/class-wpf.php';

function run_wpf() {
  $plugin = new WPF();
  $plugin->run();
}
run_wpf();

