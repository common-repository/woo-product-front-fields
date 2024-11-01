<?php

defined( 'ABSPATH' ) || exit;

abstract class WPF_Table_Storage implements WPF_IStorage {
  public $table_name;
  
  public function save( $wpf_data ) {
    global $wpdb;
    $id_prop = $wpf_data->get_id_prop();
    $data = $wpf_data->get_data();
    //echo '<pre>'.print_r($data, true).'</pre>';
    
    $id = isset( $data[ $id_prop ] ) ? $data[ $id_prop ] : '';        
    //echo '<pre>'.print_r($id, true).'</pre>';
    unset( $data[ $id_prop ] );
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
        array( $id_prop => $id )
      );
    }
  }

  public function delete( $wpf_data, $id ) {
    global $wpdb;
    $id_prop = $wpf_data->get_id_prop();
    $wpdb->delete( $wpdb->prefix . $this->table_name, array( $id_prop => $id ) );
  }

  public function get_one( $wpf_data, $id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE id = %d", $id ), ARRAY_A );
  }

  public function get_all() {
    global $wpdb;
    $result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}{$this->table_name}", ARRAY_A );    
    return $result;
  } 
}