<?php

defined( 'ABSPATH' ) || exit;

abstract class WPF_Options_Storage implements WPF_IStorage {
  public $option_id;
    
  public function save( $wpf_data ) {
    $id_prop = $wpf_data->get_id_prop();
    $data = $wpf_data->get_data();
    $id = isset( $data[ $id_prop ] ) ? $data[ $id_prop ] : '';        
    $options_data = get_option( $this->option_id, array() );
    if ( empty( $id ) ) {       
      $id = sizeof( $options_data ) ? ( max( array_keys( $options_data ) ) + 1 ) : 1;      
      $data[ $id_prop ] = $id;
      $wpf_data->set_prop( 'id', $id );
    }
    $options_data[ $id ] = $data;          
    if ( false === (bool) $options_data ) {
      add_option( $this->option_id, $options_data );
    } else {
      update_option( $this->option_id, $options_data );
    }        
  }
  
  public function delete( $wpf_data, $id ) {
    if ( empty( $id) ) {
      wp_die( "{$wpf_data->get_id_prop()} is missed" );
    }    
    $options_data = get_option( $this->option_id, false );        
    unset( $options_data[ $id ] );        
    if ( false === $options_data ) {
      add_option( $this->option_id, $options_data );
    } else {
      update_option( $this->option_id, $options_data );
    }
  }

  public function get_one( $wpf_data, $id ) {
    if ( empty( $id ) ) {
      wp_die( "{$wpf_data->get_id_prop()} is missed" );
    }    
    $options_data = get_option( $this->option_id, false );     
    return isset( $options_data[ $id ] ) ? $options_data[ $id ] : false;           
  }

  public function get_all() {                
    $options_data = get_option( $this->option_id, array() );     
    return $options_data;           
  }
}