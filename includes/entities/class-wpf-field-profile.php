<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Profile extends WPF_Data {
  
  public function __construct( $id = '' ) {
    parent::__construct( $id );
  }

  public function get_id_prop() {
    return 'id';
  }

  // setters  
  public function set_name( $value ) {
    $this->set_prop( 'name', $value );
  }
  public function set_fields( $value ) {
    $this->set_prop( 'fields', $value );
  }  
  // getters
  public function get_id() {
    return $this->get_prop( 'id' );
  }
  public function get_name() {
    return $this->get_prop( 'name' );
  }  
  public function get_fields() {
    return $this->get_prop( 'fields' );
  }  
}