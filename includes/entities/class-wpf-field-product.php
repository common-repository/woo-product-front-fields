<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field_Product extends WPF_Data {
  
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
  public function set_product_id( $value ) {
    $this->set_prop( 'product_id', $value );
  }
  public function set_variation_id( $value ) {
    $this->set_prop( 'variation_id', $value );
  }
  public function set_weight( $value ) {
    $this->set_prop( 'weight', $value );
  }  
  public function set_is_active( $value ) { 
    if ( $value === 'on' ) {
      $value = 1;      
    } else if ( $value === 'off' ) {
      $value = 0;
    }              
    $this->set_prop( 'is_active', $value );
  }    
  public function set_is_overridden_value( $value ) {
    if ( $value === 'on' ) {
      $value = 1;
    } else if ( $value === 'off' ) {
      $value = 0;
    }
    $this->set_prop( 'is_overridden_value', $value );
  }
  public function set_value( $value ) {
    if ( $value === 'on' ) {
      $value = 1;
    } else if ( $value === 'off' ) {
      $value = 0;
    }
    $this->set_prop( 'value', $value );
  }   
    
  // getters
  public function get_id() {
    return $this->get_prop( 'id' );
  }
  public function get_field_id() {
    return $this->get_prop( 'field_id' );
  }
  public function get_product_id() {
    return $this->get_prop( 'product_id' );
  }
  public function get_variation_id() {
    return $this->get_prop( 'variation_id' );
  }
  public function get_weight() {
    return $this->get_prop( 'weight' );
  }
  public function get_is_active() {
    return $this->get_prop( 'is_active' );
  } 
  public function get_is_overridden_value() {
    return $this->get_prop( 'is_overridden_value' );
  }
  public function get_value() {
    return $this->get_prop( 'value' );
  }    
}