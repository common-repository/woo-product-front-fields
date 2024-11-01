<?php

defined( 'ABSPATH' ) || exit;

class WPF_Select_Widget extends WPF_Base_Widget {  
  private $options;
  private $default_options;
  public function __construct( $field, $product_id, $default_value = '', $is_admin_form = false ) {
    parent::__construct( $field, $product_id, $default_value, $is_admin_form );          
    if ( $is_admin_form ) {
      $product_field_options = wpf_get_product_field_options( $product_id,
                                                              $field['id'],
                                                             $field['widget'] );
      $this->default_options = WPF_Field_Option_DS::instance()
                      ->get_options_by_field_id( $field['id'] );
      $this->default_value = $field['default_value'];
      if ( empty( $product_field_options) ) {
        $this->options = $this->default_options;
        wpf_options_attach_default_props(
          $this->options,
          $field['default_value']
        );
      } else {
        $this->options = $product_field_options;
      }
      $field_product = WPF_Field_Product_DS::instance()
                        ->get( $field['id'], $product_id );
      $this->is_overridden_value = $field_product['is_overridden_value'];
      $this->value = $this->is_overridden_value ? 
               $field_product['value'] : 
               $field['default_value'];
    } else {
      $this->options = WPF_Field_Option_DS::instance()
                         ->get_options_by_field_id( $field['id'] );
      $this->attributes['class'] = 'wpf-select-widget ' . $this->attributes['class'];
    }
  }

  public function element_render() {    
    ?>
    <?php if ( $this->is_admin_form ) : ?>
      <div class='wpf-radios-widget'>
        <table>
          <thead><tr><th align="left"><?php echo __( 'Override charge', 'wpf' ); ?></th><th align="left" colspan="2"><?php echo __( 'Override value', 'wpf' ); ?></th></thead>
          <tr>
            <td>
              <div class="wpf-price-checkboxes">          
                <?php foreach ( $this->options as $option ) : ?>
                <div class="wpf-radio-item" data-option="<?php echo $option['id']; ?>">
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
            <input type="checkbox" class="wpf-override-value" data-name="<?php echo $this->name; ?>" data-type="<?php echo $this->type; ?>" data-charge-type="<?php echo $this->charge_type; ?>" name="<?php echo 'wpf_override_value_' . $this->name; ?>" <?php checked( $this->is_overridden_value ); ?> data-default-value='<?php echo $this->default_value; ?>' target-name="<?php echo 'ov_' . $this->name; ?>" />  
          </td>
          <?php array_unshift( $this->options, array('id' => '', 'title' => __( 'None', 'wpf' ) ) ); ?>
          <td class="wpf-value-section" data-id="<?php echo 'ov_' . $this->name; ?>">
          <div class="override" style="display:<?php echo $this->is_overridden_value ? 'block' : 'none' ?>;">
            <div class="wpf-default-radios">              
              <?php foreach ( $this->options as $option ) : ?>
              <div class="wpf-radio-item">
                <input type="radio" class="wpf-field-value" 
                  id="<?php echo 'ropt_' . $option['id']; ?>"
                 name="<?php echo $this->name; ?>" value="<?php echo $option['id']; ?>" <?php checked( $this->value === $option['id'] ); ?>>
                <label class="wpf-option-data" for="<?php echo 'ropt_' . $option['id']; ?>"  <span class="wpf-option-title"><?php echo $option['title']; ?></span>
                </label>      
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="default" style="display:<?php echo $this->is_overridden_value ? 'none' : 'block' ?>;">
            <ul>                
              <?php foreach ( $this->options as $option ) : ?>
              <li><?php if ( $option['id'] === $this->default_value ) : ?><span class="dashicons dashicons-yes"></span><?php else : ?><span class="dashicons dashicons-minus"></span><?php endif; ?><?php echo $option['title']; ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          </td>
          </tr>
        </table>
      </div>
    <?php else : ?>
      <select  name='<?php echo $this->name; ?>' data-id='<?php echo $this->id; ?>' <?php echo wpf_attributes( $this->attributes ); ?>>
        <?php if ( ! $this->required ) : ?>
          <option value='' <?php selected( empty( $this->value ) ); ?>><?php echo __( get_option( 'wpf_select_field_empty_label', '-- Select option --' ), 'wpf' ); ?></option>
        <?php endif; ?>
        <?php foreach ( $this->options as $option ) : ?>
        <option value='<?php echo $option['id']; ?>' <?php selected( $this->value === $option['id'] ); ?>><?php echo $option['title']; ?> <?php if ( $this->chargeable && $option['price'] > 0 ) : ?>
          <?php echo wpf_price_wrapper( 
            wpf_global_get_charge( $this->product_id, $this->name, $option['id'] ) );
          ?>
          <?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>    
    <?php
  }
}