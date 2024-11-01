<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Product_Option extends WPF_Data {
  
  public function __construct( $id = '' ) {
    parent::__construct( $id );
  }

  public function get_id_prop() {
    return 'id';
  }

  // setters
  public function set_field_product_id( $value ) {
    $this->set_prop( 'fpid', $value );
  }  
  public function set_option_id( $value ) {
    $this->set_prop( 'option_id', $value );
  } 
  public function set_is_active( $value ) {
    if ( $value === 'on' ) {
      $value = 1;
    } else if ( $value === 'off' ) {
      $value = 0;
    }
    $this->set_prop( 'is_active', $value );
  }  
  public function set_is_overridden_price( $value ) {
    if ( $value === 'on' ) {
      $value = 1;
    } else if ( $value === 'off' ) {
      $value = 0;
    }
    $this->set_prop( 'is_overridden_price', $value );
  }
  public function set_price( $value ) {
    $this->set_prop( 'price', $value );
  } 
  public function set_is_overridden_title( $value ) {
    if ( $value === 'on' ) {
      $value = 1;
    } else if ( $value === 'off' ) {
      $value = 0;
    }
    $this->set_prop( 'is_overridden_title', $value );
  }
  public function set_title( $value ) {    
    $this->set_prop( 'title', $value );
  }   
    
  // getters
  public function get_field_product_id() {
    return $this->get_prop( 'fpid' );
  }  
  public function get_option_id() {
    return $this->get_prop( 'option_id' );
  }
  public function get_is_active() {
    return $this->get_prop( 'is_active' );
  }
  public function get_is_overridden_price() {
    return $this->get_prop( 'is_overridden_price' );
  }
  public function get_price() {
    return $this->get_prop( 'price' );
  }
  public function get_is_overridden_title() {
    return $this->get_prop( 'is_overridden_title' );
  }
  public function get_title() {
    return $this->get_prop( 'title' );
  }  
}