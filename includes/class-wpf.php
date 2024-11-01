<?php

defined( 'ABSPATH' ) || exit;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.

 */
class WPF {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power the plugin.     
     */
    protected $loader;
    /**
     * The unique identifier of this plugin.     
     */
    protected $WPF;
    /**
     * The current version of the plugin.     
     */
    protected $version;
    /**
     * Define the core functionality of the plugin.     
     */
    public function __construct() {
        if ( defined( 'WPF_VERSION' ) ) {
            $this->version = WPF_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->WPF = 'wpf';        
        $this->load_dependencies();
        $this->define_system_hooks();
        $this->set_locale();        
        $this->define_field_crud_hooks();
        $this->define_field_profile_crud_hooks();
        $this->define_product_form_hooks();
        $this->define_front_hooks();       
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - WPF_Loader. Orchestrates the hooks of the plugin.
     * - WPF_i18n. Defines internationalization functionality.
     * - WPF_Charge_Calculations. Defines all hooks related to the charge calculations.
     * - WPF_Admin. Defines all hooks for the admin area.
     * - WPF_Front. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.     
     */
    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'libs/cmb2/init.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wpf-functions.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-singleton.php';

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common/class-wpf-system.php';        
        // storages
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/interface-wpf-storage.php';
        // -- options storage
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-options-storage.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-options-storage.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-option-options-storage.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-product-options-storage.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-product-option-options-storage.php';
        // -- table storage
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-table-storage.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-table-storage.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-option-table-storage.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-product-table-storage.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-product-option-table-storage.php';
        // -- field profile options storage
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/storages/class-wpf-field-profile-options-storage.php';        
        // data
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpf-data.php';
        // data-stores
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/data-stores/class-wpf-data-store.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/data-stores/class-wpf-field-ds.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/data-stores/class-wpf-field-option-ds.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/data-stores/class-wpf-field-product-ds.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/data-stores/class-wpf-field-product-option-ds.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/data-stores/class-wpf-field-profile-ds.php';
        // entities
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entities/class-wpf-field.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entities/class-wpf-field-option.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entities/class-wpf-field-product.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entities/class-wpf-field-product-option.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/entities/class-wpf-field-profile.php';
        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpf-loader.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpf-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/admin/field-crud/class-wpf-field-crud.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/admin/field-profile-crud/class-wpf-field-profile-crud.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/admin/product-form/class-wpf-product-form.php';        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/front/class-wpf-front.php';        

        $this->loader = new WPF_Loader();
        $mode = 'table';
        $field_data_store = WPF_Field_DS::instance();        
        $field_option_data_store = WPF_Field_Option_DS::instance();
        $field_product_data_store = WPF_Field_Product_DS::instance();
        $field_product_option_data_store = WPF_Field_Product_Option_DS::instance();
        $field_profile_data_store = WPF_Field_Profile_DS::instance();
        $field_data_store->set_storage( $mode );
        $field_option_data_store->set_storage( $mode );
        $field_product_data_store->set_storage( $mode );
        $field_product_option_data_store->set_storage( $mode );
        $field_profile_data_store->set_storage();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the WPF_i18n class in order to set the domain and to register the hook
     * with WordPress.            
     */
    private function set_locale() {
        $plugin_i18n = new WPF_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }
    /**
     * Register all of the hooks related to the common of the admin and public sides
     */
    private function define_system_hooks() {        
        $plugin_system = new WPF_System( $this->get_WPF(), $this->get_version() );
        
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_system, 'admin_enqueue_widget_scripts' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_system, 'front_enqueue_widget_scripts' );        

        $this->loader->add_filter( 'wpf_widgets', $plugin_system, 'wpf_widgets' );
        $this->loader->add_filter( 'wpf_charge_types', $plugin_system, 'wpf_charge_types' );          
        $this->loader->add_action( 'after_setup_theme', $plugin_system, 'include_widgets' );  

        $this->loader->add_filter( 'wpf_widgets_alter', $plugin_system, 'widgets_alter_init' );
        $this->loader->add_filter( 'wpf_field_attributes_alter', $plugin_system, 'field_attributes_alter_init', 10, 3 );  
        $this->loader->add_filter( 'wpf_field_image_option_alter', $plugin_system, 'field_image_option_alter_init', 10, 3 );             

        // import
        $this->loader->add_filter( 'woocommerce_csv_product_import_mapping_options', $plugin_system, 'csv_product_import_mapping_options', 10, 2 );                
        $this->loader->add_action( 'woocommerce_product_import_inserted_product_object', $plugin_system, 'import_inserted_product_object', 10, 2 );
        
        // wpf_charge_alter hook examples
        //$this->loader->add_filter( 'wpf_charge_alter', $plugin_charge, 'wpf_charge_alter', 10, 6 );
        //$this->loader->add_filter( 'wpf_charge_alter', $plugin_charge, 'wpf_charge_alter_2', 10, 6 );                
    }
    /**
     * Register all of the hooks related to the product ofrm functionality
     * of the plugin.     
     */
    private function define_product_form_hooks() {                
        $product_form = new WPF_Product_Form( $this->get_WPF(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $product_form, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $product_form, 'enqueue_scripts', 10, 1 );
        $this->loader->add_filter( 'woocommerce_product_data_tabs', $product_form, 'product_data_tabs' );
        $this->loader->add_action( 'woocommerce_product_data_panels', $product_form, 'product_data_panels' );
        $this->loader->add_action( 'woocommerce_process_product_meta', $product_form, 'process_product_meta' );        
        $this->loader->add_filter( 'plugin_action_links_woo-product-front-fields/woo-product-front-fields.php', $product_form, 'plugin_action_links' );
        // Add settings section to the Products tab
        $this->loader->add_filter( 'woocommerce_get_sections_products', $product_form, 'add_front_fields_section' );
        // add a form to the section
        $this->loader->add_filter( 'woocommerce_get_settings_products', $product_form, 'add_front_fields_settings_form', 10, 2 );   

        $this->loader->add_action( 'woocommerce_product_duplicate', $product_form, 'product_duplicate', 10, 2 );
    }

    /**
     * Register all of the hooks related to the Field Profile CRUD functionality
     * of the plugin.     
     */
    private function define_field_profile_crud_hooks() {                
        $field_profile_crud = new WPF_Field_Profile_CRUD( $this->get_WPF(), $this->get_version() );        
        $this->loader->add_action( 'admin_enqueue_scripts', $field_profile_crud, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $field_profile_crud, 'enqueue_scripts', 10, 1 );
        // crud 
        $this->loader->add_action( 'admin_menu', $field_profile_crud, 'admin_menu_pages' );        
        $this->loader->add_action( 'admin_menu', $field_profile_crud, 'field_profiles_links' );
        $this->loader->add_action( 'cmb2_admin_init', $field_profile_crud, 'form' );
        $this->loader->add_action( 'cmb2_after_init', $field_profile_crud, 'form_submit' );        
        // shortcode        
        $this->loader->add_shortcode( $field_profile_crud::FORM_SHORTCODE, $field_profile_crud, 'form_shortcode' );        
    }
    /**
     * Register all of the hooks related to the Field CRUD functionality
     * of the plugin.     
     */
    private function define_field_crud_hooks() {                
        $field_crud = new WPF_Field_CRUD( $this->get_WPF(), $this->get_version() );                  

        $this->loader->add_filter( 'wpf_units', $field_crud, 'wpf_units' );                
        $this->loader->add_action( 'admin_enqueue_scripts', $field_crud, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $field_crud, 'enqueue_scripts', 10, 1 );
        // ajax callbacks
        $this->loader->add_action( 'wp_ajax_field_option_delete', $field_crud, 'ajax_get_overridden_options_count' );
        $this->loader->add_action( 'wp_ajax_get_charge_types', $field_crud, 'ajax_get_charge_types' );
        $this->loader->add_action( 'wp_ajax_get_field_info', $field_crud, 'ajax_get_field_info' );        
        $this->loader->add_action( 'wp_ajax_product_form_recalculate', $field_crud, 
            'ajax_product_form_recalculate_charges' );                        
        // crud 
        $this->loader->add_action( 'admin_menu', $field_crud, 'admin_menu_pages' );        
        $this->loader->add_action( 'cmb2_admin_init', $field_crud, 'form' );
        $this->loader->add_action( 'cmb2_after_init', $field_crud, 'form_submit' );        
        $this->loader->add_filter( 'cmb2_override_meta_value', $field_crud, 'group_elements_submit', 10, 4 );
        $this->loader->add_action( 'before_delete_post', $field_crud, 'before_delete_product' );
        // shortcode        
        $this->loader->add_shortcode( $field_crud::FORM_SHORTCODE, $field_crud, 'form_shortcode' );
    }
    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.     
     */
    private function define_front_hooks() {
        $plugin_front = new WPF_Front( $this->get_WPF(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_front, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_front, 'enqueue_scripts' );        

        $this->loader->add_filter( 'woocommerce_add_to_cart_validation', $plugin_front, 'add_to_cart_validation', 99, 6 );
        $this->loader->add_action( 'woocommerce_before_add_to_cart_button', $plugin_front, 'before_add_to_cart_button', 10 );
        $this->loader->add_action( 'woocommerce_after_add_to_cart_button', $plugin_front, 'after_add_to_cart_button', 10 ); 
         $this->loader->add_filter( 'woocommerce_get_price_html', $plugin_front, 'get_custom_price_html', 10, 2 );                   
        // ajax callback for the price negotiation when front fields has been changed
        $this->loader->add_action( 'wp_ajax_wc_price', $plugin_front, 'ajax_get_formatted_price' );
        $this->loader->add_action( 'wp_ajax_nopriv_wc_price', $plugin_front, 'ajax_get_formatted_price' );

        /* @TODO - Field dependency feature    
        $this->loader->add_action( 'wp_ajax_compare_dependency', $plugin_front, 'ajax_compare_dependency' );
        $this->loader->add_action( 'wp_ajax_nopriv_compare_dependency', $plugin_front, 'ajax_compare_dependency' );
        */

        $this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_front, 'add_cart_item_data', 10, 3 );        
        $this->loader->add_filter( 'woocommerce_add_cart_item', $plugin_front, 'add_cart_item', 10, 1 );
        $this->loader->add_filter( 'woocommerce_get_cart_item_from_session', $plugin_front, 'get_cart_item_from_session', 10, 2 );
        $this->loader->add_filter( 'woocommerce_loop_add_to_cart_link', $plugin_front, 'loop_add_to_cart_link', 10, 2 );
        $this->loader->add_filter( 'woocommerce_get_item_data', $plugin_front, 'get_item_data', 10, 2 );        
        $this->loader->add_action( 'woocommerce_add_order_item_meta', $plugin_front, 'order_item_meta', 99, 3 ); 
    }
    /**
     * Run the loader to execute all of the hooks with WordPress.     
     */
    public function run() {
        $this->loader->run();
    }
    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.     
     */
    public function get_WPF() {
        return $this->WPF;
    }
    /**
     * The reference to the class that orchestrates the hooks with the plugin.     
     */
    public function get_loader() {
        return $this->loader;
    }
    /**
     * Retrieve the version number of the plugin.     
     */
    public function get_version() {
        return $this->version;
    }
}