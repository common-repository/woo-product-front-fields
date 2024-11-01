<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Options_Storage extends WPF_Options_Storage {
  public $option_id = 'wpf_fields';

  public function get_by_name( $wpf_data, $name ) {
    if ( empty( $name ) ) {
      wp_die( "{$wpf_data->get_id_prop()} is missed" );
    }    
    $options_data = get_option( $this->option_id, false ); 
    $id = array_search( $name, array_column( $options_data, 'name' ) );    
    return $options_data[ $id ];           
  }
}