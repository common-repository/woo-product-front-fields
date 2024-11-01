<?php

defined( 'ABSPATH' ) || exit;

class WPF_Checkboxes_Widget extends WPF_Base_Widget {  
  private $options;
  private $default_options;
  private $default_values;
  private $default_values_string;
  private $image_style;
  private $image_data;
  private $values;    
  public function __construct( $field, $product_id, $default_values = array(), $is_admin_form = false ) {
    parent::__construct( $field, $product_id, array(), $is_admin_form );        
    if ( $is_admin_form ) {
      $product_field_options = wpf_get_product_field_options( 
                                 $product_id, 
                                 $field['id'], 
                                 $field['widget'] 
                               );
      $this->default_options = WPF_Field_Option_DS::instance()
                      ->get_options_by_field_id( $field['id'] );
      $this->default_values_string = $field['default_value'];                      
      $this->default_values = explode( ',', $this->default_values_string );
      if ( empty( $product_field_options) ) {
        $this->options = $this->default_options;
        wpf_options_attach_default_props( 
          $this->options, 
          $this->default_values_string 
        );        
      } else {
        $this->options = $product_field_options;
      }

      $field_product = WPF_Field_Product_DS::instance()
                         ->get( $field['id'], $product_id );
      $this->is_overridden_value = $field_product['is_overridden_value'];
      $this->values = $this->is_overridden_value ? 
               explode( ',', $field_product['value'] ) : 
               explode( ',', $this->default_values_string );            
    } else {
      $this->image_style = get_option( 'wpf_image_style', 'thumbnail' );
      $this->options = WPF_Field_Option_DS::instance()
                        ->get_options_by_field_id( $field['id'] );      
      // check if checkboxes values passed in the query string
      if ( isset( $_GET[$this->name] ) ) {
        $this->values = explode( ',', $default_values );
      } else {  
        $this->values = empty( $default_values ) ? array() : $default_values;        
      }
    }
  }

  public function element_render() {    
    ?>
    <?php if ( $this->is_admin_form ) : ?>
    <div class='wpf-checkboxes-group'>
      <table>
      <thead><tr><th align="left"><?php echo __( 'Override charge', 'wpf' ); ?></th><th align="left" colspan="2"><?php echo __( 'Override value', 'wpf' ); ?></th></thead>
      <tr>
      <td>
      <div class="wpf-price-checkboxes">          
        <?php foreach ( $this->options as $option ) : ?>
        <div class="wpf-radio-item wpf-checkbox-item" data-option="<?php echo $option['id']; ?>">
          <label>
          <input type="checkbox" class="wpf-override-price" target-name="<?php echo $this->name . '_' . $option['id']; ?>"
           name="<?php echo 'wpf_override_price_' . $option['id']; ?>" <?php checked( $option['is_overridden_price'] ); ?>>
          <span class="wpf-option-title"><?php echo $option['title']; ?></span>
          </label>
          <span class="wpf-option-price wpf-price-section" data-id="<?php echo $this->name . '_' . $option['id']; ?>">
            <div class="override" style="display:<?php echo $option['is_overridden_price'] ? 'block' : 'none' ?>;">
              <?php echo $this->price_field_render( 'wpf_price_' . $this->name . '_' . $option['id'], $option['overridden_price'], $this->default_options[$option['id']]['price'], '' ); ?>
            </div>
            <div class="default" style="display:<?php echo $option['is_overridden_price'] ? 'none' : 'block' ?>;">
                <?php echo wc_price( $this->default_options[$option['id']]['price'] ); ?>
            </div>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      </td>
      <td>
      <input type="checkbox" class="wpf-override-value"
         name="<?php echo 'wpf_override_value_' . $this->name; ?>"  data-name="<?php echo $this->name; ?>" data-type="<?php echo $this->type; ?>" data-charge-type="<?php echo $this->charge_type; ?>" <?php checked( $this->is_overridden_value ); ?> data-default-value='<?php echo $this->default_values_string; ?>' target-name="<?php echo 'ov_' . $this->name; ?>">          
      </td>
      <td class="wpf-value-section" data-id="<?php echo 'ov_' . $this->name; ?>">    
      <div class="override" style="display:<?php echo $this->is_overridden_value ? 'block' : 'none' ?>;">  
        <?php foreach ( $this->options as $option ) : ?>      
        <div class="wpf-radio-item">
          <input type="checkbox" class="wpf-widget-input wpf-field-value" id="<?php echo 'copt_' . $option['id']; ?>"
           name="<?php echo $this->name; ?>[]" value="<?php echo $option['id']; ?>" <?php checked( in_array( $option['id'], $this->values ) ); ?>>
          <label class="wpf-option-data" for="<?php echo 'copt_' . $option['id']; ?>">
            <span class="wpf-option-title"><?php echo $option['title']; ?></span>     
          </label>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="default" style="display:<?php echo $this->is_overridden_value ? 'none' : 'block' ?>;">        
        <ul>
          <?php foreach ( $this->options as $option ) : ?>
          <li><?php if ( in_array( $option['id'], $this->default_values ) ) : ?><span class="dashicons dashicons-yes"></span><?php else : ?><span class="dashicons dashicons-minus"></span><?php endif; ?><?php echo $option['title']; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      </td>
      </tr>
      </table>          
    </div>
    <?php else : ?>
    <div class='wpf-checkboxes-group <?php if ( 'image' === $this->options_extra ) : ?>wpf-image<?php endif; ?>' id="<?php echo $this->name; ?>" data-id='<?php echo $this->id; ?>'>
      <div>      
      <?php foreach ( $this->options as $option ) : ?>  
        <?php             
          $title = $option['title'];
          if ( $this->chargeable && $option['price'] > 0 ) {
            $title .= ' ' . wpf_price_wrapper( 
              wpf_global_get_charge( 
                $this->product_id, 
                $this->name, 
                $option['id'] 
              ) 
            );
          }
          if ( 'image' === $this->options_extra ) {
            $this->image_data['image_style'] = $this->image_style;
            $this->image_data['attributes'] = array( 'title' => $title );
            $this->image_data = apply_filters( 'wpf_field_image_option_alter', $this->name, $this->product_id, $this->image_data );
          }
        ?>

      <div class="wpf-radio-item">
        <input type="checkbox" id="<?php echo 'copt_' . $option['id']; ?>"
         name="<?php echo $this->name; ?>[]" value="<?php echo $option['id']; ?>" <?php checked( in_array( $option['id'], $this->values ) ); ?> <?php echo wpf_attributes( $this->attributes ); ?>>
        <label class="wpf-option-data" for="<?php echo 'copt_' . $option['id']; ?>">
          <?php $is_image = 'image' === $this->options_extra && isset( $option['data'] ); ?>
          <?php if ( $is_image ) : ?>
            <?php echo wp_get_attachment_image( $option['data'], $this->image_data['image_style'], false, $this->image_data['attributes'] ); ?><?php endif; ?>
          <?php if ( ! $is_image ) : ?><span class="wpf-option-title"><?php echo $title; ?></span><?php endif; ?>         
        </label>
      </div>
      <?php endforeach; ?>    
      </div>
    </div>
    <?php endif; ?>    
    <?php
  }
}