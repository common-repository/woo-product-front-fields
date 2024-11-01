<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_DS extends WPF_Data_Store {

  public function set_storage( $mode ) {  
    switch ($mode) {
      case 'options':
        $this->storage = new WPF_Field_Options_Storage();
        break;
      case 'table':        
        $this->storage = new WPF_Field_Table_Storage();
        break;
    }
  }

  public function get_all() {
    return $this->storage->get_all();
  }

  public function get_active_by_default() {
    return $this->storage->get_active_by_default();
  }

  public function check_name( $name ) {
    return $this->storage->check_name( $name );
  }

  public function get_all_with_weight( $product_id ) {
    return $this->storage->get_all_with_weight( $product_id );
  }

  public function delete( $wpf_data, $id ) {
    return $this->storage->delete( $wpf_data, $id );
  }  

  public function get_by_name( $name ) {
    return $this->storage->get_by_name( $name );
  }   
}