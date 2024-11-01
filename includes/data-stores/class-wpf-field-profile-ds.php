<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Profile_DS extends WPF_Data_Store {

  public function set_storage( $mode = '' ) {  
    $this->storage = new WPF_Field_Profile_Options_Storage();
  }

  public function get_fields_by_name( $name ) {
    return $this->storage->get_fields_by_name( $name );
  }

  public function check_name( $name, $initial_name ) {
    return $this->storage->check_name( $name, $initial_name );
  }
}