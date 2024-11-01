<?php

defined( 'ABSPATH' ) || exit;

abstract class WPF_Data_Store extends WPF_Singleton {  
  const STORE_MODE        = 'wpf_store_mode';

  protected $mode = '';  
  
  protected $options_data = array();
  protected $table_data = array();

  public $storage;

  abstract public function set_storage( $mode );  

  public function save( $wpf_data ) {    
    $this->storage->save( $wpf_data );
  }

  public function delete( $wpf_data, $id ) {
    $this->storage->delete( $wpf_data, $id );
  }

  public function get_one( $wpf_data, $id ) {
    return $this->storage->get_one( $wpf_data, $id );
  }

  public function get_all() {    
    return $this->storage->get_all();
  }  
}