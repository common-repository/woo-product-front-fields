<?php

defined( 'ABSPATH' ) || exit;

/**
 * Get the overridden or default value
 * 
 * @param  integer $product_id
 * @param  array $field
 * @return string or array
 */
function wpf_get_value( $product_id, $field ) {
  global $wpf_widgets;
  $type = $wpf_widgets[ $field['widget'] ]['type'];  
  $field_product = WPF_Field_Product_DS::instance()
                    ->get( $field['id'], $product_id );
  $default = $field_product['is_overridden_value'] ? 
             $field_product['value'] : 
             $field['default_value'];
  if ( 'multi_option' === $type ) {
    return explode( ',', $default );
  }
  return $default;
}

/**
 * Get the overridden or default charge
 * 
 * @param  integer  $product_id
 * @param  integer  $field_id
 * @param  integer $option_id
 * @param  integer $default_amount
 * @return demical
 */
function wpf_get_charge( $product_id, $field_id, 
                                   $option_id = 0, $default_amount = 0 ) {  
  $product_field = WPF_Field_Product_DS::instance()
                    ->get_single_data( $field_id, $product_id, $option_id );   
  if ( $product_field['is_overridden_price'] ) {
    return $product_field['price'];
  }  
  return $default_amount;
}

/**
 * Add/Update wpf fields by the product id
 * 
 * @param  integer $product_id
 * @param  array  $fields     
 *   Format:
 *   array(
 *     [field_id] => array(
 *       is_active => true|false
 *       is_overridden_value => true|false
 *       value => ''
 *       options => array( 
 *         option_id => array(
 *           is_active => true|false
 *           is_overridden_price => true|false
 *           price => 0.00
 *         ),
 *         option_id => array(
 *           is_active => true|false
 *           is_overridden_price => true|false
 *           price => 0.00
 *         ),       
 *       )
 *     )
 *   )
 */
function wpf_update_fields( $product_id, $fields = array() ) {
  if ( empty( $fields ) ) {
    return;
  }
  foreach ( $fields as $field_id => $field ) {
    $field_product = new WPF_Field_Product();
    $field_product->set_field_id( $field_id );
    $field_product->set_product_id( $product_id );
    if ( isset( $field['is_active'] ) ) {
      $field_product->set_is_active( $field['is_active'] );
    }
    if ( isset( $field['is_overridden_value'] ) ) {
      $field_product->set_is_overridden_value( $field['is_overridden_value'] );
    }
    if ( isset( $field['value'] ) ) {
      $field_product->set_value( $field['value'] );
    }
    if ( isset( $field['weight'] ) ) {
      $field_product->set_weight( $field['weight'] );
    }
    $field_product->save();
    if ( isset( $field['options'] ) ) {
      $fpid = $field_product->get_id();
      foreach ( $field['options'] as $option_id => $option ) {
        $fpo = new WPF_Field_Product_Option();
        $fpo->set_field_product_id( $fpid );
        $fpo->set_option_id( $option_id );
        if ( $option['is_overridden_price'] ) {
          $fpo->set_is_overridden_price( $option['is_overridden_price'] );
        }
        if ( $option['price'] ) {
          $fpo->set_price( $option['price'] );
        }
        $fpo->save();
      }
    }    
  }
}

/**
 * Add a product to the cart with its wpf fields
 * 
 * @param  integer  $product_id       
 * @param  array   $wpf_default_values
 *   Format:
 *   array(
 *     field1 => value1,
 *     field2 => value2, 
 *   );
 * @param  integer $quantity          
 * @param  integer $variation_id      
 * @param  array   $variation          
 */
function wpf_add_to_cart( $product_id, $wpf_default_values = array(), $quantity = 1, $variation_id = 0, $variation = array() ) {
  $cart_item_data = array();
  $wpf_fields = wpf_get_cart_data( $product_id, 'default' );  
  
  if ( sizeof( $wpf_default_values ) ) {
    foreach ( $wpf_default_values as $key => $value ) {
      $wpf_fields[$key]['value'] = $value;
    }
  }
  $cart_item_data['wpf_fields'] = $wpf_fields;
  WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
}

/** 
 * Get product values. Used: 
 * 1. The handler of the woocommerce_add_cart_item_data hook
 * 2. wpf_add_to_cart() 
 * 
 * @param  integer  $product_id       
 * @param  string $type
 * Possible values:
 * - 'post' (by default) - get data from the $_POST
 * - 'default' - get default values 
 * @return array - get the array that is compartiable with  
 * woocommerce_add_cart_item_data hook:
    array(
      'name'  => $field['name'],
      'title' => $field['title'],
      'value' => $value,
      'charge' => $charge,
      'multifield' => $multifield
    );
 */
function wpf_get_cart_data( $product_id, $type = 'post' ) {
  $fields = wpf_get_active_fields( $product_id );
  $wpf_fields = array();

  global $wpf_widgets;  

  $field_values = array();
  foreach ( $fields as $field ) {
    switch ( $type ) {
      case 'post':
        $value = wpf_get_post( $field['name'], '' );                        
        break;
      case 'default':
        $value = wpf_get_value( $product_id, 
                                WPF_Field_DS::instance()
                                ->get_by_name( $field['name'] ) );
        break;
    }
    $field_values[ $field['name'] ] = $value;           
  }

  foreach ( $fields as $field ) {
    $widget = $wpf_widgets[ $field['widget'] ];
    $value = $field_values[ $field['name'] ];    
    // call a cart_data_callback for a corresponding widget
    if ( is_array( $widget['cart_data_callback'] ) ) {
      $class = $widget['cart_data_callback'][0];      
      $method = $widget['cart_data_callback'][1];
      $data = $class->{$method}( $value, $product_id, $field, $field_values );
    } else if ( ! empty( $widget['cart_data_callback'] ) ) {
      $data = $widget['cart_data_callback']( $value, $product_id, $field, $field_values );
    }    
        
    if ( !empty( $value ) ) {
      $wpf_fields[ $field['name'] ] = array(
        'name'  => $field['name'],
        'title' => $field['title'],
        'value' => $data['value'],
        'charge' => $data['charge'],
        'multifield' => $data['multifield'],
        'chargeable' => $field['chargeable'],
        'unit' => $field['unit']
      );    
    } 
  }
  
  return $wpf_fields;
}

/**
 * Prepare wpf fields data to store in the cart object
 * 
 * @param decimal $charge
 * @param string | array $value
 * @param string | integer $product_id
 * @param string | array $field_values
 * @return  array
 * 'charge' => $charge,
   'value' => $value,
   'multifield' => true (checkboxes) | false (else)
 */
function wpf_calculate_callback_execute( $charge,
                                         $value,                                         
                                         $charge_type_name ) {  
  $charge_types = apply_filters( 'wpf_charge_types', array() );
  $charge_type = $charge_types[ $charge_type_name ];

  if ( is_array( $charge_type['calculate_callback'] ) ) {
    $class = $charge_type['calculate_callback'][0];      
    $method = $charge_type['calculate_callback'][1];
    $charge = $class->{$method}( $value, $charge, true );
  } else if ( ! empty( $charge_type['calculate_callback'] ) ) {
    $charge = $charge_type['calculate_callback']( $value, $charge, true );
  }

  return $charge;
}

/**
 * Get all fields
 * 
 * @return array
 */
function wpf_get_fields() {
  return WPF_Field_DS::instance()->get_all();
}

/**
 * Get all fields. Some fields are weighted by product id
 * @param  integer $product_id
 * 
 * @return array
 */
function wpf_get_all_with_weight( $product_id ) {
  return WPF_Field_DS::instance()->get_all_with_weight( $product_id );
}

/**
 * Get a field by its name
 * 
 * @param  string $name
 * @return array
 */
function wpf_get_field_by_name( $name ) {
  return  WPF_Field_DS::instance()->get_by_name( $name );
}

/**
 * Get the active fields (is_active = 1)
 * 
 * @param  integer  $product_id
 * @param  string $index - determines which column should be an index
 * @return array
 */
function wpf_get_active_fields( $product_id, $index = 'id' ) {
  $product_fields_visibility = WPF_Field_Product_DS::instance()
                               ->get_fields_visibility( $product_id );                               
  $product_fields_inactive = array();  
  $fields = array();
  foreach ( $product_fields_visibility as $item ) {
    if ( $item['is_active'] ) {
      $fields[$item[$index]] = $item;
    } else {
      $product_fields_inactive[] = $item[$index];
    }
  }
  $fields_visibility = wpf_get_fields(); 
  $weight = 250;
  foreach ( $fields_visibility as $item ) {
    // skip fields that was inactive on product form
    if ( in_array( $item[$index], $product_fields_inactive ) ) {
      continue;
    } else if ( $item['active_by_default'] && // is product active by default?
                !isset( $fields[$item[$index]] ) ) {
      $item['weight'] = $weight++;
      $fields[$item[$index]] = $item;
    }
  }    
  uasort( $fields, function ( $a, $b ) { 
    $result = 0;
    if ( $a['weight'] > $b['weight'] ) {
        $result = 1;
    } elseif ( $a['weight'] < $b['weight'] ) {
        $result = -1;
    }
    return $result;    
  } );
  return $fields;
}

/**
 * Get the active field names
 * 
 * @param  integer  $product_id
 * @return array
 */
function wpf_get_active_names( $product_id ) {    
  $fields = wpf_get_active_fields( $product_id );
  return array_column( $fields, 'name' );
}

/**
 * Analyze form data from js on Product page and prepare 
 * them for charges calculation
 * 
 * @param  array $form_data form data after .serializeArray() 
 * function on client side
 * @return array prepared form values in 
 *   $data[$name] = $field_value
 * format.
 */
function wpf_ajax_parse_form_data( $product_id, $form_data ) {
  $result = array();
  $fields = wpf_get_active_fields( $product_id, 'name' );
  $names = array_column( $fields, 'name' ); 
  global $wpf_widgets; 
  foreach ( $form_data as $form_item ) {
    $name = sanitize_text_field( $form_item['name'] );
    $single_name = str_replace( '[]', '', $name );
    $value = wpf_sanitize_value( $form_item['value'] );
    if ( in_array( $single_name, $names ) ) {
      // if checkboxes field - save value as array
      if ( ']' === substr( $name, -1 ) ) {
        if ( ! isset( $result[$single_name] ) ) {
          $result[$single_name] = array();
        }
        $result[$single_name][] = $value;
      } else if ( 'checkbox' === $wpf_widgets[ $fields[$single_name]['widget'] ]['type'] ) {
        $result[$single_name] = 1;
      } else {
        $result[$single_name] = $value;
      }
    }
  }

  return $result;
}

/**
 * Get widgets that declared with `wpf_widgets` hook
 * 
 * @return array
 */
function wpf_get_widget_names() {
  global $wpf_widgets;

  foreach ( $wpf_widgets as $id => $widget ) {
    $wpf_widgets[$id]['id'] = $id;
  }
  return array_column( $wpf_widgets, 'name', 'id' );
}

/**
 * Get charge types by widget that declared with `wpf_charge_types` hook 
 * 
 * @return array
 */
function wpf_get_charge_types( $widget ) {
  global $wpf_widgets;
  $charge_types = apply_filters( 'wpf_charge_types', array() ); 
  $charge_type_ids = $wpf_widgets[$widget]['charge_types'];
  $result = array();
  foreach ( $charge_type_ids as $id ) {
    $result[$id] = $charge_types[$id]['name'];
  }
  return $result;
}

/**
 * Calculate all fields charges by product
 * 
 * @param  integer  $product_id
 * @param  array  $field_values
 * @return decimal
 */
function wpf_calculate_fields_charges( $product_id, $field_values ) {        
  $charges = 0;  
  global $wpf_widgets;

  $charge_types = apply_filters( 'wpf_charge_types', array() );  
  foreach ( $field_values as $name => $field_value ) {
    $field = wpf_get_field_by_name( $name );
    $charge = $field['charge'];
    $widget = $field['widget'];       
    //
    $class = $wpf_widgets[$widget]['product_charge_callback'][0];
    $method = $wpf_widgets[$widget]['product_charge_callback'][1];
    $charge = $class->{$method}( $field_value,                                 
                                 $product_id,
                                 $field );    
    $charges += $charge;           
  }
  return $charges;
}

/**
 * Check if the product contains at least one wpf field
 *
 * @param  integer  $product_id
 * @return boolean
 */
function wpf_is_field_product( $product_id ) {  
  $product = wc_get_product( $product_id );  
  if ( $product->get_regular_price() === '' ) {
    return false;
  }
  $product_fields_visibility = WPF_Field_Product_DS::instance()
                               ->get_fields_visibility( $product_id );
  $product_fields_inactive = array();  
  foreach ( $product_fields_visibility as $item ) {
    if ( $item['is_active'] ) {
      return true;
    } else {
      $product_fields_inactive[] = $item['id'];
    }
  }
  $fields_visibility = wpf_get_fields(); 
  // check weither all fields were overridden as is_active = false
  if ( sizeof( $product_fields_inactive ) == sizeof( $fields_visibility ) ) {
    return false;
  }
  foreach ( $fields_visibility as $item ) {
    if ( in_array( $item['id'], $product_fields_inactive ) ) {
      continue;
    } else if ( $item['active_by_default'] ) {
      return true;
    }
  }
  return false;
}

/**
 * Get a value from the $_POST array or default value if it's not set
 * 
 * @param  string $key 
 * @param  string $default
 * @return string
 */
function wpf_get_post( $key, $default = 'off' ) {
  if ( isset( $_POST[$key] ) ) {
    return wpf_sanitize_value( $_POST[$key] );
  }
  return $default;  
}

/**
 * Get formatted field's options like [ Title ( +Price ) ]
 * 
 * @param  integer $field_id 
 * @return array           
 */
function wpf_get_field_options( $field_id, $formatted = true ) {
  if ( empty( $field_id ) ) {
    return array();
  }
  $options = WPF_Field_Option_DS::instance()->get_options_by_field_id( $field_id );   
  if ( ! $formatted ) {
    return $options;
  }
  $result = array();
  foreach ( $options as $option ) {
    $result[ $option['id'] ] = $option['title'];    
    
    if ( !empty( $option['price'] ) && 0 != $option['price'] ) {
      $result[ $option['id'] ] .= ' ' . wpf_price_wrapper( $option['price'] );
    }
  }
  
  return $result;
}

/**
 * Attach default values to the passed options
 * 
 * @param  array &$options
 * @param  string $default_value 
 */
function wpf_options_attach_default_props( &$options, $default_value = '' ) {
  foreach ( $options as $key => $option ) {          
    $options[$key]['is_overridden_price'] = false;
    $options[$key]['overridden_price'] = $option['price'];
    $options[$key]['is_overridden_value'] = false;
    $options[$key]['overridden_value'] = $default_value;
    $options[$key]['is_active'] = true;
    $options[$key]['is_active_option'] = true;
    
  }
}

/**
 * Get field product options
 * 
 * @param  integer $product_id
 * @param  integer $field_id 
 * @param  string $widget
 * @return array            
 */
function wpf_get_product_field_options( $product_id, $field_id, $widget ) {
  $options = WPF_Field_Product_Option_DS::instance()
              ->get_options( $product_id, $field_id, $widget );
  return $options;
}

/**
 * Get price in format like `(+ [symbol like $]45.05 )`
 * 
 * @param  decimal  $price
 * @param  boolean $currency_symbol
 * @return string
 */
function wpf_price_wrapper( $price, $charge_type = 'fixed_text', $show_info = false, $currency_symbol = false ) {
  if ( ! $currency_symbol ) {
    $currency_symbol = get_woocommerce_currency_symbol();
  }
  $info = '';
  if ( true === $show_info ) {
    $charge_types = apply_filters( 'wpf_charge_types', array() );
    $info = isset( $charge_types[$charge_type]['info'] ) ? 
          ' ' . $charge_types[$charge_type]['info'] : '';  
  }
  return '(+ '. $currency_symbol . $price . $info . ' )';
}

/**
 * Get info charge from the global wpf array.
 * Info charge means an option charge that only passed through wpf_charge_alter hook.
 * 
 * @param  integer  $product_id 
 * @param  string  $name 
 * @param  integer $option_id  
 * @return decimal (.2)        
 */
function wpf_global_get_charge( $product_id, $name, $option_id = 0 ) {
  global $wpf_products;
  if ( isset( $wpf_products[$product_id][$name] ) && 
       isset( $wpf_products[$product_id][$name]['charges'] ) &&
       isset( $wpf_products[$product_id][$name]['charges'][$option_id] ) ) {
    return wpf_format_charge( $wpf_products[$product_id][$name]['charges'][$option_id] );  
  }
  return 'unset';
}

/**
 * Turn string number or integer into decimal
 * "2" => 2.00, 1 => 1.00
 * 
 * @param  string | integer $charge 
 * @return decimal (*.2)
 */
function wpf_format_charge( $charge ) {
  return number_format( $charge, 2, '.', '' );
}

/**
 * Load all wpf widgets data and store it in global variable 
 */
function wpf_widgets_global_load() {  
  global $wpf_widgets;
  if ( ! empty( $wpf_widgets ) ) {
    return;
  }
  $wpf_widgets = apply_filters( 'wpf_widgets', array() );  
}

/**
 * Load all wpf data about product and store it as global array.
 * The global data is used with next cases:
 * 1) First total price calculation
 * 2) Show charge labels on Product page
 * The wpf data has been reloaded every time a wpf ajax call is started on Product page
 * The format:
 * array(
 *    [product_id] => array(
 *        name => array(
 *          name,
 *          charge_type,
 *          field_values => array(
 *              name1 => value1
 *              name2 => value2
 *              ...
 *          ),
 *          calculated_charge => [number], (sum of calculated charges) hooks: (wpf_charge_alter, charge_type_calculation ( text_fixed, per_char, ... ) )
 *          charges => array(
 *              [option_id] => [calculated_charge] hooks: (wpf_charge_alter)
 *              ...  
 *              [0] => [calculated_charge] ( option_id = 0 for text widgets and checkbox  )
 *          )
 *       )
 *    )
 * )
 * 
 * @param   integer $product_id    
 */
function wpf_product_charges_global_load( $product_id ) {  
  global $wpf_products;
  if ( isset( $wpf_products[$product_id] ) ) {
    return;
  }
  global $wpf_widgets;

  $fields = wpf_get_active_fields( $product_id );
  $field_values = array();    
  
  foreach ( $fields as $field ) {
    $type = $wpf_widgets[ $field['widget'] ]['type'];
    $name = $field['name'];
    $options = WPF_Field_Option_DS::instance()
                 ->get_options_by_field_id( $field['id'] );
    $wpf_products[$product_id][ $name ] = array( 
      'name' => $name,
      'widget' => $field['widget'],
      'charge_type' => $field['charge_type'],
      'charges' => array(),
      'visibility' => empty( $field['visibility'] ) ? '' : 
                      unserialize( $field['visibility'] )
    );    
    
    // setup default value
    if ( isset( $_POST[$name] ) ) {
      $field_values[$name] = wpf_sanitize_value( $_POST[$name] );
    } else if ( isset( $_GET['wpf'] ) && isset( $_GET[$name] ) ) {
      $field_values[$name] = wpf_sanitize_value( $_GET[$name] );
    } else if ( isset( $_POST['add-to-cart'] ) &&  
      'checkbox' === $type ) {
      $field_values[$name] = 0;
    } else if ( isset( $_POST['add-to-cart'] ) &&  
      'multi_option' === $type ) {
      $field_values[$name] = array();
    } else {              
      $field_values[$name] = wpf_get_value( $product_id, $field );
    }    
    // transform comma-separated string if value is not taken from $_POST
    // 'multi_option' === $widgets[ $field['widget'] ]['type']
    if ( 'multi_option' === $type && 
         ! is_array( $field_values[$name] ) ) {
      $field_values[$name] = ! empty( $field_values[$name] ) ? 
                              explode( ',', $field_values[$name] ) : array(); 
    }
    $wpf_products[$product_id][ $name ]['value'] = $field_values[$name];

    // 
    if ( in_array( $type, array( 'text', 'checkbox' ) ) ) {
      // make charge = 0 if chargeable is unchecked
      $charge = $field['chargeable'] ? $field['charge'] : 0;
      $wpf_products[$product_id][ $name ]['charges'][0] = wpf_get_charge( $product_id, $field['id'], 0, $charge ); 
    } else {
      foreach ( $options as $option ) {
        // make charge = 0 for every option if chargeable is unchecked
        $charge = $field['chargeable'] ? $option['price'] : 0;      
        $wpf_products[$product_id][ $name ]['charges'][ $option['id'] ] = wpf_get_charge( $product_id, $field['id'], $option['id'], $option['price'] );
      }
    }
  }
  
  $wpf_products[$product_id]['field_values'] = $field_values;    

  foreach ( $wpf_products[$product_id] as $field_id => $field ) {
    if ( ! isset( $wpf_products[$product_id][$field_id]['charges'] ) ) {
      continue;
    }
    $calculated_charge = 0;
    foreach ( $wpf_products[$product_id][$field_id]['charges'] as $option_id => $charge ) {      
      $charge = apply_filters( 'wpf_charge_alter', $charge, $field['value'], $field['name'], $field_values, true, $product_id );
      $wpf_products[$product_id][$field_id]['charges'][$option_id] = wpf_format_charge( $charge );
      if ( is_array( $field['value'] ) && in_array( $option_id, $field['value'] ) ||
           $field['value'] == $option_id ||
           !$option_id ) {        
        $calculated_charge += wpf_calculate_callback_execute( $charge, 
                                         $field['value'],
                                         $field['charge_type'] );
      }
    }
    $wpf_products[$product_id][$field_id]['calculated_charge'] = wpf_format_charge( $calculated_charge );    
  }  
}

/**
 * Check if the field is visible for the product
 * 
 * @param  integer $product_id           
 * @param  integer $field_id             
 * @param  boolean $is_active_by_default
 * @return boolean                       
 */
function wpf_is_field_visible( $product_id, $field_id, $is_active_by_default ) {
  $visibility = wpf_product_field_status( $product_id, $field_id );  
  if ( ( $visibility === -1 && ! $is_active_by_default ) || ! $visibility ) {
    return false;
  }
  return true;
}

/**
 * Check field's product visibility status
 * One of the three options:
 * -1 - Product is not related with the field directly (it will happen when some product exist while we create a new field)
 * 0 - The field is switched off for the product directly (on the Product page)
 * 1 - The field is switched on for the product directly (on the Product page)
 * 
 * @param  integer  $product_id
 * @param  integer  $field_id
 * @return boolean
 */
function wpf_product_field_status( $product_id, $field_id ) {
  $product_field = WPF_Field_Product_DS::instance()
                     ->get( $field_id, $product_id );   

  if ( empty( $product_field ) ) {
    return -1;
  }
  return $product_field['is_active'];
}

/**
 * Get the price pattern for html5 validation
 * 
 * @return string
 */
function wpf_get_price_pattern() {
  return '|(\d+(\.\d{1,2})?)';
}

/**
 * Get all registered image sizes
 * 
 * @return array
 */
function wpf_get_image_sizes( $formatted = true ) {
  global $_wp_additional_image_sizes;
  $image_sizes = array();
  $default_image_sizes = array( 'thumbnail', 'medium', 'large' );
  foreach ( $default_image_sizes as $size ) {
      $image_sizes[$size] = array(
          'width'  => intval( get_option( "{$size}_size_w" ) ),
          'height' => intval( get_option( "{$size}_size_h" ) ),
          'crop'   => get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false,
      );
  }
  if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
      $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
  }

  if ( $formatted ) {
    $formatted_sizes = array();
    foreach ( $image_sizes as $size_id => $image_size ) {
      $formatted_sizes[$size_id] = "{$size_id} ({$image_size['width']}x{$image_size['height']})";
      if ( $image_size['crop'] ) {
        $formatted_sizes[$size_id] .= ' (cropped)';
      }
    }
    return $formatted_sizes;
  }

  return $image_sizes;
}

/**
 * Get wpf units
 * 
 * @return  array 
 */
function wpf_get_units( $key = 0 ) {  
  $units = apply_filters( 'wpf_units', array() );  
  if ( ! empty( $key ) ) {
    return $units[ $key ];
  }
  return $units;
}

/**
 * Get all field profiles
 * @return  array
 */
function wpf_get_field_profiles() {
  return WPF_Field_Profile_DS::instance()->get_all();
}

/**
 * Get all field profiles
 * @return  array
 */
function wpf_fields_get_active_by_default() {
  return WPF_Field_DS::instance()->get_active_by_default();
}

/**
 * Get a field profile by id
 * @return  array
 */
function wpf_get_field_profile( $id ) {
  return WPF_Field_Profile_DS::instance()->get_one( null, $id );
}

/**
 * Converts an associative array to an XML/HTML tag attribute string.
 * 
 * @param  array  $attributes
 * @return string
 */
function wpf_attributes( array $attributes = array()  ) {
  foreach ( $attributes as $attribute => &$data ) {
    $data = implode( ' ', (array) $data );
    $data = $attribute . '="' . htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' ) . '"';
  }
  return $attributes ? ' ' . implode( ' ', $attributes ) : '';
}

/**
 * Check weither a field name is used by another field
 * @param  string $name 
 * @return boolean
 */
function wpf_is_name_unique( $name ) {
  return empty( WPF_Field_DS::instance()->check_name( $name ) );
}

/**
 * Check weither a field name is used by another field
 * @param  string $name 
 * @return boolean
 */
function wpf_is_profile_name_unique( $name, $except_current = false ) {
  return empty( WPF_Field_Profile_DS::instance()->check_name( $name, $except_current ) );
}

/**
 * Check weither user is on the Product add/edit form
 * Notice: This function is called from the `admin_enqueue_scripts` hook handler
 * 
 * @param  string $hook - parameter that is provided by `admin_enqueue_scripts` hook handler
 * @return boolean
 */
function wpf_is_product_form( $hook ) {
  if ( ( 'post-new.php' === $hook || 'post.php' === $hook )  ) {
    global $post;      
    if ( 'product' === $post->post_type ) {
      return true;
    }
  }
  return false;
}

/**
 * Get field ids by field profile name
 * @param  string $name
 * @return array
 */
function wpf_get_fields_by_profile_name( $name ) {
  return WPF_Field_Profile_DS::instance()->get_fields_by_name( $name );
}

/**
 * Sanitize a string or the elements of the array 
 * @param  array|string $value 
 * @return array|string
 */
function wpf_sanitize_value( $value ) {
  return is_array( $value ) ? 
         array_map( 'sanitize_text_field', $value ) : 
         sanitize_text_field( $value );
}