<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Option extends WPF_Data {
  
  public function __construct( $id = '' ) {
    parent::__construct( $id );
  }

  public function get_id_prop() {
    return 'id';
  }

  // setters
  public function set_field_id( $value ) {
    $this->set_prop( 'field_id', $value );
  }  
  public function set_name( $value ) {
    $this->set_prop( 'name', $value );
  }
  public function set_option_data( $value ) {
    $this->set_prop( 'data', $value );
  }
  public function set_option_title( $value ) {
    $this->set_prop( 'title', $value );
  }
  public function set_option_value( $value ) {
    $this->set_prop( 'value', $value );
  }
  public function set_option_price( $value ) {
    $this->set_prop( 'price', $value );
  }  
  public function set_option_weight( $value ) {
    $this->set_prop( 'weight', $value );
  }
  // getters
  public function get_id() {
    return $this->get_prop( 'id' );
  }
  public function get_field_id() {
    return $this->get_prop( 'field_id' );
  }  
  public function get_name() {
    return $this->get_prop( 'name' );
  }
  public function get_option_data() {
    return $this->get_prop( 'data' );
  }
  public function get_option_title() {
    return $this->get_prop( 'title' );
  }
  public function get_option_value() {
    return $this->get_prop( 'value' );
  }
  public function get_option_price() {
    return $this->get_prop( 'price' );
  }
  public function get_option_weight() {
    return $this->get_prop( 'weight' );
  }  
}