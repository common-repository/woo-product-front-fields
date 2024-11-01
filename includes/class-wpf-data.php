<?php

defined( 'ABSPATH' ) || exit;

abstract class WPF_Data {

  protected $data = array();  

  protected $data_store;

  public function __construct( $id ) {
    // static::class
    $data_store_class = static::class . '_DS';
    $this->data_store = $data_store_class::instance();
    if ( ! empty( $id ) ) {
      $this->data = $this->data_store->get_one( $this, $id );                
    }
  }

  public function get_id_prop() {

  }

  public function get_data() {
      return $this->data;
  }

  public function set_prop( $key, $value ) {
    $this->data[$key] = $value;
  }

  public function get_prop( $key ) {        
    return $this->data[$key];
  }

  public function save() {        
    $this->data_store->save( $this );    
  }

  public function delete() {   
    $id_prop = $this->get_id_prop(); 
    $id = $this->get_data()[$id_prop];    
    $this->data_store->delete( $this, $id );    
  }
}