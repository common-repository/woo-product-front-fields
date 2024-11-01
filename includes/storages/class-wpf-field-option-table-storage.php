<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Option_Table_Storage extends WPF_Table_Storage {
  public $table_name = 'wpf_fields_options';

  public function get_options_by_field_id( $field_id ) {
    global $wpdb;
    $result = array();
    $data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE field_id = %d ORDER BY weight", array( $field_id ) ), ARRAY_A );
    foreach ( $data as $item ) {
      $result[$item['id']] = $item;      
    }
    return $result;
  }

  public function get_option_ids_by_field_id( $field_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}{$this->table_name} WHERE field_id = %d ORDER BY weight", array( $field_id ) ), ARRAY_A );
  }

  public function get_option( $option_id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE id = %d", array( $option_id ) ), ARRAY_A );
  }

  public function remove_diff_options( $ids, $field_id ) {
    global $wpdb;    
    $wpdb->query( 
      $wpdb->prepare(         
        "DELETE FROM {$wpdb->prefix}{$this->table_name}
        WHERE field_id = %d
        AND id NOT IN (" . implode( ',', $ids ) . ")",        
        $field_id 
      )
    );    
    $wpdb->query( 
      $wpdb->prepare(         
        "DELETE fpo.* FROM {$wpdb->prefix}wpf_fields_products_options fpo
        INNER JOIN {$wpdb->prefix}wpf_fields_products fp ON fp.id = fpo.fpid
        WHERE fp.field_id = %d AND fpo.option_id NOT IN (" . implode( ',', $ids ) . ")",        
        $field_id 
      )
    );
  }
}