<?php

defined( 'ABSPATH' ) || exit;

/**
 * The product add/edit form functionality of the plugin.
 *
 * @since   1.0.0
 */
class WPF_Product_Form {    
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
   * The fields from the storage.
   *   
   * @var string  $fields  
   */
  private $fields;  

  /**
   * Initialize the class and set its properties.
   *   
   * @param string $plugin_name
   * @param string $version
   */   
  public function __construct( $plugin_name, $version ) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    $this->fields = array();     
  }

  /**
   * Retrive all fields      
   */   
  private function retrive_fields() {
    global $post;      
    if ( isset( $post->ID ) && 'auto-draft' !== $post->post_status ) {
      $this->fields = wpf_get_all_with_weight( $post->ID );      
    } else {
      $this->fields = wpf_get_fields();
      if ( isset( $_GET['fpid'] ) ) {
        $profile_id = sanitize_text_field( $_GET['fpid'] );
        $field_profile = wpf_get_field_profile( $profile_id );                    
        $fids = explode( ',', $field_profile['fields'] );        
        foreach ( $this->fields as $key => $field ) {
          if ( in_array( $key, $fids ) ) {
            $this->fields[$key]['active_by_default'] = 1;
          } else {            
            $this->fields[$key]['active_by_default'] = 0;
          }
        }                      
        $active_fields = array();
        foreach ( $fids as $fid ) {
          if ( !isset( $this->fields[$fid] ) ) {
            continue;
          }
          $active_field = $this->fields[$fid];
          unset( $this->fields[$fid] );
          $active_fields[$fid] = $active_field;           

        }            
        
        $this->fields = $active_fields + $this->fields;          
      }
    }                   
  }

  /**
   * hook handler: admin_enqueue_scripts
   * Register the stylesheets
   */
  public function enqueue_styles( $hook ) {      
    if ( wpf_is_product_form( $hook ) ) {
      wp_enqueue_style( $this->plugin_name . get_class($this), plugin_dir_url( __FILE__ ) . 'css/wpf-product-form.css', array(), $this->version, 'all' );
    }    
  } 

  /**
   * hook handler: admin_enqueue_scripts
   * Register the JavaScript
   */
  public function enqueue_scripts( $hook ) { 
    if ( wpf_is_product_form( $hook ) ) {
      $script_id = $this->plugin_name . get_class($this); 
      wp_enqueue_script( $script_id, plugin_dir_url( __FILE__ ) . 'js/wpf-product-form.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, false );
      wp_localize_script(
        $script_id,
        'wpf_product_form',
        array(                        
          'ajax_url' => admin_url( 'admin-ajax.php' )                     
        )
      );      
    }
  }
  
  /**
   * hook handler: woocommerce_product_data_tabs   
   * 
   * @param array $tabs
   */
  public function product_data_tabs( $tabs ) { 
    $this->retrive_fields(); 
    if ( sizeof( $this->fields ) ) {       
      $tabs['wpf_front_fields'] = array(
        'label'   => __( 'Front fields', 'wpf' ),
        'target' => 'wpf_front_fields_options',        
      );      
    }
    return $tabs;
  }

  /**
   * hook handler: plugin_action_links_woo-product-front-fields/woo-product-front-fields.php
   * Add relevant links to plugins page.
   *   
   * @param array $links   
   */
  public function plugin_action_links( $links ) {
    $plugin_links = array();    
    if ( function_exists( 'WC' ) ) {
      $setting_url = add_query_arg(
        array(
          'page' => 'wc-settings',      
          'tab' => 'products',
          'section' => 'wpf',
        ),
        admin_url('admin.php')
      );
      $plugin_links[] = '<a href="' . esc_url( $setting_url ) . '">' . esc_html__( 'Settings', 'wpf' ) . '</a>';
    }    

    return array_merge( $plugin_links, $links );
  }

  /**
   * hook handler: woocommerce_product_data_panels   
   */
  public function product_data_panels() {                     
    ?>
    <div id='wpf_front_fields_options' class='panel woocommerce_options_panel'>
      <div class="wpf-price-calculator">
        <div class="wpf-base-price">
          <strong><?php echo __( 'Base price', 'wpf' ); ?> (<?php echo get_woocommerce_currency_symbol(); ?>): <?php echo wc_help_tip( __( 'The value of the <em>Regular price</em> or <em>Sale price</em>' , 'wpf' ) ); ?></strong>
          <span class="wpf-base-price-value"></span>
        </div>
        <div class="wpf-updated-price">
          <strong><?php echo __( 'Updated price', 'wpf' ); ?> (<?php echo get_woocommerce_currency_symbol(); ?>): <?php echo wc_help_tip( __( 'The <em>Base price</em> <strong>plus</strong> the values of the all active fields that have default or overridden values to setup', 'wpf') ); ?></strong>
          <span class="wpf-updated-price-value"></span>
        </div>
      </div>
      <div>
        <select id="wpf-fields-picker">
          <option value="">-- Select a field --</option>
          <?php foreach ( $this->fields as $field ) : ?>
          <option value="<?php echo $field['name']; ?>"><strong><?php echo $field['title'] . ' ( '. $field['name'].' )'; ?></strong></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="button" id="wpf-add-field">Add field</button>
      </div>
      <?php $weight = 1;
      ?>
      <div class="wpf-data">                  
        <?php foreach ( $this->fields as $field ) : ?>
          <?php             
            $is_active = $field['active_by_default'];
            $is_overridden_price = false;
            $is_overridden_value = false;                        
            $options = array();             
            global $post;                      
            if ( isset( $post->ID ) && 'auto-draft' !== $post->post_status ) {              
              $field_product = WPF_Field_Product_DS::instance()
                                 ->get( $field['id'], $post->ID );
              $is_overridden_value = $field_product['is_overridden_value'];
              
              $weight = isset( $field_product['weight'] ) ? $field_product['weight'] : $field['weight'];
              $is_active = wpf_is_field_visible( $post->ID, $field['id'], $is_active );
              $option_id = 0;              
              $options = WPF_Field_Product_Option_DS::instance()->get_options( $post->ID, $field['id'], $field['widget'] );                            
              // find wheither at least one price option is overridden              
              $overridden_prices_list = array_column( $options, 'is_overridden_price' );
              if ( !empty( $overridden_prices_list ) ) {
                $is_overridden_price = max( $overridden_prices_list );
              }                            
            } else {
              $weight++;
            }            
          ?>
        <div class="wpf-field-item<?php echo $is_active ? ' checked' : '' ?>">
          <div class="wpf-control">
            <label>
            <span class="wpf-is-active">
              <input type="checkbox" class="wpf-active-checkbox" name="wpf_is_active_<?php echo $field['name']; ?>" data-name="<?php echo $field['name']; ?>" <?php checked( $is_active, 1 ); ?> />
              <input type="hidden" name="wpf_is_active_previous_<?php echo $field['name']; ?>" value="<?php echo $is_active ? 'on' : 'off'; ?>" />
            </span>
            <span class="wpf-title"><?php echo $field['title']; ?></span>
            </label>
            <div class="wpf-field-override">              
              <a class="wpf-override-link<?php if ( $is_active && ( $is_overridden_price || $is_overridden_value ) ) : ?> expanded<?php endif; ?><?php if ( ! $is_active ) : ?> disabled<?php endif; ?>" href="#">Settings</a>
            </div>
          </div>
          <div class="wpf-override-content" <?php if ( ! $is_active || ( ! $is_overridden_price && ! $is_overridden_value ) ) : ?> style="display:none;" <?php endif; ?>>
            <?php 
              global $wpf_widgets;
              // turn Select widget into Radios widget to simplify price managment
              $widget_type = $wpf_widgets[ $field['widget'] ]['type'] === 'single_option' ? 'WPF_Radio_Widget' : $field['widget'];
              $view_class = new $widget_type( $field, $post->ID, '', true );
              $view_class->render();
            ?>              
            <input type="hidden" name="wpf_field_id_<?php echo $field['name']; ?>" value="<?php echo $field['id']; ?>" />
            <input type="hidden" name="wpf_weight_<?php echo $field['name']; ?>" value="<?php echo $weight; ?>" />
          </div>
        </div>
        <?php endforeach; ?>        
      </div>      
    </div>
    <?php
  }

  /**
   * hook handler: woocommerce_process_product_meta
   * 
   * @param  integer $product_id   
   */
  public function process_product_meta( $product_id ) {            
    $fields = wpf_get_fields();
    $updated_fields = array();
    global $wpf_widgets;
    foreach ( $fields as $field ) {
      $field_id = $field['id'];      
      $name = $field['name'];
      $is_default_active = $field['active_by_default'];
      $is_active = wpf_get_post( "wpf_is_active_{$name}" );      
      $is_active_previous = wpf_get_post( "wpf_is_active_previous_{$name}" );
      if ( 'off' === $is_active && 'off' === $is_active_previous && ! $is_default_active ) {
        continue;
      }      
            
      // check if a field is a checkboxes group or a single value      
      $value = wpf_get_post( $name, '' );
      if ( !empty( $value ) ) {        
        if ( is_array( $_POST[$name] ) ) {
          $value = array_map( 'sanitize_text_field', $_POST[$name] );
          $value = implode( ',', $value );
        } else {
          $value = sanitize_text_field( $_POST[$name] );
        }        
      }      
      $updated_fields[$field_id] = array( 
        'is_active' => $is_active,
        'is_overridden_value' => wpf_get_post( "wpf_override_value_{$name}" ),
        'weight' => wpf_get_post( "wpf_weight_{$name}" ),
        'value' => $value,         
        'options' => array()
      );
      switch ( $wpf_widgets[ $field['widget'] ]['type'] ) {
        case 'text':
        case 'checkbox':
          $option_id = 0;          
          $is_overridden_price = wpf_get_post( "wpf_override_price_{$name}" );
          $price = wpf_get_post( "wpf_price_{$name}", $field['charge'] );
          $updated_fields[$field_id]['options'][$option_id] = array(
            'is_overridden_price' => $is_overridden_price,
            'price' => $price
          );
          break;
        case 'single_option':
        case 'multi_option':        
          $options = wpf_get_field_options( $field_id, false );          
          foreach ( $options as $option ) {
            $option_id = $option['id'];            
            $is_overridden_price = wpf_get_post( "wpf_override_price_{$option_id}" );
            $price = wpf_get_post( "wpf_price_{$name}_{$option_id}", $option['price'] );     
            $updated_fields[$field_id]['options'][$option_id] = array(
              'is_overridden_price' => $is_overridden_price,
              'price' => $price
            );            
          }          
          break;        
      }          
    }           
    wpf_update_fields( $product_id, $updated_fields );      
  }

  /**
   * hook-handler: woocommerce_get_sections_products
   * Add Front fields section to the Products setting tab
   * 
   * @param array $sections
   */
  public function add_front_fields_section( $sections ) {
    $sections['wpf'] = __( 'Front fields', 'wpf' );
    return $sections;
  }  

  /**
   * hook-handler: woocommerce_product_duplicate
   * Duplicate wpf fields for the product
   *
   * @param array $duplicate - new product
   * @param array $product - main product
   */
  public function product_duplicate( $duplicate, $product ) {        
    $product_fields = WPF_Field_Product_DS::instance()
                        ->get_by_product_id( $product->get_id() );
    foreach ( $product_fields as $fp_item ) {
      $duplicated_fp = new WPF_Field_Product();
      $duplicated_fp->set_field_id( $fp_item['field_id'] );
      $duplicated_fp->set_product_id( $duplicate->get_id() );
      $duplicated_fp->set_variation_id( $fp_item['variation_id'] );
      $duplicated_fp->set_weight( $fp_item['weight'] );
      $duplicated_fp->set_is_active( $fp_item['is_active'] );
      $duplicated_fp->set_is_overridden_value( $fp_item['is_overridden_value'] );
      $duplicated_fp->set_value( $fp_item['value'] );
      $duplicated_fp->save();
      $duplicated_fp_id = $duplicated_fp->get_id();
      $pf_options = WPF_Field_Product_Option_DS::instance()
                      ->get_by_field_product_id( $fp_item['id'] );
      foreach ( $pf_options as $fpo_item ) {
        $duplicated_fpo = new WPF_Field_Product_Option();
        $duplicated_fpo->set_field_product_id( $duplicated_fp_id );
        $duplicated_fpo->set_option_id( $fpo_item['option_id'] );
        $duplicated_fpo->set_is_active( $fpo_item['is_active'] );
        $duplicated_fpo->set_is_overridden_price( $fpo_item['is_overridden_price'] );
        $duplicated_fpo->set_price( $fpo_item['price'] );
        $duplicated_fpo->set_is_overridden_title( $fpo_item['is_overridden_title'] );
        $duplicated_fpo->set_title( $fpo_item['title'] );        
        $duplicated_fpo->save();
      }
    }
  }

  /**
   * hook-handler: add_front_fields_settings_form
   * Add Front fields settings form to the Front fields section
   * 
   * @param array $settings
   * @param integer $current_section
   */
  public function add_front_fields_settings_form( $settings, $current_section ) {
    if ( $current_section == 'wpf' ) {
      $settings = array();
      $settings[] = array( 'name' => __( 'Front fields', 'wpf' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure Front fields', 'wpf' ), 'id' => 'wpf' );
      $settings[] = array(
        'name'     => __( 'Image style', 'wpf' ),
        'desc_tip' => __( 'The image style that is used for the option images', 'wpf' ),
        'id'       => 'wpf_image_style',
        'default'  => 'thumbnail',
        'type'     => 'select',
        'options'  => array( '' => '---- None ----' ) + wpf_get_image_sizes()        
      );
      $settings[] = array(
        'name'     => __( 'Product teaser button text', 'wpf' ),
        'desc_tip' => __( 'Product teaser are shown on Shop page and on Related products section', 'wpf' ),
        'id'       => 'wpf_product_teaser_button_text',
        'type'     => 'text', 
        'default'  => __( 'Select options', 'wpf' )
      );
      $settings[] = array(
        'name'     => __( 'Radio field empty label', 'wpf' ),
        'desc_tip' => __( 'It will be shown on the Product page if product has active radio field', 'wpf' ),
        'id'       => 'wpf_radio_field_empty_label',
        'type'     => 'text', 
        'default'  => __( 'None', 'wpf' )
      );
      $settings[] = array(
        'name'     => __( 'Select field empty label', 'wpf' ),
        'desc_tip' => __( 'It will be shown on the Product page if product has active select field', 'wpf' ),
        'id'       => 'wpf_select_field_empty_label',
        'type'     => 'text', 
        'default'  => __( '--Select option--', 'wpf' )
      );
      $settings[] = array(
        'name'     => __( 'Checkbox is checked label', 'wpf' ),
        'desc_tip' => __( 'For single checkbox field only. It will be shown on Cart popup, Cart page, Checkout page, Order\'s review popup and page', 'wpf' ),
        'id'       => 'wpf_checkbox_is_checked_label',
        'type'     => 'text', 
        'default'  => __( 'yes', 'wpf' )
      );
      // show fields before or after 'Add to cart' button
      $settings[] = array(
        'name'     => __( 'Fields location', 'wpf' ),
        'desc_tip' => __( 'The fields location on the Product page', 'wpf' ),
        'id'       => 'wpf_fields_location',
        'default'  => 'before_addtocart',
        'type'     => 'select',
        'options'  => array(
          'before_addtocart'  => __( 'Before Add to cart button', 'wpf' ),
          'after_addtocart'   => __( 'After Add to cart button', 'wpf' ),
        )
      );
      $settings[] = array(
        'name'     => __( 'Base price info', 'wpf' ),
        'desc'     => __( 'Show info about initial product price without any extra charges.', 'wpf' ),
        'desc_tip' => __( 'It will be shown on Cart popup, Cart page, Checkout page, Order\'s review popup and page.', 'wpf' ),
        'id'       => 'wpf_base_price_info',
        'default'  => 'yes',
        
        'type'     => 'checkbox',        
      );
      $settings[] = array(
        'name'     => __( 'Base price label', 'wpf' ),
        'desc'     => __( 'Is used together with Base price info setting.', 'wpf' ),
        'desc_tip' => __( 'By default is Price.', 'wpf' ),
        'id'       => 'wpf_base_price_label',
        'default'  => 'Price',        
        'type'     => 'text',        
      );
      $settings[] = array(
        'name'     => __( 'Show field profile sidebar links', 'wpf' ),
        'desc'     => __( 'Field Profile links similar to `Add new` link will be shown on the Products menu.', 'wpf' ),        
        'id'       => 'wpf_field_profile_show_links',        
        'type'     => 'checkbox', 
        'default'  => 'yes',       
      );

      // None label for Radio buttons      
      $settings[] = array( 'type' => 'sectionend', 'id' => 'wpf' );        
    } 
    return $settings;      
  }
}

