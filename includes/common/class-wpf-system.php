<?php

defined( 'ABSPATH' ) || exit;

/**
 * The charge calculations functionality of the plugin
 *
 * Calculation Types:
 * 1) `Charge type` - Field -> Field Type -> Charge Type -> Calculation ( fixed_text, per_char, fixed_radio )
 * 
 * 2) `wpf_charge_alter` - Allows to make changes in some charges depending on values of other 
 *    fields ( Pizza size, Ingridients )                
 * 
 *
 * @since   1.0.0
 */
class WPF_System {    
  /**
   * The ID of this plugin.
   *   
   * @var string $plugin_name
   */
  private $plugin_name;      
  /**
   * The version of this plugin.
   *   
   * @var string $version
   */
  private $version; 

  /**
   * Initialize the class and set its properties.
   *   
   * @param string $plugin_name
   * @param string $version
   */   
  public function __construct( $plugin_name, $version ) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  /**
   * hook-handler: after_setup_theme
   * Include widgets dynamically through `wpf_widgets` hook
   */
  public function include_widgets() {
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-base-widget.php';    
    wpf_widgets_global_load();
    global $wpf_widgets;    
    foreach ( $wpf_widgets as $widget ) {
      require_once $widget['path'];
    }    
  }

  /**
   * hook handler: admin_enqueue_scripts
   * Register the admin widget's JavaScript
   */
  public function admin_enqueue_widget_scripts( $hook ) {
    if ( wpf_is_product_form( $hook ) || ( isset( $_GET['page'] ) && 'wpf_field_edit' === $_GET['page'] ) ) {      
      wpf_widgets_global_load();
      global $wpf_widgets;    
      $libs = array_column( $wpf_widgets, 'libs', 'name' );             
      $this->load_libs( $libs );
    }
  }

  /**
   * hook handler: wp_enqueue_scripts
   * Register the front widget's JavaScript
   */
  public function front_enqueue_widget_scripts( $hook ) {        
    if ( !is_product() ) {
      return;
    }    
    global $post;    
    wpf_product_charges_global_load( $post->ID ); 
    global $wpf_products;    
    // check weither at least one wpf field has been attached to the product
    if ( !sizeof( $wpf_products[$post->ID] ) ) {
      return;
    }
    $grouped_widgets = array_unique( array_column( 
                            $wpf_products[$post->ID], 'widget' 
                          ) );      
    wpf_widgets_global_load();
    global $wpf_widgets;
    $libs = array();
    foreach ( $wpf_widgets as $id => $widget ) {
      if ( in_array( $id, $grouped_widgets) && isset( $widget['libs'] ) ) {
        $libs[ $wpf_widgets[$id]['name'] ] = &$widget['libs'];
      }
    }
    
    $this->load_libs( $libs, 'front' );
  }

  private function load_libs( $libs, $mode = 'admin' ) {
    foreach ( $libs as $name => $lib ) {
      if ( isset( $lib['custom_dependencies'] ) ) {
        foreach ( $lib['custom_dependencies'] as $dep_id => $dep ) { 
          if ( !isset( $dep['dependencies'] ) ) {
            $dep['dependencies'] = array();
          }
          if ( isset( $dep['include_default_styles'] ) && $dep['include_default_styles'] ) {
            wp_enqueue_style( $dep_id ); 
          }
          if ( isset( $dep['js'] ) ) {      
            wp_register_script( $dep_id, $dep['js'], $dep['dependencies'], $this->version, false );
          }
          if ( isset( $dep['css'] ) ) {      
            wp_register_style( $dep_id, $dep['css'], false, $this->version, false );       
          }
          if ( isset( $dep['localize_script'] ) && 
               isset( $dep['localize_script']['object'] ) && 
               isset( $dep['localize_script']['data'] ) ) {                        
            wp_localize_script( $dep_id, 
              $dep['localize_script']['object'], 
              $dep['localize_script']['data'] 
            );
          }
        }
      }
      $widget_id = str_replace( ' ', '_', strtolower( $name ) );
      $script_id = 'field_form_' . $widget_id;

      $js_dependencies = isset( $lib['js_dependencies'] ) ? $lib['js_dependencies'] : array();
      $css_dependencies = isset( $lib['css_dependencies'] ) ? $lib['css_dependencies'] : array();      
      if ( isset( $lib["{$mode}_js"] ) ) {           
        wp_enqueue_script( $script_id, $lib["{$mode}_js"], $js_dependencies, $this->version, false );       
      }
      if ( isset( $lib['settings'] ) ) {                                    
        wp_localize_script(
          $script_id,
          'wpf_' . $widget_id. '_widget',
          array(                                    
            'settings' => $lib['settings']
          )
        );
      }
      if ( isset( $lib['css'] ) ) {
        wp_enqueue_style( $script_id, $lib['css'], $css_dependencies, $this->version, false );       
      }
    }    
  }

  /**
   * hook-handler: woocommerce_csv_product_import_mapping_options   
   *
   * @param array $options   
   * @param array $item   
   */
  public function csv_product_import_mapping_options( $options, $item ) {
    $options['wpf_field_profile'] = __( 'WPF Field Profile', 'wpf' );
    return $options;
  }

  /**
   * hook handler: woocommerce_product_import_inserted_product_object
   * @param  object $product 
   * @param  array $data mapped data   
   */
  public function import_inserted_product_object( $product, $data ) {
    if ( !isset( $data['wpf_field_profile'] ) || 
         empty( $data['wpf_field_profile'] ) ) {
      return;
    }
    $field_profile_name = $data['wpf_field_profile'];
    $profile_field_ids = wpf_get_fields_by_profile_name( $field_profile_name );
    $active_field_ids = wpf_fields_get_active_by_default();
    $merged_ids = array_merge( $profile_field_ids, $active_field_ids );
    $weight = 0;
    foreach ( $merged_ids as $field_id ) {
      $field = new WPF_Field( $field_id );
      $fp = new WPF_Field_Product();
      $fp->set_field_id( $field_id );
      $fp->set_product_id( $product->get_id() );
      $fp->set_variation_id( 0 );
      $fp->set_weight( $weight );
      $is_active = true;
      if ( in_array( $field_id, $active_field_ids ) &&
           !in_array( $field_id, $profile_field_ids ) ) {
        $is_active = false;
      }
      $fp->set_is_active( $is_active );
      $fp->set_is_overridden_value( false );
      $fp->set_value( $field->get_default_value() );
      $fp->save();  
      $weight++;
    }   
  }  

  /**
   * hook-handler: wpf_widgets
   * Add Front fields settings form to the Front fields section
   * 
   * @param array $widgets   
   */
  public function wpf_widgets( $widgets ) {    
    $widgets['WPF_Text_Widget'] = array(      
      'name' => 'Text',
      'type' => 'text',
      'attributes' => array(
        'placeholder' => '',
        'autocomplete' => 'off'
      ),
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-text-widget.php',      
      'charge_types' => array( 'fixed_text', 'per_char' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),
    );
    $widgets['WPF_Textarea_Widget'] = array(      
      'name' => 'Textarea',
      'type' => 'text',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-textarea-widget.php',      
      'charge_types' => array( 'fixed_text', 'per_char' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),
    );
    $widgets['WPF_Number_Widget'] = array(      
      'name' => 'Number',
      'type' => 'text',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-number-widget.php',      
      'charge_types' => array( 'fixed_text' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),
    );
    $widgets['WPF_Date_Widget'] = array(      
      'name' => 'Date',
      'type' => 'text',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-date-widget.php',      
      'charge_types' => array( 'fixed_text' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),
      'libs' => array(
        'admin_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-date-widget.admin.js',
        'front_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-date-widget.front.js',
        'css' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/css/jquery-ui.min.css',
        'js_dependencies' => array( 'jquery', 'jquery-ui-datepicker' ),  
        'settings' => array(
          'dateFormat' => 'dd-mm-yy',          
        ),      
      ),
    );
    /*$widgets['WPF_Color_Widget'] = array(      
      'name' => 'Color',
      'type' => 'text',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-color-widget.php',      
      'charge_types' => array( 'fixed_text' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),
      'libs' => array(
        'admin_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-color-widget.admin.js',
        'front_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-color-widget.front.js',
        'js_dependencies' => array( 'wp-color-picker' ),
        'css_dependencies' => array( 'wp-color-picker' ),  
        'custom_dependencies' => array(
          'iris' => array(
            'js' => admin_url( 'js/iris.min.js' ),            
            'dependencies' => array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
          ),          
          'wp-color-picker' => array(
            'js' => admin_url( 'js/color-picker.min.js' ),            
            'dependencies' => array( 'iris' ),
            'include_default_styles' => true,
            'localize_script' => array(
              'object' => 'wpColorPickerL10n',
              'data' => array(
                'clear' => __( 'Clear', 'wpf' ),
                'defaultString' => __( 'Default', 'wpf' ),
                'pick' => __( 'Select Color', 'wpf' ),
                'current' => __( 'Current Color', 'wpf' )
              ),
            ),
          ),          
        ),      
      ),
    );    
    */
    $widgets['WPF_Slider_Widget'] = array(      
      'name' => 'Slider',
      'type' => 'text',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-slider-widget.php',      
      'charge_types' => array( 'fixed_text' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),      
      'libs' => array(
        'admin_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-slider-widget.admin.js',
        'front_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-slider-widget.front.js',
        'css' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/css/jquery-ui.min.css',
        'js_dependencies' => array( 'jquery', 'jquery-ui-slider' ),        
        'settings' => array(
          'step' => 1,
          'min' => 1,
          'max' => 50
        ),        
      ),
    );
    $widgets['WPF_Checkbox_Widget'] = array(
      'name' => 'Checkbox',
      'type' => 'checkbox',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-checkbox-widget.php',      
      'charge_types' => array( 'fixed_text' ),      
      'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_checkbox_cart_data_map' ),
    );
    $widgets['WPF_Select_Widget'] = array(
      'name' => 'Select',
      'type' => 'single_option',
      'allow_images' => false,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-select-widget.php',      
      'charge_types' => array( 'fixed_radio' ),      
      'product_charge_callback' => array( $this, 'wpf_single_option_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_single_option_cart_data_map' ),
    );
    $widgets['WPF_Radio_Widget'] = array(
      'name' => 'Radios',
      'type' => 'single_option',
      'allow_images' => true,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-radio-widget.php',      
      'charge_types' => array( 'fixed_radio' ),      
      'product_charge_callback' => array( $this, 'wpf_single_option_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_single_option_cart_data_map' ),
    );
    $widgets['WPF_Checkboxes_Widget'] = array(
      'name' => 'Checkboxes',
      'type' => 'multi_option',
      'allow_images' => true,
      'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-checkboxes-widget.php',      
      'charge_types' => array( 'fixed_checkboxes' ),      
      'product_charge_callback' => array( $this, 'wpf_multi_option_product_charge_map' ),
      'cart_data_callback' => array( $this, 'wpf_multi_option_cart_data_map' ),
    );

    $widgets = apply_filters( 'wpf_widgets_alter', $widgets );

    return $widgets;
  }

  /**
   * hook-handler: wpf_charge_types
   * Add Front fields settings form to the Front fields section
   * 
   * @param array $charge_types   
   */
  public function wpf_charge_types( $charge_types ) {
    $charge_types['fixed_text'] = array(
      'name' => 'Fixed',
      'calculate_callback' => array( $this, 'wpf_fixed_text_calculate_callback' ),      
    );
    $charge_types['per_char'] = array(
      'name' => 'Per char',
      'calculate_callback' => array( $this, 'wpf_per_char_text_calculate_callback' ),
      'info' => __( 'per char', 'wpf' )
    );
    $charge_types['fixed_radio'] = array(
      'name' => 'Fixed',
      'calculate_callback' => array( $this, 'wpf_fixed_radio_calculate_callback' ),      
    );
    $charge_types['fixed_checkboxes'] = array(
      'name' => 'Fixed',
      'calculate_callback' => array( $this, 'wpf_fixed_checkboxes_calculate_callback' ),      
    );
    return $charge_types;
  }  

  /**
   * Allows to change widget settings
   * 
   * @param  array $attributes 
   * @return array 
   */
  public function widgets_alter_init( $attributes ) {    
    return $attributes;
  }

  /**
   * Allows to change front field attributes
   * 
   * @param  array $field
   * @param  integer $product_id
   * @param  array $attributes
   * @return array
   */
  public function field_attributes_alter_init( $field, $product_id, $attributes ) {    
    return $attributes;
  }

  /**
   * Allows to change thumbnail and attributes of the imageable field options
   * 
   * @param  array $name
   * @param  integer $product_id
   * @param  array $image_data  array(
   *    'image_style' => 'thumbnail_name',
   *    'attributes' => array()
   * )
   * @return array(
   *    'image_style' => 'thumbnail_name',
   *    'attributes' => array()
   * )
   */
  public function field_image_option_alter_init( $name, $product_id, $image_data ) {    
    return $image_data;
  }

  /** Example */
  /*public function field_image_option_alter_init2( $name, $product_id, $image_data ) {    
    $image_data['image_style'] = 'medium';
    $image_data['attributes'] = array( 'data-tippy' => $image_data['attributes']['title'] );
    unset($image_data['attributes']['title']);
    return $image_data;
  }*/

  /**
   * ***
   * Cart data callback handlers - run after clicking on `Add to cart` button
   * 
   */
  /**   
   * Text widget field: This callback maps a wpf field data (charge, value, multifield) from the global data to further use for the Cart, Checkout and Order pages popups and forms
   *
   * @param  array $value        
   * @param  integer $product_id   
   * @param  array $field        
   * @param  array $field_values 
   * @return array               
   */
  public function wpf_text_cart_data_map( $value, $product_id, $field, $field_values ) {
    global $wpf_products;    
    $charge = $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];
    return array(
      'charge' => $charge,
      'value' => $value,
      'multifield' => false
    );
  }

  /**   
   * Checkbox widget field: This callback maps a wpf field data (charge, value, multifield) from the global data to further use for the Cart, Checkout and Order pages popups and forms
   *
   * @param  array $value        
   * @param  integer $product_id   
   * @param  array $field        
   * @param  array $field_values 
   * @return array               
   */
  public function wpf_checkbox_cart_data_map( $value, $product_id, $field, $field_values ) {
    if ( isset( $value ) && ! empty( $value ) ) {
      global $wpf_products;
      $charge = $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];      
      $value = __( get_option( 'wpf_checkbox_is_checked_label', 'yes' ), 'wpf' );
      return array(
        'charge' => $charge,
        'value' => $value,
        'multifield' => false
      );
    }
  }

  /**   
   * Radio, Select widget fields: This callback maps a wpf field data (charge, value, multifield) from the global data to further use for the Cart, Checkout and Order pages popups and forms
   *
   * @param  array $value        
   * @param  integer $product_id   
   * @param  array $field        
   * @param  array $field_values 
   * @return array               
   */
  public function wpf_single_option_cart_data_map( $value, $product_id, $field, $field_values ) {    
    $option = WPF_Field_Option_DS::instance()
                    ->get_option( $value );                   
    //$charge = !empty( $option['price'] ) ? $option['price'] : 0;
    global $wpf_products;
    $charge = $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];    
    //
    $value = !empty( $option['title'] ) ? $option['title'] : '';
    return array(
      'charge' => $charge,
      'value' => $value,
      'multifield' => false
    );
  }

  /**   
   * Checkboxes widget field: This callback maps a wpf field data (charge, value, multifield) from the global data to further use for the Cart, Checkout and Order pages popups and forms
   *
   * @param  array $value        
   * @param  integer $product_id   
   * @param  array $field        
   * @param  array $field_values 
   * @return array               
   */
  public function wpf_multi_option_cart_data_map( $value, $product_id, $field, $field_values ) {
    $info = '';
    global $wpf_products;
    $charges = $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];
    $charge_list = array();         
    if ( ! empty( $value ) ) {
      foreach ( $value as $item_id ) {        
        $option = WPF_Field_Option_DS::instance()
                  ->get_option( $item_id );      
        $charge = $wpf_products[$product_id][ $field['name'] ]['charges'][$item_id];
        $charge = ( $field['chargeable'] && $charge > 0 ) ? wpf_price_wrapper( $charge ) : '';
        $info .= !empty( $option['title'] ) ? $option['title'] . ' ' . $charge . '<br/>' : '';      
      }
    }
    return array(
      'charge' => $charges,
      'value' => $info,
      'multifield' => true
    );
  }

  /**
   * ***
   * Product charge callback handlers - run during Product page loading or 
   * wpf ajax calls on Product page
   */
  
  /**
   * Text, Checkbox widget fields: This callback maps a wpf field calculated charge from the global data to further use on the Product view page ( Main Product page, Related products, Shop page )
   * 
   * @param  string $value          
   * @param  decimal $default_charge 
   * @param  integer $product_id     
   * @param  array $field          
   * @return decimal         
   */
  public function wpf_text_product_charge_map( 
                                  $value,                                  
                                  $product_id, 
                                  $field ) {
        
    if ( ! empty( $value ) ) {
      global $wpf_products;                      
      return $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];                    
    }
    return 0;
  }
  
  /**
   * Radio, Select widget fields: This callback maps a wpf field calculated charge from the global data to further use on the Product view page ( Main Product page, Related products, Shop page )
   * 
   * @param  array $value          
   * @param  decimal $default_charge 
   * @param  integer $product_id     
   * @param  array $field          
   * @return decimal                 
   */
  public function wpf_single_option_product_charge_map( 
                                  $value,                                  
                                  $product_id, 
                                  $field ) {
    if ( ! empty( $value ) ) {
      global $wpf_products;      
      return $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];
    }
    return 0;
  }

  /**
   * Checkboxes widget field: This callback maps a wpf field calculated charge from the global data to further use on the Product view page ( Main Product page, Related products, Shop page )
   * 
   * @param  array $value          
   * @param  decimal $default_charge 
   * @param  integer $product_id     
   * @param  array $field          
   * @return decimal                 
   */
  public function wpf_multi_option_product_charge_map( 
                                  $value,                                  
                                  $product_id, 
                                  $field ) {    
    if ( sizeof( $value ) ) {
      global $wpf_products;
      return $wpf_products[$product_id][ $field['name'] ]['calculated_charge'];
    }
    return 0;    
  }  
  
  /**
   *  wpf_charge_alter hook handler examples 
   *  
   * hook-handler: wpf_charge_alter
   * Allows to make changes in some charges depending on values of other fields
   * For example in Pizza example: depending on the value of the Pizza Size field - the charge of the every Ingridient option increased to some value (x1 - small size, x2 - medium x3 large)
   * 
   * @param  decimal $charge       
   * @param  string|array $value        
   * @param  string $name   
   * @param  array $field_values 
   * @param  boolean $product_page 
   * @param  integer $product_id   
   * @return decimal updated charge              
   */
  /*public function wpf_charge_alter( $charge, $value, $name, $field_values, $product_page, $product_id ) {       
    if ( isset( $field_values['pizza_size'] ) && 'ingridients' == $name ) {                 
      switch ( $field_values['pizza_size'] ) {
        // Small
        case '73':          
          return $charge * 1;
        // Medium
        case '74':          
          return $charge * 2;
        // Large 
        case '75':          
          return $charge * 3;
      }
    }
    return $charge;
  }

  public function wpf_charge_alter_2( $charge, $value, $name, $field_values, $product_page, $product_id ) {       
    if ( 'width' == $name && is_numeric( $value ) && $value > 1 && is_numeric( $field_values['height'] ) && $field_values['height'] > 1 ) {
      
      return $field_values['width'] * $field_values['height'] * 10;
    }
    return $charge;
  }
  */
  /**
   * ***
   * Charge type calculation rules callback
   */
  /**
   * Calculation callback for the `fixed_text` charge type
   * 
   * @param  string  $value
   * @param  decimal  $charge
   * @param  boolean $product_page
   * @return decimal
   */
  public function wpf_fixed_text_calculate_callback( $value, $charge, $product_page = true ) {
    if ( ! empty( $value ) ) {      
      return $charge;
    }
    return 0;
  }

  /**
   * Calculation callback for the `per_char` charge type
   * 
   * @param  string  $value
   * @param  decimal  $charge
   * @param  boolean $product_page
   * @return decimal
   */
  public function wpf_per_char_text_calculate_callback( $value, $charge, $product_page = true ) {
    if ( ! empty( $value ) ) {      
      return mb_strlen( $value ) * $charge;
    }
    return 0;
  }

  /**
   * Calculation callback for the `fixed_radio` charge type
   * 
   * @param  string  $value
   * @param  decimal  $charge
   * @param  boolean $product_page
   * @return decimal
   */
  public function wpf_fixed_radio_calculate_callback( $value, $charge, $product_page = true ) {    
    if ( ! empty( $value ) ) {      
      return $charge;
    }
    return 0;
  }

  /**
   * Calculation callback for the `fixed_checkboxes` charge type
   * 
   * @param  string  $value
   * @param  decimal  $charge
   * @param  boolean $product_page
   * @return decimal
   */
  public function wpf_fixed_checkboxes_calculate_callback( $value, $charge, $product_page = true ) {
    if ( ! empty( $value ) ) {      
      return $charge;
    }
    return 0;
  }
}