<?php

defined( 'ABSPATH' ) || exit;

/**
 * The front-specific functionality of the plugin. 
 *
 * @since   1.0.0
 */
class WPF_Front {
  /**
   * The ID of this plugin.     
   *
   * @var string $plugin_name
   */
  private $plugin_name;
  /**
   * The version of this plugin.   
   *
   * @var string $version
   */
  private $version;

  /**
   * Initialize the class and set its properties.
   *
   * @param string $plugin_name
   * @param string $version
   */
  public function __construct( $plugin_name, $version ) {
      $this->plugin_name = $plugin_name;
      $this->version = $version;
  }  

  /**
   * hook-handler: wp_enqueue_scripts
   * Register the stylesheets for the Product page
   */
  public function enqueue_styles() {        
      wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpf-front.css', array(), $this->version, 'all' );
  }

  /**
   * hook-handler: wp_enqueue_scripts
   * Register the JavaScript files for the Product page
   */
  public function enqueue_scripts() {        
    global $post; 
    if ( ! is_object( $post ) ) {
      return;
    }        
    if ( is_product() ) {      
      if ( 'storefront' == wp_get_theme()->template ) {
        wp_enqueue_script( $this->plugin_name.'_sticky', plugin_dir_url( __FILE__ ) . 'js/wpf-product-sticky.js', array( 'jquery' ), $this->version, false );
      } 
      wp_enqueue_script( $this->plugin_name.'_front_fields', plugin_dir_url( __FILE__ ) . 'js/wpf-front.js', array( 'jquery' ), $this->version, false );
      wp_localize_script(
        $this->plugin_name.'_front_fields',
        'wpf_product',
        array(
          'id' => $post->ID,
          'throbber_path' => plugin_dir_url( __FILE__ ) . 'images/throbber-active.gif',
          'ajax_url' => admin_url( 'admin-ajax.php' )                     
        )
      );
    }
  }

  /**
   * hook-handler: wp_ajax_wc_price, wp_ajax_nopriv_wc_price
   * Ajax callback to change the price after front fields have been changed     
   */
  public function ajax_get_formatted_price() {        
    if ( isset( $_POST[ "product_id" ] ) ) {
      $product_id = sanitize_text_field( $_POST[ "product_id" ] );      
      $form_data = $_POST[ 'form_data' ];
      // sanitize $_POST['form_data'] inside wpf_ajax_parse_form_data()
      $form_values = wpf_ajax_parse_form_data( $product_id, $form_data );     
      $_POST = $_POST + $form_values;
      $field_id = sanitize_text_field( $_POST[ "field_id" ] );      
      
      wpf_product_charges_global_load( $product_id );      
      wpf_widgets_global_load();

      $field = new WPF_Field( $field_id );        
      $widget = $field->get_widget();            

      $product  = wc_get_product( $product_id );
      $regular_price  = $product->get_regular_price();
      $sale_price  = $product->get_sale_price();           
      
      global $wpf_widgets;
      $type = $wpf_widgets[$widget]['type'];

      // check if the charge is unset for the field
      if ( in_array( $type, array( 'text', 'checkbox' ) ) && 
           '' == $field->get_charge() ) {
        wp_send_json( array( "status" => false ) );
        wp_die();
      }
      $charges = wpf_calculate_fields_charges( $product_id, $form_values );      
      $data = array();                                                    
      $data['updated_raw_regular_price'] = $regular_price + $charges;
      $data['updated_formatted_regular_price'] = wc_price( $data['updated_raw_regular_price'] );
      if ( $product->is_on_sale() ) {
        $data['updated_raw_sale_price'] = $sale_price + $charges;
        $data['updated_formatted_sale_price'] = wc_price( $data['updated_raw_sale_price'] );
      }
      
      wp_send_json( array( "status" => true, "data" => $data ) );
      wp_die();        
    }

    wp_send_json( array( "status" => false, "data" => "Error" ) );
    wp_die();        
  } 

  /**
   * hook-handler: woocommerce_add_to_cart_validation
   * Add the validation for the front fields
   *
   * @param boolean $passed
   * @param integer $product_id
   * @param integer $quantity
   * @param boolean $variation_id
   * @param boolean $variations
   * @param boolean $cart_item_data
   */    
  public function add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = false, $variations = false, $cart_item_data = false ) {   
      wpf_product_charges_global_load( $product_id );                               
      global $wpf_products;
      $field_values = $wpf_products[$product_id]['field_values'];
      $fields = wpf_get_active_fields( $product_id );            
      foreach ( $fields as $field ) {
        if ( ! $field['required'] ) {
          continue;
        }       
        $default_value = $field_values[ $field['name'] ];        
        if ( empty( $default_value ) ) {
          wc_add_notice( "The `{$field['title']}` is required", 'error' );
          $passed = false;
        }
      }     
            
      return $passed;
  }

  /**
   * hook-handler: woocommerce_before_add_to_cart_button
   */
  public function before_add_to_cart_button() {
    if ( 'before_addtocart' === get_option( 'wpf_fields_location', 'before_addtocart' ) ) {
      $this->render_front_fields(); 
    } 
  }   

  /**
   * hook-handler: woocommerce_after_add_to_cart_button
   */
  public function after_add_to_cart_button() {
    if ( 'after_addtocart' === get_option( 'wpf_fields_location', 'before_addtocart' ) ) {
      $this->render_front_fields(); 
    }
  }

  /**
   * Render front fields on the Product single page
   */
  protected function render_front_fields() {
    global $product;        
    $product_id = $product->get_id();    
    $fields = wpf_get_active_fields( $product_id ); 
    global $wpf_products;
    $field_values = $wpf_products[$product_id]['field_values'];
    foreach ( $fields as $field ) {        
      $widget = $field['widget'];
      $default_value = $field_values[ $field['name'] ];     
      $view_class = new $widget( $field, $product_id, $default_value );      
      
      /**
       * Hook: wpf_field_view_before.       
       */
      do_action( 'wpf_field_view_before', $field, $product_id );
      
      $view_class->render();
      
      /**
       * Hook: wpf_field_view_after.       
       */
      do_action( 'wpf_field_view_after', $field, $product_id );
    }
  }

  /**
   * hook-handler: woocommerce_get_price_html
   * Calls for Main Product price on Product page and
   * for all Product teasers (Related products block, Shop page)     
   *
   * @param  string $price
   * @param  WC_Product $product
   * @return string wc_price() or wc_format_sale_price()
   */    
  public function get_custom_price_html( $price, $product ) {
    $product_id = $product->get_id();
    if ( ! wpf_is_field_product( $product_id ) ) {
      return $price;
    }    
    wpf_product_charges_global_load( $product_id );
    global $wpf_products;    
    $field_values = $wpf_products[$product_id]['field_values'];
    $charges = wpf_calculate_fields_charges( $product_id, $field_values );      
    if ( $product->is_on_sale() ) {
      return wc_format_sale_price( $product->get_regular_price() + $charges, 
                                   $product->get_sale_price() + $charges );
    } else {
      return wc_price( $product->get_regular_price() + $charges );
    }      
  }    
  
  /**
   * hook-handler: woocommerce_add_cart_item_data
   * Add wpf fields data to the cart array (happens after clicking 
   * on Add to cart button)  
   * 
   * @param array $cart_items
   * @param integer $product_id
   * @param integer $variation_id
   */
  public function add_cart_item_data( $cart_items, $product_id = 0, $variation_id = 0 ) {      
    wpf_product_charges_global_load( $product_id );
    global $wpf_products;    
    
    if ( ! $product_id ) {
      return $cart_items;
    }
    $wpf_fields = wpf_get_cart_data( $product_id );    
    if ( ! empty( $wpf_fields ) ) {
      $cart_items['wpf_fields'] = $wpf_fields;          
    }            

    return $cart_items;
  }

  /**
   * hook-handler: woocommerce_add_cart_item
   * Calculate product's price (happens after clicking 
   * on the Add to cart button)
   * 
   * @param array $cart_items
   * @return array
   */
  public function add_cart_item( $cart_items ) {      
    if ( !isset( $cart_items['wpf_fields'] ) ) {
      return $cart_items;
    }
    $fields_charge = 0;
    foreach ( $cart_items['wpf_fields'] as $field ) {        
      $fields_charge += $field['charge'];
    }            
    $cart_items['data']->set_price( 
      ( $cart_items['data']->is_on_sale() ? 
        $cart_items['data']->get_sale_price() : 
        $cart_items['data']->get_regular_price() ) + $fields_charge
    );
    return $cart_items;
  }

  /**
   * hook-handler: woocommerce_get_cart_item_from_session
   * Take product data from the session (happens after clicking 
   * on the Add to cart button)
   *
   * @param  array $cart_items
   * @param  array $values    
   * @return array            
   */    
  public function get_cart_item_from_session( $cart_items, $values ) {
      $cart_items = $this->add_cart_item( $cart_items );
      return $cart_items;
  }

  /**
   * hook-handler: woocommerce_loop_add_to_cart_link
   * Change the `Add to cart` link behaivour for the products with 
   * front fields (happens on the Shop page, Relacted products section)
   *
   * @param  string $link
   * @param  WC_Product $product
   * @return string
   */    
  public function loop_add_to_cart_link( $link, $product ) {      
    if ( wpf_is_field_product( $product->get_id() ) ) {
      $link = sprintf( '<a rel="nofollow" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s">%s</a>',
        esc_url( $product->get_permalink() ),
        esc_attr( isset( $quantity ) ? $quantity : 1 ),
        esc_attr( $product->get_id() ),
        esc_attr( $product->get_sku() ),
        esc_attr( 'button add_to_cart_button' ),
        esc_html( __( get_option( 'wpf_product_teaser_button_text', 'Select options' ), 'wpf' ) )
      );
    }

    return $link;
  }    

  /**
   * hook-handler: woocommerce_get_item_data
   * Show products on the cart, cart popup and checkout page
   *
   * @param  array $item_data 
   * @param  array $cart_items
   * @return array            
   */
  public function get_item_data( $item_data, $cart_items = null ) {
    $item_data = is_array($item_data) ? $item_data : array();     
    // take extra data from the cart object        
    $wpf_fields = $cart_items && isset( $cart_items['wpf_fields'] ) ? $cart_items['wpf_fields'] : false;
    if ( $wpf_fields ) {   
      if ( 'yes' === get_option( 'wpf_base_price_info', 'yes' ) && $cart_items['data']->get_regular_price() > 0 ) {
        $product = $cart_items['data'];
        $item_data['__base'] = array( 
          'name' => __( get_option( 'wpf_base_price_label', 'Price' ), 'wpf' ), 
          'value' => $product->is_on_sale() ? 
            wc_format_sale_price( $product->get_regular_price(), $product->get_sale_price() ) : 
            wc_price( $product->get_regular_price() )
        );
      }
      foreach ( $cart_items['wpf_fields'] as $field ) {
        $value = trim( stripslashes( $field['value'] ) ); 
        $price_wrapper = '';          
          if ( $field['charge'] > 0 &&
               empty( $field['multifield'] ) && 
               !empty( $field['chargeable'] ) ) {

            $price_wrapper = ' ' . wpf_price_wrapper( $field['charge'] );
          }
          
        
        $unit = ! empty( $field['unit'] ) ? " ". wpf_get_units( $field['unit'] ) : '';
        $item_data[] = array( 'name' => $field['title']  , 'value' => $value . $unit . $price_wrapper );              
      }                  
    }        
    return $item_data;
  }
  
  /**
   * hook-handler: woocommerce_add_order_item_meta
   * Save metadata for the order (happens after Order creation)    
   *
   * @param  integer $item_id
   * @param  array $cart_items
   * @param  integer $cart_key     
   */
  public function order_item_meta( $item_id, $cart_items, $cart_key ) {        
    $wpf_fields = $cart_items && isset( $cart_items['wpf_fields'] ) ? $cart_items['wpf_fields'] : false;
    if ( $wpf_fields ) {
      if ( 'yes' === get_option( 'wpf_base_price_info', 'yes' ) && $cart_items['data']->get_regular_price() > 0 ) {
        $product = $cart_items['data'];        
        $name = __( get_option( 'wpf_base_price_label', 'Price' ), 'wpf' );
        $value = $product->is_on_sale() ? 
            wc_format_sale_price( $product->get_regular_price(), $product->get_sale_price() ) : 
            wc_price( $product->get_regular_price() );        
        wc_add_order_item_meta( $item_id, $name, $value );        
      }
      foreach ( $cart_items['wpf_fields'] as $field ) {
        $value = trim( stripslashes( $field['value'] ) );                
        $price_wrapper = '';        
        if ( $field['charge'] > 0 &&
             empty( $field['multifield'] ) && 
             !empty( $field['chargeable'] ) ) {
            $price_wrapper = ' ' . wpf_price_wrapper( $field['charge'] );
        }
        $unit = ! empty( $field['unit'] ) ? ' ' . wpf_get_units( $field['unit'] ) : '';
        $value = $value . $unit . $price_wrapper;
        wc_add_order_item_meta( $item_id, $field['title'], $value );
      }
    }                
  }

  /**
   * hook-handler: wp_ajax_compare_dependency, wp_ajax_nopriv_compare_dependency
   * Ajax callback to compare if the expected value of the dependent field is the same with the value of  the target field
   */
  /*
    @TODO - Field dependency feature
  public function ajax_compare_dependency() {
    if ( isset( $_POST[ "target_field_id" ] ) ) {
      $product_id = sanitize_text_field( $_POST[ "product_id" ] ); 
      $target_field_id = sanitize_text_field( $_POST[ "target_field_id" ] );
      $target_type = sanitize_text_field( $_POST[ "target_type" ] );
      $expected_value = sanitize_text_field( $_POST[ "expected_value" ] );
      $operation = sanitize_text_field( $_POST[ "operation" ] );  
      $form_data = $_POST[ 'form_data' ];
      $form_values = wpf_ajax_parse_form_data( $product_id, $form_data );      
      
      $compare = false;
      global $wpf_widgets;
      switch ( $wpf_widgets[$target_type]['type'] ) {
        case 'text':              
          switch ( $operation ) {
            case 'equal':
              $compare = $form_values[$target_field_id] === $expected_value;
              break;
            case 'not_equal':
              $compare = $form_values[$target_field_id] !== $expected_value;
              break;
            case 'gt':
              $compare = $form_values[$target_field_id] > $expected_value;
              break;
            case 'lt':
              $compare = $form_values[$target_field_id] < $expected_value;
              break;
          }
          break;
        case 'checkbox':
          $value = !isset( $form_values[$target_field_id] ) ? 0 : 1;
          $compare = $value === $expected_value;
          break;
        case 'single_option':        
          $compare = $value === $expected_value;
          break;
        case 'multi_option':              
          $compare = in_array( $expected_value, $value );
          break;
      }
      
      wp_send_json( array( "status" => true, 'compare' => $compare ) );
        wp_die();
    }
    wp_send_json( array( "status" => false ) );
    wp_die();
  }
  */       
}