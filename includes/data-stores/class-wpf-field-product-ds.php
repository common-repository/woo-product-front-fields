<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Product_DS extends WPF_Data_Store {

  public function set_storage( $mode ) {
    switch ($mode) {
      case 'options':
        $this->storage = new WPF_Field_Product_Options_Storage();
        break;
      case 'table':
        $this->storage = new WPF_Field_Product_Table_Storage();
        break;
    }
  }
  
  public function get_active_fields( $product_id ) {
    return $this->storage->get_active_fields( $product_id );
  }

  public function delete_by_product( $product_id ) {
    return $this->storage->delete_by_product( $product_id );
  }

  public function get_single_data( $field_id, $product_id, $option_id = 0 ) {
    return $this->storage->get_single_data( $field_id, $product_id, $option_id );
  }

  public function get( $field_id, $product_id ) {
    return $this->storage->get( $field_id, $product_id );
  }

  public function get_fields_visibility( $product_id ) {
    return $this->storage->get_fields_visibility( $product_id );
  }

  public function get_by_product_id( $product_id ) {
    return $this->storage->get_by_product_id( $product_id );
  }

  public function get_by_id( $field_product_id ) {
    return $this->storage->get_by_id( $field_product_id );
  }
}