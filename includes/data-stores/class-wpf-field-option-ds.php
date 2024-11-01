<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Option_DS extends WPF_Data_Store {

  public function set_storage( $mode ) {
    switch ($mode) {
      case 'options':
        $this->storage = new WPF_Field_Option_Options_Storage();
        break;
      case 'table':
        $this->storage = new WPF_Field_Option_Table_Storage();
        break;
    }
  }

  public function get_options_by_field_id( $field_id ) {
    return $this->storage->get_options_by_field_id( $field_id );
  }

  public function get_option_ids_by_field_id( $field_id ) {
    return $this->storage->get_option_ids_by_field_id( $field_id );
  }

  public function remove_diff_options( $ids, $field_id ) {
    return $this->storage->remove_diff_options( $ids, $field_id );
  }

  public function get_option( $option_id ) {
    return $this->storage->get_option( $option_id );
  }
}