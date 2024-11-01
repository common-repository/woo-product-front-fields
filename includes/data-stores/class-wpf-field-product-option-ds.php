<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Product_Option_DS extends WPF_Data_Store {

  public function set_storage( $mode ) {
    switch ($mode) {
      case 'options':
        $this->storage = new WPF_Field_Product_Option_Options_Storage();
        break;
      case 'table':
        $this->storage = new WPF_Field_Product_Option_Table_Storage();
        break;
    }
  }

  public function get_option( $product_id, $field_id, $option_id = 0 ) {
    return $this->storage->get_option( $product_id, $field_id, $option_id );
  }
  
  public function get_by_field_product_id( $field_product_id ) {
    return $this->storage->get_by_field_product_id( $field_product_id );
  }  

  public function get_overridden_options_count( $field_id, $option_id ) {
    return $this->storage->get_overridden_options_count( $field_id, $option_id );
  }  

  public function get_options( $product_id, $field_id, $widget ) {
    return $this->storage->get_options( $product_id, $field_id, $widget );
  }  
}