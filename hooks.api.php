<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Example how to create Color widget
 */
add_filter( 'wpf_widgets', 'wpf_color_wpf_widget' );

function wpf_color_wpf_widget( $widgets ) {
  $widgets['WPF_Color_Widget'] = array(      
    // the widget's title
    'name' => 'Color',    
    // widget's type ( 'text', 'checkbox', 'single_option', 'multi_option ') 
    'type' => 'text',
    // should images be available in Extra options property?
    'allow_images' => false,
    // the path to widget class file
    'path' => plugin_dir_path( dirname( __FILE__ ) ) . 'field-widgets/class-color-widget.php',      
    // the list of available charge types
    'charge_types' => array( 'fixed_text' ),      
    // callback that map field charge on Product page
    'product_charge_callback' => array( $this, 'wpf_text_product_charge_map' ),
    // callback that map field charge on the Cart
    'cart_data_callback' => array( $this, 'wpf_text_cart_data_map' ),
    // js and css libs that enhance this widget (Slider, Datepicker widgets have libs)
    'libs' => array(
      // js file that responsible for the admin part (Field CRUD, Product form)
      'admin_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-color-widget.admin.js',
      // js file that responsible for the front part (Product page)
      'front_js' => plugin_dir_url( dirname( __FILE__ ) ) . 'field-widgets/js/wpf-color-widget.front.js',
      // js dependencies of the main lib
      'js_dependencies' => array( 'wp-color-picker' ),
      // css dependencies of the main lib
      'css_dependencies' => array( 'wp-color-picker' ),  
      // create custom dependencies
      'custom_dependencies' => array(
        // simple custom dependency        
        'iris' => array(
          'js' => admin_url( 'js/iris.min.js' ),            
          'dependencies' => array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
        ),          
        // custom dependency with variables
        'wp-color-picker' => array(
          'js' => admin_url( 'js/color-picker.min.js' ),            
          'dependencies' => array( 'iris' ),
          // include same named styles ( 'wp-color-picker' )
          'include_default_styles' => true,
          // add variables to the dependency
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
}

/**
 * Example how to change properties of the existing widgets
 */
add_filter( 'wpf_widgets_alter', 'wpf_widgets_alter' );

function wpf_widgets_alter( $widgets ) {
  $widgets['WPF_Text_Widget']['name'] = 'My text';
}

/**
 * Example how to create Per char charge type ( the field's charge is depends on the number of the symbols in the field )
 */
add_filter( 'wpf_charge_types', 'wpf_perchar_wpf_charge_type' );

function wpf_perchar_wpf_charge_type( $charge_types ) {
  $charge_types['per_char'] = array(
    // the name that is shown on Field form
    'name' => 'Per char',
    // callback where field charge is caluclated
    'calculate_callback' => 'wpf_per_char_text_calculate_callback',
    // suffix that is shown on Product page and Cart related pages
    'info' => __( 'per char', 'wpf' )
  );
}

/**
 * calculate_callback
 * @param  [type]  $value        the field value
 * @param  [type]  $charge       the field charge
 * @param  boolean $product_page where calculation happened (Product page or Product form)
 * @return updated charge
 */
function wpf_per_char_text_calculate_callback( $value, $charge, $product_page = true ) {
  if ( ! empty( $value ) ) {      
    return mb_strlen( $value ) * $charge;
  }
  return 0;
}

/**
 * Example how to override default charge calculation for the field based on value of the another field
 */
add_filter( 'wpf_charge_alter', 'wpf_charge_alter', 10, 6 );

/**
 * All Toppings field charges depends on the value of the pizza_size field.
 * 
 * @param  $charge       
 * @param  $value        
 * @param  $name   
 * @param  $field_values 
 * @param  $product_page 
 * @param  $product_id   
 * @return updated charge               
 */
function wpf_charge_alter( $charge, $value, $name, $field_values, $product_page, $product_id ) {       
    if ( isset( $field_values['pizza_size'] ) && 'toppings' == $name ) {                 
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

/**
 * Example how to output html before or after the field on the Product page
 */
add_action( 'wpf_field_view_before', 'wpf_field_view_before', 10, 2 );
add_action( 'wpf_field_view_after', 'wpf_field_view_after', 10, 2 );

function wpf_field_view_before( $field, $product_id ) {
  if ( 'toppings' == $field['name'] ) {
    echo '<h3>Some info about toppings field before</h3>';
  }
}

function wpf_field_view_after( $field, $product_id ) {
  if ( 'toppings' == $field['name'] ) {
    echo '<h3>Some info about toppings field after</h3>';
  }
}

/**
 * Example how to add extra field units 
 */
add_filter( 'wpf_units', 'wpf_units' );

function wpf_units( $units ) {    
  $units['cm'] = __( 'cm', 'wpf' );  
  $units['m'] = __( 'm', 'wpf' );
  return $units;
}

/**
 * Example how to change field attributes
 */
add_filter( 'wpf_field_attributes_alter', 'wpf_field_attributes_alter', 10, 3 );

function wpf_field_attributes_alter( $field, $product_id, $attributes ) {
  if ( 'toppings' == $field['name'] ) {
    $attributes['class'] = 'my-class';
  }
}

/**
 * Example how to change field image option thumbnail and attributes
 */
add_filter( 'wpf_field_image_option_alter', 'wpf_field_image_option_alter', 10, 3 );

function wpf_field_image_option_alter( $name, $product_id, $image_data ) {
  if ( 'toppings' == $name ) {
    $image_data['image_style'] = 'medium';
    $image_data['attributes']['class'] = 'my-class';
  }
}

/**
 * Example how to add some code after wpf field has been created
 */
add_action( 'wpf_field_create', 'wpf_field_create', 10, 2 );

function wpf_field_create( $field, $form_values ) {
  if ( 'toppings' == $field['name'] ) {

  }
}

/**
 * Example how to add some code after wpf field has been updated
 */
add_action( 'wpf_field_update', 'wpf_field_update', 10, 2 );

function wpf_field_update( $field, $form_values ) {
  if ( 'toppings' == $field['name'] ) {

  }
}

/**
 * Example how to add some code before wpf field has been deleted
 */
add_action( 'wpf_field_delete', 'wpf_field_delete', 10, 2 );

function wpf_field_delete( $field ) {
  if ( 'toppings' == $field['name'] ) {

  }
}

/**
 * Example how to add some code after wpf field profile has been created
 */
add_action( 'wpf_field_profile_create', 'wpf_field_profile_create', 10, 2 );

function wpf_field_profile_create( $field_profile, $form_values ) {
  if ( 'Profile1' == $field_profile['name'] ) {

  }
}

/**
 * Example how to add some code after wpf field profile has been updated
 */
add_action( 'wpf_field_profile_update', 'wpf_field_profile_update', 10, 2 );

function wpf_field_profile_update( $field_profile, $form_values ) {
  if ( 'Profile1' == $field_profile['name'] ) {

  }
}

/**
 * Example how to add some code before wpf field profile has been deleted
 */
add_action( 'wpf_field_profile_delete', 'wpf_field_profile_delete', 10, 2 );

function wpf_field_profile_delete( $field_profile ) {
  if ( 'Profile1' == $field_profile['name'] ) {

  }
}

