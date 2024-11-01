<?php

defined( 'ABSPATH' ) || exit;

class WPF_Field extends WPF_Data {
  
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
  public function set_title( $value ) {
    $this->set_prop( 'title', $value );
  }  
  public function set_chargeable( $value ) {
    $this->set_prop( 'chargeable', $value === 'on' );
  }
  public function set_charge_type( $value ) {
    $this->set_prop( 'charge_type', $value );
  }
  public function set_charge( $value ) {
    $this->set_prop( 'charge', $value );
  }
  public function set_unit( $value ) {
    $this->set_prop( 'unit', $value );
  }
  public function set_default_value( $value ) {
    $this->set_prop( 'default_value', $value );
  }
  public function set_required( $value ) {
    $this->set_prop( 'required', $value === 'on' );
  }
  public function set_active_by_default( $value ) {
    $this->set_prop( 'active_by_default', $value === 'on' );
  }
  public function set_widget( $value ) {
    $this->set_prop( 'widget', $value );
  }    
  public function set_options_extra( $value ) {
    $this->set_prop( 'options_extra', $value );
  }  
  public function set_visibility( $value ) {
    if ( '' !== $value ) {
      $this->set_prop( 'visibility', serialize( $value ) );  
    } else {
      $this->set_prop( 'visibility', '' );
    }   
  }
  // getters
  public function get_id() {
    return $this->get_prop( 'id' );
  }
  public function get_name() {
    return $this->get_prop( 'name' );
  }
  public function get_title() {
    return $this->get_prop( 'title' );
  }
  public function get_charge() {
    return $this->get_prop( 'charge' );
  }  
  public function get_chargeable() {
    return $this->get_prop( 'chargeable' );
  }
  public function get_unit() {
    return $this->get_prop( 'unit' );
  }
  public function get_charge_type() {
    return $this->get_prop( 'charge_type' );
  }
  public function get_default_value() {
    return $this->get_prop( 'default_value' );
  }
  public function get_required() {
    return $this->get_prop( 'required' );
  }
  public function get_active_by_default() {
    return $this->get_prop( 'active_by_default' );
  }
  public function get_widget() {
    return $this->get_prop( 'widget' );
  }  
  public function get_options_extra() {
    return $this->get_prop( 'options_extra' );
  }  
  public function get_visibility() {
    $value = $this->get_prop( 'visibility' );
    if ( empty( $value ) ) {
      return '';
    }
    return unserialize( $value );
  }  
}