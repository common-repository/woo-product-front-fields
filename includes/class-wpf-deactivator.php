<?php

defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin deactivation.
 */
class WPF_Deactivator {
  /**   
   *
   * @since    1.0.0
   */
  public static function deactivate() {
    /** 
     * Uninstalling WPF tables and options.
     */
    /*if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
      exit;
    }*/

    global $wpdb, $wp_version;
                    
    include_once dirname( __FILE__ ) . '/class-wpf-activator.php';      
    WPF_Activator::drop_tables();
    WPF_Activator::drop_options();  
    // Clear any cached data that has been removed
    wp_cache_flush();    
  }
}