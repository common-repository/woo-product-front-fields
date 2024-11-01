<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Profile_Options_Storage extends WPF_Options_Storage {
  public $option_id = 'wpf_field_profiles';

  public function get_fields_by_name( $name ) {
    $options_data = get_option( $this->option_id, array() );
    $result = array_column( $options_data, 'fields', 'name' );
    return explode( ',', $result[$name] );
  }

  public function check_name( $name, $initial_name ) {        
    $options_data = get_option( $this->option_id, array() );    
    $names = array_column( $options_data, 'name' );                      
    if ( $name === $initial_name ) {      
      if ( ( $key = array_search( $initial_name, $names ) ) !== false ) {
        unset( $names[$key] );
      }
    }
    return in_array( $name, $names );
  }
}