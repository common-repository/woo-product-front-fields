<?php

defined( 'ABSPATH' ) || exit;

interface WPF_IStorage {
  public function save( $wpf_data );
  public function delete( $wpf_data, $id );
  public function get_one( $wpf_data, $id );
  public function get_all();
}