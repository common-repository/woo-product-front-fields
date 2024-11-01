<?php

defined( 'ABSPATH' ) || exit;

/*
 * This class defines all code necessary to run during the plugin's activation.
 */
class WPF_Activator {
  /**     
   *
   * @since    1.0.0
   */
  public static function activate() {
    self::create_options();
    self::create_tables();
    self::add_capabilities();
  }

  /**
   * Get schema
   * @return array
   */
  private static function get_schema() {
    global $wpdb;

    $collate = '';
    if ( $wpdb->has_cap( 'collation' ) ) {
      $collate = $wpdb->get_charset_collate();
    }

    $tables = "
      CREATE TABLE {$wpdb->prefix}wpf_fields (
        id BIGINT UNSIGNED NOT NULL auto_increment,
        name varchar(128) NOT NULL,
        title varchar(128) NOT NULL,
        widget varchar(64) NOT NULL,
        chargeable tinyint(1) NOT NULL DEFAULT '1',
        charge decimal(10,2) NOT NULL,
        charge_type varchar(32) NOT NULL DEFAULT 'fixed',
        default_value varchar(256) NOT NULL,
        required tinyint(1) NOT NULL DEFAULT '0',
        active_by_default tinyint(1) NOT NULL,       
        options_extra varchar(32) NOT NULL DEFAULT '',        
        unit varchar(16) NOT NULL DEFAULT '',        
        visibility text NULL DEFAULT '',
        PRIMARY KEY (id),
        UNIQUE KEY id (id),
        KEY name (name)
      ) $collate;
      CREATE TABLE {$wpdb->prefix}wpf_fields_options (
        id BIGINT UNSIGNED NOT NULL auto_increment,
        field_id BIGINT UNSIGNED NOT NULL,
        data varchar(64) NOT NULL,
        title varchar(128) DEFAULT NULL,
        price decimal(10,2) NOT NULL,
        weight int(11) NOT NULL,
        PRIMARY KEY (id),
        KEY field_id (field_id)
      ) $collate;
      CREATE TABLE {$wpdb->prefix}wpf_fields_products (
        id BIGINT UNSIGNED NOT NULL auto_increment,
        field_id BIGINT UNSIGNED NOT NULL,
        product_id BIGINT UNSIGNED NOT NULL,
        variation_id BIGINT UNSIGNED NOT NULL,
        is_active tinyint(1) NOT NULL DEFAULT '0',
        is_overridden_value tinyint(1) NOT NULL DEFAULT '0',
        value text NOT NULL,
        weight int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (id),
        KEY active_product_id (product_id,is_active),
        KEY field_product_id (field_id,product_id)
      ) $collate;
      CREATE TABLE {$wpdb->prefix}wpf_fields_products_options (
        id BIGINT UNSIGNED NOT NULL auto_increment,
        fpid BIGINT UNSIGNED NOT NULL,
        option_id BIGINT UNSIGNED DEFAULT NULL,
        is_active tinyint(1) NOT NULL DEFAULT '1',
        is_overridden_price tinyint(1) NOT NULL DEFAULT '0',
        price decimal(10,2) NOT NULL DEFAULT '0.00',
        is_overridden_title tinyint(1) NOT NULL DEFAULT '0',
        title varchar(256) NOT NULL,
        PRIMARY KEY (id)
      ) $collate;      
    ";

    return $tables;
  }

  /**
   * Get core tables
   * @return array
   */
  public static function get_tables() {
    global $wpdb;

    $tables = array(
      "{$wpdb->prefix}wpf_fields",
      "{$wpdb->prefix}wpf_fields_options",
      "{$wpdb->prefix}wpf_fields_products",
      "{$wpdb->prefix}wpf_fields_products_options"
    );
    return $tables;
  }

  /**
   * Get core capabilities
   * @return array
   */
  private static function get_core_capabilities() {
    $capabilities = array();
    $capabilities[] = 'manage_wpf_fields';
    return $capabilities;
  }

  /**
   * Add core capabilities to the roles
   */
  public static function add_capabilities() {
    global $wp_roles;
    if ( ! class_exists( 'WP_Roles' ) ) {
      return;
    }
    if ( ! isset( $wp_roles ) ) {
      $wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
    }
    $capabilities = self::get_core_capabilities();
    foreach ( $capabilities as $cap ) {
      $wp_roles->add_cap( 'shop_manager', $cap );
      $wp_roles->add_cap( 'administrator', $cap );
    }
  }

  /**
   * Drop WPF tables.
   *
   * @return void
   */
  public static function drop_tables() {
    global $wpdb;

    $tables = self::get_tables();

    foreach ( $tables as $table ) {
      $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }
  }

  /**
   * Create tables   
   */
  public static function create_tables() {
    global $wpdb;

    $wpdb->hide_errors();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( self::get_schema() );
  }

  /**
   * Default options.
   *
   * Sets up the default options used on the settings page.
   */
  public static function create_options() {    
    add_option( 'wpf_product_teaser_button_text', __( 'Select options', 'wpf' ), '', 'yes' );    
    add_option( 'wpf_radio_field_empty_label', __( 'None', 'wpf' ), '', 'yes' );
    add_option( 'wpf_select_field_empty_label', __( '--Select option--', 'wpf' ), '', 'yes' );
    add_option( 'wpf_checkbox_is_checked_label', __( 'yes', 'wpf' ), '', 'yes' );
    add_option( 'wpf_fields_location', 'before_addtocart', '', 'yes' );
    add_option( 'wpf_base_price_info', 'yes', '', 'yes' );
    add_option( 'wpf_base_price_label', 'Price', '', 'yes' );
    add_option( 'wpf_field_profile_show_links', 'yes', '', 'yes' );
    add_option( 'wpf_field_profiles', array(), '', 'yes' );    
  }

  /**
   * Drop options   
   */
  public static function drop_options() {
    delete_option( 'wpf_product_teaser_button_text' );
    delete_option( 'wpf_checkbox_is_checked_label' );    
    delete_option( 'wpf_radio_field_empty_label' );
    delete_option( 'wpf_select_field_empty_label' );
    delete_option( 'wpf_fields_location' );
    delete_option( 'wpf_base_price_info' );
    delete_option( 'wpf_base_price_label' );
    delete_option( 'wpf_field_profile_show_links' );
    delete_option( 'wpf_field_profiles' ); 
  }
}