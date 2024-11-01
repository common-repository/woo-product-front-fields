<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Table_Storage extends WPF_Table_Storage {
  public $table_name = 'wpf_fields';  

  public function get_all() {
    global $wpdb;
    $fields = array();
    $result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}{$this->table_name}", ARRAY_A );    
    foreach ( $result as $item ) {
      $fields[$item['id']] = $item;
    }
    return $fields;
  }

  public function get_active_by_default() {
    global $wpdb;    
    $result = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}{$this->table_name} WHERE active_by_default = 1", ARRAY_A );
    $ids = array();
    foreach ( $result as $item ) {
      $ids[] = $item['id'];
    }        
    return $ids;
  }

  public function check_name( $name ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}{$this->table_name} WHERE name = %s", $name ), ARRAY_A );
  }

  public function get_all_with_weight( $product_id ) {
    global $wpdb;
    $result = $wpdb->get_results( $wpdb->prepare(         
      "SELECT f.*, IFNULL(fp.weight, 100) AS weight FROM {$wpdb->prefix}wpf_fields f LEFT JOIN (SELECT field_id, weight FROM {$wpdb->prefix}wpf_fields_products WHERE product_id = %d) fp ON f.id = fp.field_id ORDER BY weight ASC", $product_id ), ARRAY_A );
    return $result;
  }

  public function delete( $wpf_data, $field_id ) {
    global $wpdb;
    
    // delete from wpf_fields_products_options
    $wpdb->query( $wpdb->prepare(         
      "DELETE fpo.* FROM {$wpdb->prefix}wpf_fields_products_options fpo
       INNER JOIN {$wpdb->prefix}wpf_fields_products fp ON fp.id = fpo.fpid
       WHERE fp.field_id = %d", $field_id 
    ) );    
    // delete from wpf_fields_products        
    $wpdb->query( $wpdb->prepare(         
      "DELETE FROM {$wpdb->prefix}wpf_fields_products 
       WHERE field_id = %d", $field_id 
    ) );    
    // delete from wpf_fields_options
    $wpdb->query( $wpdb->prepare(         
      "DELETE FROM {$wpdb->prefix}wpf_fields_options 
       WHERE field_id = %d", $field_id 
    ) );    
    // delete from wpf_fields    
    $wpdb->delete( $wpdb->prefix . $this->table_name, array( 'id' => $field_id ) );
  } 

  public function get_by_name( $name ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE name = %s", $name ), ARRAY_A );
  }  
}