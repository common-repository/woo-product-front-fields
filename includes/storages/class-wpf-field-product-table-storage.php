<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Product_Table_Storage extends WPF_Table_Storage {
  public $table_name = 'wpf_fields_products';

  public function save( $wpf_data ) {
    global $wpdb;    
    $data = $wpf_data->get_data();    
    $id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}{$this->table_name} WHERE product_id = %d AND field_id = %d", array( $data['product_id'], $data['field_id'] ) ) );        
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
      $wpf_data->set_prop( 'id', $id );
    }
  }

  
  public function get_active_fields( $product_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare( "SELECT f.name FROM {$wpdb->prefix}{$this->table_name} fp INNER JOIN {$wpdb->prefix}wpf_fields f ON fp.field_id = f.id  WHERE fp.product_id = %d AND fp.is_active = 1", array( $product_id ) ), ARRAY_A );
  }

  public function delete_by_product( $product_id ) {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(         
      "DELETE fpo.* FROM {$wpdb->prefix}wpf_fields_products_options fpo
       INNER JOIN {$wpdb->prefix}wpf_fields_products fp ON fp.id = fpo.fpid
       WHERE fp.product_id = %d", $product_id 
    ) );
    
    $wpdb->delete( $wpdb->prefix . $this->table_name, array( 'product_id' => $product_id ) );
  }

  public function get_fields_visibility( $product_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare( "SELECT fp.field_id AS id, fp.is_active, fp.weight, f.* FROM {$wpdb->prefix}{$this->table_name} fp INNER JOIN {$wpdb->prefix}wpf_fields f ON fp.field_id = f.id WHERE fp.product_id = %d", array( $product_id ) ), ARRAY_A );
  }

  public function get_by_product_id( $product_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE  product_id = %d", array( $product_id ) ), ARRAY_A );
  }

  public function get( $field_id, $product_id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE field_id = %d AND product_id = %d", array( $field_id, $product_id ) ), ARRAY_A );
  }

  public function get_single_data( $field_id, $product_id, $option_id = 0 ) {
    global $wpdb;    
    return $wpdb->get_row( $wpdb->prepare( "SELECT fp.is_overridden_value, fp.value, fpo.is_overridden_price, fpo.price, fp.is_active AS field_is_active, fpo.is_overridden_title, fpo.title FROM {$wpdb->prefix}{$this->table_name} fp INNER JOIN {$wpdb->prefix}wpf_fields_products_options fpo ON fp.id = fpo.fpid WHERE fp.field_id = %d AND fp.product_id = %d AND fpo.option_id = %d", array( $field_id, $product_id, $option_id ) ), ARRAY_A );
  }

  public function get_by_id( $field_product_id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE id = %d", array( $field_id, $product_id ) ), ARRAY_A );
  }
}