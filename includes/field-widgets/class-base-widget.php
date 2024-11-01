<?php

defined( 'ABSPATH' ) || exit;

abstract class WPF_Base_Widget {
  protected $label;
  protected $name;
  protected $id;  
  protected $widget;
  protected $required;
  protected $charge;
  protected $value;
  protected $price;
  protected $chargeable;
  protected $charge_type;
  protected $default_value;
  protected $default_price;
  protected $unit;  
  protected $is_admin_form;
  protected $product_id;
  protected $field_id;  
  protected $options_extra;     

  protected $attributes;
  
  protected $type;

  protected $is_overridden_value;
  protected $dependency_added;
  protected $dependent_is_hidden;
  protected $dependency_target_field;
  protected $dependency_target_value;

  public function __construct( $field, $product_id, $default_value = '', $is_admin_form = false ) {
    $this->label = $field['title'];    
    $this->id = $field['id'];
    $this->widget = $field['widget'];
    $this->required = $field['required'];
    $this->chargeable = $field['chargeable'];
    $this->charge = $field['charge'];
    $this->unit = $field['unit'];
    $this->value = empty( $default_value ) ? '' : $default_value;
    $this->product_id = $product_id;
    $this->field_id = $field['id'];
    $this->name = $field['name'];      
    $this->charge_type = $field['charge_type']; 
    $this->options_extra = $field['options_extra'];     

    global $wpf_widgets;
    $this->type = $wpf_widgets[ $field['widget'] ]['type'];    
    if ( isset( $wpf_widgets[$this->widget]['attributes'] ) ) {
      $this->attributes = $wpf_widgets[$this->widget]['attributes'];
    }    
    
    $classes = array(
      'wpf-widget-input'
    );
    if ( 'multi_option' === $this->type ) {
      $classes[] = 'wpf-multifield';
    }
    if ( 'image' === $this->options_extra ) {
      $classes[] = 'wpf-hidden';
    }
    $classes = implode( ' ', $classes );
    if ( isset( $this->attributes['class'] ) && !empty( $this->attributes['class'] ) ) {
      $this->attributes['class'] = $classes . ' ' . $this->attributes['class'];
    } else {
      $this->attributes['class'] = $classes;
    }    
    
    $this->attributes = apply_filters( 'wpf_field_attributes_alter', $field, $product_id, $this->attributes );
                  
    $this->is_admin_form = $is_admin_form; 
    /* @TODO - Field dependency feature    
    global $wpf_products;
    $field_info = &$wpf_products[$this->product_id][$this->name];    
    // check if dependency is set and dependency field is attached to the product
    $this->dependency_added = !empty( $field_info['visibility'] ) && 
      isset( $wpf_products[$this->product_id][ $field_info['visibility']['name'] ] );    
    $this->dependent_is_hidden = false;
    $this->dependency_target_field = '';
    $this->dependency_target_value = '';
    $this->dependency_target_operation = 'equal';
    // check if dependency is set for the field
    if ( $this->dependency_added ) { 
      $target_field = &$wpf_products[$this->product_id][ $field_info['visibility']['name'] ];
      $this->dependency_target_field = $field_info['visibility']['name'];     
      $this->dependency_target_value = $field_info['visibility']['value'];
      $this->dependency_target_operation = $field_info['visibility']['operation'];
      // check if target value is the same as dependent field expects
      if ( ( ! is_array( $target_field['value'] ) && $field_info['visibility']['value'] !== $target_field['value'] ) || ( ! in_array( $field_info['visibility']['value'], $target_field['value'] ) ) ) {
        $this->dependent_is_hidden = true;
      }
    }
    data-type="<?php echo $this->widget; ?>" class="wpf-field <?php echo $this->dependent_is_hidden ? 'wpf-hidden' : ''; ?>" 
      <?php echo !empty( $this->dependency_target_field ) ? 'data-target="'. $this->dependency_target_field .'"' : ''; ?> 
      <?php echo !empty( $this->dependency_target_value ) ? 'data-value="'. $this->dependency_target_value .'"' : ''; ?>
      <?php echo !empty( $this->dependency_target_operation ) ? 'data-op="'. $this->dependency_target_operation .'"' : ''; ?>
    */
  }

  abstract protected function element_render();

  public function render() {
    ?>
    <?php if ( ! $this->is_admin_form ) : ?>
    <div id="<?php echo $this->name ; ?>" class="wpf-field <?php echo strtolower( $this->widget ); ?>">
      <label>
        <strong class="title"><?php echo $this->label; ?><?php if ( $this->required ) : ?><span class="asterisk">*</span><?php endif; ?>
        </strong>
    <?php endif; ?>
        <?php $this->element_render(); ?>
    <?php if ( ! $this->is_admin_form ) : ?>
      </label>
    </div>
    <?php endif; ?>
    <?php
  }  

  protected function price_field_render( $name, $value, $default_price, $readonly = '' ) {
    ?>
    <input type="text" name="<?php echo $name; ?>" class="wpf-price-field" value="<?php echo $value; ?>" data-default-price="<?php echo $default_price; ?>" <?php echo $readonly; ?> pattern="<?php echo  wpf_get_price_pattern(); ?>" />
    <?php
  }
}