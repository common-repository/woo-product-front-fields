<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Product_Option_Table_Storage extends WPF_Table_Storage {
  public $table_name = 'wpf_fields_products_options';

  public function save( $wpf_data ) {
    global $wpdb;    
    $data = $wpf_data->get_data();
    $id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}{$this->table_name} WHERE fpid = %d AND option_id = %d", array( $data['fpid'], $data['option_id'] ) ) );    
    
    if ( empty( $id ) ) {
      $wpdb->insert( 
        $wpdb->prefix . $this->table_name,
        $data 
      );
      $wpf_data->set_prop( 'id', $wpdb->insert_id );
    } else {
      $wpdb->update( 
        $wpdb->prefix . $this->table_name, 
        $data, 
        array( 'id' => $id )
      );
    }
  }

  public function get_by_field_product_id( $field_product_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE fpid = %d", array( $field_product_id ) ), ARRAY_A );
  }

  public function get_overridden_options_count( $field_id, $option_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}{$this->table_name} fpo INNER JOIN {$wpdb->prefix}wpf_fields_products fp ON fpo.fpid = fp.id  WHERE fp.field_id = %d AND fpo.option_id = %d", array( $field_id, $option_id ) ) );
  }

  public function get_option( $product_id, $field_id, $option_id = 0 ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE product_id = %d AND field_id = %d AND option_id = %d", array( $product_id, $field_id, $option_id ) ), ARRAY_A );
  }  

  public function get_options( $product_id, $field_id, $widget ) {
    global $wpdb;
    global $wpf_widgets;  
    $type = $wpf_widgets[$widget]['type'];      
    if ( in_array( $type, array( 'text', 'checkbox' ) ) ) {
      return array( WPF_Field_Product_DS::instance()
                      ->get_single_data( $field_id, $product_id ) );
    }
    $field_options = WPF_Field_Option_DS::instance()->
                      get_options_by_field_id( $field_id );
    foreach ( $field_options as $key => $field_option ) {
      $field_product_option = WPF_Field_Product_DS::instance()
                                ->get_single_data( $field_id, 
                                                   $product_id, 
                                                   $field_option['id'] );
      $field_options[$key]['is_overridden_price'] = false;
      $field_options[$key]['overridden_price'] = $field_options[$key]['price'];
      $field_options[$key]['is_active'] = false;      
      if ( !empty( $field_product_option ) ) {
        $field_options[$key]['is_overridden_price'] = $field_product_option['is_overridden_price'];
        $field_options[$key]['overridden_price'] = $field_product_option['price'];
        $field_options[$key]['is_active'] = true;//$field_product_option['is_active'];      
      }
    }
    return $field_options;    
  }
}