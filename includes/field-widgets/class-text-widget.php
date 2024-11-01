<?php

defined( 'ABSPATH' ) || exit;

class WPF_Text_Widget extends WPF_Base_Widget {  

  public function __construct( $field, $product_id, $default_value = '', $is_admin_form = false ) {
    parent::__construct( $field, $product_id, $default_value, $is_admin_form );
    if ( $is_admin_form ) {
      $data = WPF_Field_Product_DS::instance()
              ->get_single_data( $field['id'], $product_id );      
      $this->value = $field['default_value'];
      $this->price = $this->charge;
      $this->default_value = $field['default_value'];
      $this->default_price = $this->charge;
      $this->is_overridden_value = false;
      $this->is_overridden_price = false;          
      
      if ( ! empty( $data ) ) {
        $this->is_overridden_value = $data['is_overridden_value'];
        $this->is_overridden_price = $data['is_overridden_price'];
        $this->value               = $data['value']; 
        $this->price               = $data['price'];        
      }              
    }
  }

  public function element_render() {
    ?>
    <?php if ( $this->is_admin_form ) : ?>        
      <table><thead><tr><th align="left" colspan="2"><?php echo __( 'Override charge', 'wpf' ); ?></th><th align="left" colspan="2"><?php echo __( 'Override value', 'wpf' ); ?></th></thead><tr>
      <td>
      <input type="checkbox" name="wpf_override_price_<?php echo $this->name; ?>" class="wpf-override-price" <?php checked( $this->is_overridden_price ); ?> target-name="<?php echo $this->name; ?>" />
      </td>
      <td class="wpf-price-section" data-id="<?php echo $this->name; ?>">
        <div class="override" style="display:<?php echo $this->is_overridden_price ? 'block' : 'none' ?>;">
        <?php echo $this->price_field_render( 'wpf_price_' . $this->name, $this->price, $this->default_price, '' ); ?>
        </div>
        <div class="default" style="display:<?php echo $this->is_overridden_price ? 'none' : 'block' ?>;">
          <?php echo wc_price( $this->default_price ); ?>
        </div>
      </td>  
      <td>    
      <input type="checkbox" name="wpf_override_value_<?php echo $this->name; ?>" data-name="<?php echo $this->name; ?>" data-type="<?php echo $this->type; ?>" data-charge-type="<?php echo $this->charge_type; ?>" class="wpf-override-value" <?php checked( $this->is_overridden_value ); ?> target-name="<?php echo 'ov_' . $this->name; ?>"  data-default-value='<?php echo $this->default_value; ?>' />
      </td>
      <td class="wpf-value-section" data-id="<?php echo 'ov_' . $this->name; ?>">
      <div class="override" style="display:<?php echo $this->is_overridden_value ? 'block' : 'none' ?>;">
        <input type='text' class='wpf-widget-input wpf-field-value' name='<?php echo $this->name; ?>' value='<?php echo $this->value; ?>' />
      </div>
      <div class="default" style="display:<?php echo $this->is_overridden_value ? 'none' : 'block' ?>;">
        <?php if ( empty( $this->default_value ) ) : ?>
          <span title="No text by default" class="dashicons dashicons-minus"></span>
        <?php else : ?>
          <span title="Default value" class="wpf-default-text"><?php echo $this->default_value; ?></span>
        <?php endif; ?>
      </div>
      </td>      
      </tr></table>
    <?php else : ?>
      <input type='text' name='<?php echo $this->name; ?>' data-id=<?php echo $this->id; ?> value='<?php echo $this->value; ?>' <?php echo wpf_attributes( $this->attributes ); ?> />  
      <?php if ( ! empty( $this->unit ) ) : ?> <span class="wpf-unit"><?php echo $this->unit; ?></span><?php endif; ?>
      <?php if ( $this->chargeable && $this->charge > 0 ) : ?>
        <strong class="wpf-extra-price-info"> 
          <?php 
            echo wpf_price_wrapper( 
              wpf_global_get_charge( $this->product_id, $this->name, 0 ), 
              $this->charge_type, true 
            ); 
          ?>
        </strong>
      <?php endif; ?>
    <?php endif; ?>        
    <?php
  }
}