<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Field CRUD functionality of the plugin.
 *
 * @since   1.0.0
 */
class WPF_Field_CRUD {    
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

  const LIST_PAGE         = 'wpf_fields_list';
  const ADD_PAGE          = 'wpf_field_add';
  const EDIT_PAGE         = 'wpf_field_edit';
  const FORM_ID           = 'wpf_field_form';
  const FORM_SHORTCODE    = 'wpf_field_form';  
  const FIELDS_OPTION_ID  = 'wpf_fields';
  const PARENT_MENU       = 'edit.php?post_type=product';
  
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
   * Get all fields 
   * 
   * @return array
   */
  private function get_items() {
    return wpf_get_fields();
  }   
   
  /**
   * hook handler: admin_enqueue_scripts
   * Register the stylesheets
   */
  public function enqueue_styles( $hook ) {             
    if ( isset( $_GET['page'] ) && ( 
         self::ADD_PAGE == $_GET['page'] || 
         self::EDIT_PAGE == $_GET['page'] ) ) {         
      wp_enqueue_style( get_class( $this ), plugin_dir_url( __FILE__ ) . 'css/wpf-field-crud.css', array(), $this->version, 'all' );
      wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
    }
  }  

  /**
   * hook handler: admin_enqueue_scripts
   * Register the JavaScript
   */
  public function enqueue_scripts( $hook ) {    
    if ( isset( $_GET['page'] ) && ( 
         self::ADD_PAGE == $_GET['page'] || 
         self::EDIT_PAGE == $_GET['page'] ) ) {             
      $script_id = get_class($this);          
      wp_enqueue_script( $script_id, plugin_dir_url( __FILE__ ) . 'js/wpf-field-form.js', array( 'jquery', 'jquery-tiptip', 'jquery-ui-sortable' ), $this->version, false );      
      global $wpf_widgets;            
      wp_localize_script(
        $script_id,
        'wpf_field_form',
        array(                        
          'ajax_url' => admin_url( 'admin-ajax.php' ),
          'wpf_widgets' => $wpf_widgets
        )
      );    
    }
  }  

  /**
   * hook handler: wp_ajax_get_charge_types
   * Ajax callback for getting the charge types by the widget
   */
  public function ajax_get_charge_types() {
    if ( isset( $_POST[ "widget" ] ) ) {
      $widget = sanitize_text_field( $_POST[ "widget" ] );
      $charge_types = wpf_get_charge_types( $widget );
      wp_send_json( array( 'status' => true, 'chargeTypes' => $charge_types ) );
      wp_die();
    }
    wp_send_json( array( 'status' => false ) );
    wp_die();
  }

  /**
   * hook handler: wp_ajax_get_field_info
   * Ajax callback for getting the charge types by the widget
   */
  public function ajax_get_field_info() {    
    if ( isset( $_POST[ "name" ] ) ) {
      $name = sanitize_text_field( $_POST[ "name" ] );
      $field = wpf_get_field_by_name( $name );
      $widget = $field['widget'];     

      global $wpf_product_types;      
      $options = array();
      if ( in_array( $wpf_product_types[$widget]['type'], 
                     array( 'single_option', 'multi_option' ) ) ) {
        $options = wpf_get_field_options( $field['id'], false );                
        $options = array_column( $options, 'title', 'id' );
      }      
      wp_send_json( array( 'status' => true, 'type' => $widget, 'options' => $options ) );
      wp_die();
    }
    wp_send_json( array( 'status' => false ) );
    wp_die();
  }

  /**
   * hook handler: wp_ajax_field_option_delete
   * Ajax callback for validating how many overridden options are present 
   * per current option that is going to be deleted
   */
  public function ajax_get_overridden_options_count() {        
    if ( isset( $_POST[ "field_id" ] , $_POST[ "option_id" ] ) ) {
      $field_id = sanitize_text_field( $_POST[ "field_id" ] );
      $option_id = sanitize_text_field( $_POST[ "option_id" ] );
      $count = WPF_Field_Product_Option_DS::instance()
                 ->get_overridden_options_count( $field_id, 
                                                 $option_id );
      wp_send_json( array( 'status' => true, 'count' => $count ) );
      wp_die();
    }
    wp_send_json( array( 'status' => false ) );
    wp_die();
  }  
  
  /**
   * hook handler: wp_ajax_product_form_recalculate
   * Ajax callback for recalculating `Updated price` label on the Product form
   */
  public function ajax_product_form_recalculate_charges() {
    if ( isset( $_POST[ "wpf_data" ] ) && isset( $_POST[ "wpf_field_values" ] ) ) {
      $field_values = array_map( 'sanitize_text_field', $_POST[ "wpf_field_values" ] );
      $charges = 0;
      $charge_types = apply_filters( 'wpf_charge_types', array() );
      foreach ( $_POST[ "wpf_data" ] as $name => $item ) {
        $name = sanitize_text_field( $name );
        $item = array_map( 'sanitize_text_field', $item );
        $charge = apply_filters( 'wpf_charge_alter', $item['charge'], $item['value'], $name, $field_values, false, 0 );
        $charge = wpf_calculate_callback_execute( $charge, $item['value'], $item['charge_type'] );
        $charges += $charge;
      }
      wp_send_json( array( 'status' => true, 'charges' => $charges ) );
      wp_die();
    }
    wp_send_json( array( 'status' => true, 'charges' => 0 ) );
    wp_die();
  }

  /**
   * hook handler: admin_menu
   * * Add submenu pages: Fields list, Add field form, Edit field form          
   */
  public function admin_menu_pages() {
    $list_page_name = __( 'WPF Fields', 'wpf' );
    add_submenu_page( self::PARENT_MENU, $list_page_name, $list_page_name, 'manage_wpf_fields', self::LIST_PAGE, array( $this, 'list_menu_page_handler' ) );
    $add_form_name = __( 'Add field', 'wpf' );
    add_submenu_page( null, $add_form_name, $add_form_name, 'manage_wpf_fields', self::ADD_PAGE, array( $this, 'add_form_menu_page_handler' ) );
    $edit_form_name =  __( 'Edit field', 'wpf' );
    add_submenu_page( null, $edit_form_name, $edit_form_name, 'manage_wpf_fields', self::EDIT_PAGE, array( $this, 'edit_form_menu_page_handler' ) );
  }

  /**
   * The list page handler of the add_submenu_page    
   */
  public function list_menu_page_handler() {
    $items = $this->get_items();    
    $widget_names = wpf_get_widget_names();        
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo __( 'WPF Fields', 'wpf' ); ?></h1>
      <a href="<?php echo esc_url( $this->get_add_url() ); ?>" class="page-title-action"> <?php echo __( 'Add New', 'wpf' ); ?></a>    
      <table class="wp-list-table wpf-fields widefat fixed striped">
        <thead>       
        <td scope="col" class="manage-column column-title"><?php echo __( 'Title', 'wpf' ); ?> </td>
        <td scope="col" class="manage-column column-name"><?php echo __( 'Name', 'wpf' ); ?> </td>
        <td scope="col" class="manage-column column-type"><?php echo __( 'Type', 'wpf' ); ?></td>        
        <td scope="col" class="manage-column column-required"><?php echo __( 'Required', 'wpf' ); ?></td>
        <td scope="col" class="manage-column column-active-by-default"><?php echo __( 'Active by default', 'wpf' ); ?></td>
        
        <td scope="col" class="manage-column column-actions"><?php echo __( 'Actions', 'wpf' ); ?></td>
        </thead>
        <tbody>  
        <?php if ( sizeof( $items ) ) : ?>
        <?php foreach ( $items as $field_item ) : ?>
          <tr data-id="<?php echo $field_item['id']; ?>">        
            <td><?php echo $field_item['title']; ?></td>
            <td><?php echo $field_item['name']; ?></td>
            <td><?php echo $widget_names[ $field_item['widget'] ]; ?></td>
            <td><?php echo $field_item['required'] ? 'yes' : 'no'; ?></td>
            <td><?php echo $field_item['active_by_default'] ? 'yes' : 'no'; ?></td>            
            <td><a href="<?php echo esc_url( $this->get_edit_url( 
                $field_item['id'] ) ); ?>">
                <?php echo __( 'Edit', 'wpf' ); ?>
                <a>
                <a href="<?php echo esc_url( $this->get_delete_url( 
                $field_item['id'] ) ); ?>"
                onclick="return confirm('<?php echo __( 'Are you sure you want to delete this field? This action cannot be undone.', 'wpf' ); ?>');">
                <?php echo __( 'Delete', 'wpf' ); ?>
                <a>
            </td>
          </tr>    
        <?php endforeach; ?>
        <?php else : ?>
          <tr class="no-items"><td colspan="4"><?php echo __( 'No items found. You can add a new front field by clicking on `Add new` button next to the page title', 'wpf' ); ?></td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php    
  }

  /**
   * The add form page handler of the add_submenu_page    
   */
  public function add_form_menu_page_handler() {
    echo do_shortcode( '[' . self::FORM_SHORTCODE . ']' );
  }

  /**
   * The edit form page handler of the add_submenu_page      
   */
  public function edit_form_menu_page_handler() {
    $field_id = isset( $_GET['field_id'] ) ? sanitize_text_field( $_GET['field_id'] ) : '';
    $field_id = esc_attr( $field_id );
    echo do_shortcode( "[" . self::FORM_SHORTCODE . " field_id='{$field_id}']" );
  }

  /**
   * hook handler: cmb2_admin_init
   * The cmb2 form    
   */
  public function form() {
    $field_id = isset( $_GET['field_id'] ) ? sanitize_text_field( $_GET['field_id'] ) : '';
    $cmb = new_cmb2_box( array(
      'id'           => self::FORM_ID,      
      'object_types' => array( 'post' ),   
      'hookup'       => false,
      'save_fields'  => false, 
      'classes' => array( !empty( $field_id ) ? 'wpf-edit-form' : 'wpf-add-form' ),      
    ) ); 
    if ( !empty( $field_id ) ) {
      $cmb->add_field( array(            
        'id'      => 'field_id',    
        'type'    => 'hidden',
        'default' => esc_attr( $field_id ),      
      ) ); 
    }
    $cmb->add_field( array(
      'name'    => 'Title',        
      'id'      => 'title',    
      'type'    => 'text',        
      'attributes' => array(
        'autocomplete' => 'off',
        'required' => 'required',
        'pattern' => '.{3,}',
        'title' => "Start with a letter",
        'data-form-type' => !empty( $field_id ) ? 'edit' : 'add',        
      ),
    ) );
    $cmb->add_field( array(
      'name'    => 'Name',        
      'id'      => 'name',    
      'type'    => 'text',      
      'after' => wc_help_tip( __( 'Must be unique.', 'wpf' ) ),
      'attributes' => array(         
        'autocomplete' => 'off',       
        'pattern' => '[a-z][a-z0-9_]{3,}',
        'title' => "Start with a letter",        
      ),
    ) );        
    $cmb->add_field( array(
      'name'    => 'Required',        
      'id'      => 'required',
      'type'    => 'checkbox',
      'after' => wc_help_tip( __( 'After clicking on Add to cart button, the customers will see validation message untill this field is empty or no option is selected', 'wpf' ) ),
      'default' => 0,      
    ) );
    $cmb->add_field( array(
      'name'    => 'Active by default',        
      'id'      => 'active_by_default',
      'type'    => 'checkbox',
      'after' => wc_help_tip( __( 'Indicates weither the field should be shown for all products by default (new or existing ones). You can switch the field visibility per product inside <em>Front fields</em> section on the Product form', 'wpf' ), true ),
      'default' => 0,      
    ) );
    $widget_locked = false;
    if ( !empty( $field_id ) ) {
      $field = new WPF_Field( $field_id );
      $widget = $field->get_widget();
      global $wpf_widgets;
      $widget_locked = isset( $wpf_widgets[$widget]['libs'] ) && 
                           !empty( $wpf_widgets[$widget]['libs'] );
    }    
    $cmb->add_field( array(
      'name'    => 'Type',        
      'id'      => 'widget',
      'type'    => 'select',
      'options' => wpf_get_widget_names(),
      'after' => wc_help_tip( __( 'There are two groups of widgets: 1) Single option (Text, Checkbox) 2) Multi options (Select, Radio, Checkboxes). Per <em>Single option</em> type you set up a price field, per <em>Multi options</em> type you setup an options table where you add a variable amount of the options.', 'wpf' ), true ),
      'default' => 'text',
      'attributes' => array(                
        'data-locked' => $widget_locked,
      ),
    ) );

    $cmb->add_field( array(
      'name'    => 'Options extra',        
      'id'      => 'options_extra',
      'type'    => 'select',
      'options' => array(
        '' => __( '---- None ----', 'wpf' ),
        'image' => __( 'Image', 'wpf' ),
        // @TODO 
        //'color' => __t( 'Color', 'wpf' ),
      ),
      //'after' => wc_help_tip( __( '', 'wpf' ), true ),
      'default' => '',
      'attributes' => array(                
        'data-locked' => $widget_locked,
      ),
    ) );    
    $cmb->add_field( array(
      'name'    => 'Chargeable',        
      'id'      => 'chargeable',
      'type'    => 'checkbox',
      'after' => wc_help_tip( __( 'Allows to setup a price to the field or its options to take part in the product price calculations', 'wpf' ) ),
      'default' => 0,      
    ) ); 
    $cmb->add_field( array(
      'name'    => 'Charge type',        
      'id'      => 'charge_type',
      'type'    => 'select',
      'options' => array(), // the options will be loaded through ajax
      'after' => wc_help_tip( __( 'The way by which the chare will interact with the field value', 'wpf' ), true ),
      'default' => 'text',
    ) );    

    $cmb->add_field( array(
      'name'    => 'Charge',
      'id'      => 'charge',
      'type'    => 'text',
      'default' => '0.00',
      'after' => wc_help_tip( __( 'Format ( 12.00, 9.50 ). This charge will be added to the product price after the customer fills up the field. Leave 0 or empty if no charge is needed for the field', 'wpf' ), true ),    
      'attributes' => array(        
        'autocomplete' => 'off',
        'required' => 'required',
        'pattern' => wpf_get_price_pattern(),
      ) )
    );    

    $cmb->add_field( array(
      'name'    => 'Unit',        
      'id'      => 'unit',
      'type'    => 'select',
      'options' => array( '' => '---- None ----' ) + wpf_get_units(),
      'default' => '',
    ) );

    if ( !empty( $field_id ) ) {
      $cmb->add_field( array(
        'name'    => 'Default',        
        'id'      => 'field_default_text',
        'type'    => 'text',   
        'attributes' => array(
          'autocomplete' => 'off',                       
        )
      ) );

      $cmb->add_field( array(
        'name'    => 'Default',
        'id'      => 'field_default_checkbox',
        'type'    => 'radio',
        'options' => array(
          0 => __( 'Unchecked', 'wpf' ),
          1 => __( 'Checked', 'wpf' ),
        ),        
        'default' => 0,          
      ) );

      $cmb->add_field( array(
        'name'    => 'Default',
        'id'      => 'field_default_option_single',
        'type'    => 'select',
        'after' => wc_help_tip( __( 'Set up the default value for the field. The extra charge will be added per product price. <em>Notice:</em> If you\'ve added / changed / removed some options then you\'ll see the proper list of options only after Save button click ', 'wpf' ), true ), 
        'default' => '', 
        'options' => array()        
      ) );

      $cmb->add_field( array(
        'name'    => 'Default',
        'id'      => 'field_default_option_multiple',
        'type'    => 'multicheck',
        'after' => wc_help_tip( __( 'Set up the default value for the field. The extra charge will be added per product price. <em>Notice:</em> If you\'ve added / changed / removed some options then you\'ll see the proper list of options only after Save button click ', 'wpf' ), true ), 
        'default' => '0',
        'options' => wpf_get_field_options( $field_id )
      ) );
    } 
    $options_group = $cmb->add_field( array(
      'id'          => 'field_options_group',
      'type'        => 'group',    
      'name'        => __( 'Options', 'wpf' ),
      'options'     => array(
        'group_title'   => __( '{#}', 'wpf' ),
        'add_button'    => __( 'Add new option', 'wpf' ),
        'remove_button' => __( 'X', 'wpf' ),
        'sortable'      => true,
      ),
    ) );    
    $cmb->add_group_field( $options_group, array(      
      'id'   => 'field_option_id',
      'type' => 'text',  
      'attributes' => array(
        'class' => 'field-option-id'        
      ),
    ) ); 
    $cmb->add_group_field( $options_group, array(
      'name'    => 'Image',      
      'id'      => 'field_option_image',
      'type'    => 'file',
      // Optional:
      'options' => array(
        'url' => false, // Hide the text input for the url
      ),
      'text'    => array(
        'add_upload_file_text' => 'Add Image' // Change upload button text. Default: "Add or Upload File"
      ),
      // query_args are passed to wp.media's library query.
      'query_args' => array(
        //'type' => 'application/pdf', // Make library only display PDFs.
        // Or only allow gif, jpg, or png images
         'type' => array(
        //  'image/gif',
          'image/jpeg',
          'image/png',
          ),
      ),
      'preview_size' => array( 50, 50 ), // Image size to use when previewing in the admin.
    ) );   
    $cmb->add_group_field( $options_group, array(
      'name' => 'Title',
      'id'   => 'field_option_title',
      'type' => 'text',  
      'attributes' => array(     
        'required' => 'required',   
        'data-hidden-pattern' => '.{1,}',
      )    
    ) );    
    $cmb->add_group_field( $options_group, array(
      'name'    => 'Charge',
      'id'      => 'field_option_price',
      'type'    => 'text',
      'default' => 0,    
      'attributes' => array(
        //'required' => 'required',
        'data-hidden-pattern' =>  wpf_get_price_pattern(),        
      )
    ) );    

    /* @TODO - Field dependency feature  
    $cmb->add_field( array(
      'name'    => 'Add dependency',        
      'id'      => 'add_dependency',
      'type'    => 'checkbox',
      'after' => wc_help_tip( __( 'Specifies if this field should be shown when another field has specific value', 'wpf' ) ),
      'default' => 0,        
    ) );
    
    $dependency_group = $cmb->add_field( array(
      'id'          => 'field_dependency_group',
      'type'        => 'group',    
      'name'        => __( 'Dependency field', 'wpf' ),      
    ) );

    $cmb->add_group_field( $dependency_group, array(
      'name'    => 'Default visibility',        
      'id'      => 'dependency_default_visibility',
      'type'    => 'radio',
      'options' => array(
        0 => 'Hide',
        1 => 'Show'
      ),
      //'after' => wc_help_tip( __( 'Specifies if this field should be shown when another field has specific value', 'wpf' ) ),
      'default' => 0,      
    ) ); 
    $titles = array_column( wpf_get_fields(), 'title', 'name' );
    $field_id = isset( $_GET['field_id'] ) ? sanitize_text_field( $_GET['field_id'] ) : '';
    // if edit form - exclude current field from the dependency list
    if ( !empty( $field_id ) ) {
      $field = new WPF_Field( $field_id );
      $name = $field->get_name();
      unset( $titles[$name] );
    }
    $cmb->add_group_field( $dependency_group, array(
      'name'    => 'Field',        
      'id'      => 'dependency_field',
      'type'    => 'select',
      'options' => array( '' => '-- Select field --' ) + $titles,
      //'after' => wc_help_tip( __( 'Specifies if this field should be shown when another field has specific value', 'wpf' ) ),
      'default' => '',           
    ) );     

    $cmb->add_group_field( $dependency_group, array(
      'name'    => 'Operation',        
      'id'      => 'dependency_operation',
      'type'    => 'select',
      'options' => array( 'equal' => 'Equal', 'not_equal' => 'Not equal', 'gt' => 'Greater than', 'lt' => 'Lower than' ),
      //'after' => wc_help_tip( __( 'Specifies if this field should be shown when another field has specific value', 'wpf' ) ),
      'default' => 'equal',
    ) );

    $cmb->add_group_field( $dependency_group, array(
      'name'    => 'Value',        
      'id'      => 'dependency_value_text',
      'type'    => 'text',            
      'default' => '', 
    ) );

    $cmb->add_group_field( $dependency_group, array(
      'name'    => 'Value',        
      'id'      => 'dependency_value_checkbox',
      'type'    => 'checkbox',            
      'default' => 1,
    ) );

    $cmb->add_group_field( $dependency_group, array(
      'name'    => 'Value',        
      'id'      => 'dependency_value_radio',
      'type'    => 'select',
      'options' => array(),      
      'default' => '',  
    ) ); 
    */   
  }

  /**
   * hook handler: cmb2_after_init
   * The cmb2 form submit      
   */
  public function form_submit() {
    // If no form submission, bail
    if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'] ) ) {
      return false;
    }  
    // Get CMB2 metabox object
    $cmb = cmb2_get_metabox( self::FORM_ID, 'custom' );
    if ( !is_object( $cmb ) ) {
      return false;
    }

    // Check security nonce
    if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( 'Security check failed.', 'wpf' ) ) );
    }      

    $sanitized_values = $cmb->get_sanitized_values( $_POST );  

    if ( empty( $sanitized_values['title'] )   ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The title is required.', 'wpf' ) ) );    
    }      
    if ( empty( $sanitized_values['name'] )   ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The name is required.', 'wpf' ) ) );    
    }      
    if ( empty( $sanitized_values['widget'] )   ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The type is required.', 'wpf' ) ) );    
    }
    if ( empty( $sanitized_values['charge_type'] )   ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The charge type is required.', 'wpf' ) ) );    
    } 
    $field_id = isset( $_GET['field_id'] ) ? sanitize_text_field( $_GET['field_id'] ) : '';
    $edit_form = !empty( $field_id );
    if ( !$edit_form && !wpf_is_name_unique( $sanitized_values['name'] ) ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The name is used by another field. Please rename this field.', 'wpf' ) ) );    
    }                         
    
    $title = $sanitized_values['title'];
    $name = $sanitized_values['name'];
    $widget = $sanitized_values['widget'];    
    $chargeable = isset( $sanitized_values['chargeable'] ) ?
                  $sanitized_values['chargeable'] : false;
    $charge_type = $sanitized_values['charge_type'];  
    $unit = isset( $sanitized_values['unit'] ) ? $sanitized_values['unit'] : '';    
    $price = empty( $sanitized_values['charge'] ) ? 0 : $sanitized_values['charge'];
    $required = isset( $sanitized_values['required'] ) ?
                  $sanitized_values['required'] : false;    
    $options_extra = isset( $sanitized_values['options_extra'] ) ?
                  $sanitized_values['options_extra'] : '';
    $active_by_default = isset( $sanitized_values['active_by_default'] ) ?
                          $sanitized_values['active_by_default'] : false;
    /* @TODO - Field dependency feature  
    $add_dependency = isset( $sanitized_values['add_dependency'] ) ?
                          $sanitized_values['add_dependency'] : false;
    */                   
    $default_value_text = isset( $sanitized_values['field_default_text'] ) ? 
                          $sanitized_values['field_default_text'] : '';
    $default_value_checkbox = isset( $sanitized_values['field_default_checkbox'] ) ? 
                              $sanitized_values['field_default_checkbox'] : 0;
    $default_option_single = isset( $sanitized_values['field_default_option_single'] ) ?
                              $sanitized_values['field_default_option_single'] : '';
    $default_option_multiple = isset( $sanitized_values['field_default_option_multiple']                            ) ? $sanitized_values['field_default_option_multiple'] : '';            
    $field = $edit_form ? 
             new WPF_Field( $field_id ) : 
             new WPF_Field();
    $field->set_name( $name );
    $field->set_title( $title );
    $field->set_widget( $widget );    
    $field->set_chargeable( $chargeable );
    $field->set_charge_type( $charge_type );
    $field->set_unit( $unit );
    $field->set_required( $required );
    $field->set_options_extra( $options_extra );        
    $field->set_active_by_default( $active_by_default );    

    global $wpf_widgets;
    $value = '';
    $type = $wpf_widgets[$widget]['type'];
    switch ( $type ) {
      case 'text':      
        $field->set_charge( $price );
        $value = $default_value_text;
        break;
      case 'checkbox':
        $field->set_charge( $price );
        $value = $default_value_checkbox;
        break;
      case 'single_option':      
        $value = !empty( $default_option_single ) ? $default_option_single : '';
        break;
      case 'multi_option':
        $value = !empty( $default_option_multiple ) ? implode( ',', $default_option_multiple ) : '';
        break;
    }

    $field->set_default_value( $value );
    $field->save();    

    // save options group data
    $field_id = $field->get_id();              
    $options_types = array(
      'single_option',
      'multi_option'      
    );
    if ( in_array( $type, $options_types ) && 
         isset( $sanitized_values['field_options_group'] ) && 
         sizeof( $sanitized_values['field_options_group'] ) ) {      
      $weight = 0;      
      $active_option_ids = array_column( 
                            $sanitized_values['field_options_group'], 
                            'field_option_id' 
                          );  
      // clear removed options 
      if ( !empty( $active_option_ids[0] ) ) {        
        WPF_Field_Option_DS::instance()
          ->remove_diff_options( $active_option_ids, $field_id );
      }
      $first_option_id = '';      
      
      foreach ( $sanitized_values['field_options_group'] as $group_item ) {
        $charge = empty( $group_item['field_option_price'] ) ? 0 : $group_item['field_option_price'];
        $data = empty( $group_item['field_option_image_id'] ) ? 0 : $group_item['field_option_image_id'];
        if ( empty( $group_item['field_option_id'] ) ) {          
          $field_option = new WPF_Field_Option();
          $field_option->set_field_id( $field_id );          
          $field_option->set_option_data( $data );                    
          $field_option->set_option_title( $group_item['field_option_title'] );          
          $field_option->set_option_price( $charge );
          $field_option->set_option_weight( $weight );
          $field_option->save();          
          if ( empty( $first_option_id ) ) {
            $first_option_id = $field_option->get_id();
          }
          do_action( 'wpf_field_option_create', $field_option, $sanitized_values );
        } else {          
          $field_option = new WPF_Field_Option( $group_item['field_option_id'] );
          $field_option->set_option_data( $data );                    
          $field_option->set_option_title( $group_item['field_option_title'] );          
          $field_option->set_option_price( $charge );
          $field_option->set_option_weight( $weight );
          $field_option->save();
          do_action( 'wpf_field_option_update', $field_option, $sanitized_values );
        }
        $weight++;
      }      
      // setup first option as default if `Is required` is set and 
      // there's no default option has been already picked
      if ( $required &&
           'single_option' === $type && 
           empty( $value ) && 
           !empty( $first_option_id ) ) {        
        $field->set_default_value( $first_option_id );
        $field->save();  
      }      
    } 

    if ( $edit_form ) {
      do_action( 'wpf_field_update', $field, $sanitized_values );        
    } else {
      do_action( 'wpf_field_create', $field, $sanitized_values );
    }

    // save depencency group data
    /* @TODO - Field dependency feature  
    $depencency_group = &$sanitized_values['field_dependency_group'][0];
    if ( $add_dependency && isset( $depencency_group ) && 
         sizeof( $depencency_group ) ) {       
      $depencency_name = $depencency_group['dependency_field'];
      $depencency_field = wpf_get_field_by_name( $depencency_name );      
      $dependency_widget = $depencency_field['widget'];
      $dependency_type = $wpf_widgets[$dependency_widget]['type'];
      $data = array(
        'default_visibility' => $depencency_group['dependency_default_visibility'],
        'name' => $depencency_name,
        'operation' => $depencency_group['dependency_operation'],
        'type' => $dependency_widget
      );
      switch ( $dependency_type ) {
        case 'single_option':
        case 'multi_option':        
          $data['value'] = $depencency_group['dependency_value_radio'];
          break;
        case 'text':
          $data['value'] = $depencency_group['dependency_value_text'];
          break;
        case 'checkbox':
          $data['value'] = $depencency_group['dependency_value_checkbox'] === 'on';
          break;
      }      
      $field->set_visibility( $data );
      
    } else {
      $field->set_visibility( '' );
    }
    $field->save();
    */
    $this->edit_page_redirect( $field_id, true );
  }
  
  /**
   * The fields form shortcoe
   * 
   * @param  array  $atts
   * @return string    
   */
  public function form_shortcode( $atts = array() ) {        
    $field_id = isset( $atts['field_id'] ) ? $atts['field_id'] : '';     
    
    $output = '<div class="wrap"><h1 class="wp-heading-inline">';           
    $output .= empty( $field_id ) ? 
                __( 'Add new field', 'wpf' ) : 
                __( 'Edit field', 'wpf' );
    $output .= '</h1></div>';
    if ( isset( $field_id ) && isset( $_GET['wpf_nonce'] ) && wp_verify_nonce( $_GET['wpf_nonce'], 'delete' ) ) {
      $field = new WPF_Field( $field_id );      
      do_action( 'wpf_field_delete', $field );
      $field->delete();      
      $this->list_page_redirect();      
    }
    $cmb = cmb2_get_metabox( self::FORM_ID, 'custom' );        
    
    $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
    $widget = isset( $_POST['widget'] ) ? sanitize_text_field( $_POST['widget'] ) : '';     
    $chargeable = isset( $_POST['chargeable'] ) ? sanitize_text_field( $_POST['chargeable'] ) : 0;
    $charge_type = isset( $_POST['charge_type'] ) ? sanitize_text_field( $_POST['charge_type'] ) : 'fixed_text';
    $unit = isset( $_POST['unit'] ) ? sanitize_text_field( $_POST['unit'] ) : '';
    $charge = isset( $_POST['charge'] ) ? sanitize_text_field( $_POST['charge'] ) : '';
    $field_default_text = isset( $_POST['field_default_text'] ) ? 
                          sanitize_text_field( $_POST['field_default_text'] ) : '';
    $field_default_checkbox = isset( $_POST['field_default_checkbox'] ) ? 
                              sanitize_text_field( $_POST['field_default_checkbox'] ) : 0;
    $field_default_option_single  = isset( $_POST['field_default_option_single'] ) ? 
                                    sanitize_text_field( $_POST['field_default_option_single'] ) : '';
    $field_default_option_multiple  = isset( $_POST['field_default_option_multiple'] ) ? 
                                      array_map( 'sanitize_text_field', $_POST['field_default_option_multiple'] ) : '';    
    // the form is not submitted (init)    
    if ( isset( $field_id ) && !empty( $field_id ) && empty( $title ) ) {           
      $items = $this->get_items();
      $field_item = $items[ $field_id ];                  
      $name = $field_item['name'];      
      $title = $field_item['title'];
      $widget = $field_item['widget'];
      $chargeable = $field_item['chargeable'];   
      $charge_type = $field_item['charge_type']; 
      $unit = $field_item['unit'];      
      $required = $field_item['required'];      
      $options_extra = $field_item['options_extra'];      
      $active_by_default = $field_item['active_by_default']; 
      /* @TODO - Field dependency feature
      $add_dependency = !empty( $field_item['visibility'] );
      */
      $charge = '';
      $field_default_text = '';
      $field_default_checkbox = '';
      $field_default_option_single = '';
      $field_default_option_multiple = array();

      $options = array();
      if ( isset( $field_id ) ) {
        $options = wpf_get_field_options( $field_id );
      }      
      if ( ! $required ) {
        $options = array( '' => '----- None -----' ) + $options;
      }                                                    
      $cmb->update_field_property( 'field_default_option_single', 'options', $options );   

      global $wpf_widgets;      
      switch ( $wpf_widgets[$widget]['type'] ) {
        case 'text':
          $charge = $field_item['charge'];          
          $field_default_text = $field_item['default_value'];          
          break;
        case 'checkbox':
          $charge = $field_item['charge'];          
          $field_default_checkbox = $field_item['default_value'];          
          break;
        case 'single_option':   
               
          $field_default_option_single = $field_item['default_value'];          
          break;
        case 'multi_option':          
          $field_default_option_multiple = explode( ',', $field_item['default_value'] );
          break;
      }             
    }
    
    if ( ! empty( $title ) ) {
      // overcome CMB2 https://github.com/CMB2/CMB2/issues/894 
      $cmb->update_field_property( 'title', 'default_cb', function () use ( $title ) {
          return $title;
      } );
    }
    // overcome CMB2 https://github.com/CMB2/CMB2/issues/894 
    if ( ! empty( $name ) ) {
      $cmb->update_field_property( 'name', 'default_cb', function () use ( $name ) {
          return $name;
      } );
    }    
    if ( ! empty( $widget ) ) {
      $cmb->update_field_property( 'widget', 'default', $widget );      
    }
    if ( ! empty( $chargeable ) ) {
      $cmb->update_field_property( 'chargeable', 'default', $chargeable );
    }
    if ( ! empty( $charge_type ) ) {
      $cmb->update_field_property( 'charge_type', 'attributes', 'data-value='.$charge_type );
    }  
    if ( ! empty( $unit ) ) {
      $cmb->update_field_property( 'unit', 'default', $unit );
    }                        
    if ( ! empty( $charge ) ) {
      $cmb->update_field_property( 'charge', 'default', $charge );
    }    
    if ( ! empty( $required ) ) {
      $cmb->update_field_property( 'required', 'default', $required );
    }
    if ( ! empty( $options_extra ) ) {
      $cmb->update_field_property( 'options_extra', 'default', $options_extra );
    }    
    if ( ! empty( $active_by_default ) ) {
      $cmb->update_field_property( 'active_by_default', 'default', $active_by_default );
    }
    if ( ! empty( $field_default_option_single ) ) {      
      $cmb->update_field_property( 'field_default_option_single', 'default', $field_default_option_single );
    }  
    if ( ! empty( $field_default_option_multiple ) ) {
      $cmb->update_field_property( 'field_default_option_multiple', 'default', $field_default_option_multiple );
    }  
    // overcome CMB2 https://github.com/CMB2/CMB2/issues/894 
    if ( ! empty( $field_default_text ) ) {
      $cmb->update_field_property( 'field_default_text', 'default_cb', function () use ( $field_default_text ) {
          return $field_default_text;
      } );
    }  
    if ( ! empty( $field_default_checkbox ) ) {
      $cmb->update_field_property( 'field_default_checkbox', 'default', $field_default_checkbox );
    } 
    /* @TODO - Field dependency feature    
    if ( ! empty( $add_dependency ) ) {
      $cmb->update_field_property( 'add_dependency', 'default', $add_dependency );
    }
    */

    $error = $cmb->prop( 'submission_error' );    
    // Get any submission errors
    if ( is_wp_error( $error ) ) {
      // If there was an error with the submission, add it to our ouput.
      $msg = $error->get_error_message();
      $output .= '<div id="message" class="error inline">' . sprintf( __( '%s', 'wpf' ), '<h4>'. $msg .'</h4>' ) . '</div>';
    }
    $add_field_url = add_query_arg( 
      array(
        'page' => self::ADD_PAGE          
      ),
      admin_url( self::PARENT_MENU )
    );
    $add_product_url = add_query_arg( 
      array(
        'post_type' => 'product'          
      ),
      admin_url( 'post-new.php' )
    );    

    // If the post was submitted successfully, notify the user.
    if ( isset( $_GET['submitted'] ) ) {      
      // Add notice of submission to our output
      $output .= '<div class="notice notice-success is-dismissible"> 
                  <p>'.sprintf( __( "The field has been saved. <a href='{$add_field_url}'>Add another field</a> or <a href='{$add_product_url}'>Create a product</a> (Use <em>Front fields</em> section to handle the fields per product.) ", 'wpf' ) ).'</p>
                </div>';
    }   
    $output .= cmb2_get_metabox_form( $cmb, 'custom', array( 'save_button' => __( 'Save', 'wpf' ) ) );
    return $output;
  }  

  /**
   * hook-handler: cmb2_override_meta_value
   * Edit product form. Set up default values for the field group form element.
   */
  public function group_elements_submit( $value, $object_id, $args, $field ) {  
    switch ( $args['field_id'] ) {          
      case 'field_options_group':
        // Only set the default if the original value has not been overridden
        if ( 'cmb2_field_no_override_val' !== $value ) {
            return $value;
        }  
        if ( isset( $_GET['page'] ) &&
             $_GET['page'] === 'wpf_field_edit' &&
             isset( $_GET['field_id'] ) ) {
          $field_id = sanitize_text_field( $_GET['field_id'] );
          $group = array();
          $options = WPF_Field_Option_DS::instance()
                      ->get_options_by_field_id( $field_id );
          foreach ( $options as $option ) {
            $group[] = array(
              'field_option_id' => $option['id'],
              'field_option_image_id' => $option['data'],
              'field_option_image' => wp_get_attachment_image_src( $option['data'] )[0],
              'field_option_title' => $option['title'],          
              'field_option_price' => $option['price'],
              'field_option_weight' => $option['weight'],
            );
          }
          return $group;
        }        
        return $value; 
      /* @TODO - Field dependency feature  
      case 'field_dependency_group':
        // Only set the default if the original value has not been overridden
        if ( 'cmb2_field_no_override_val' !== $value ) {
            return $value;
        }  
        if ( isset( $_GET['page'] ) &&
             $_GET['page'] === 'wpf_field_edit' &&
             isset( $_GET['field_id'] ) ) {
          $group = array();
          $wpf_field = new WPF_Field( $_GET['field_id'] );
          $data = $wpf_field->get_visibility();
          if ( empty( $data ) ) {
            return $value;
          }
          $group[0] = array(
            'dependency_default_visibility' => $data['default_visibility'],
            'dependency_field' => $data['name'],
            'dependency_operation' => $data['operation'],              
          );
          global $wpf_widgets;
          switch ( $wpf_widgets[ $data['type'] ] ) {
            case 'text': 
              $group[0]['dependency_value_text'] = $data['value'];              
              break;
            case 'checkbox':
              $group[0]['dependency_value_checkbox'] = $data['value'];              
              break;
            case 'single_option':            
            case 'multi_option':              
              $field->args['attributes']['data-value'] = $data['value'];
              break;              
          }          
          
          return $group;
        }
        break;
        */
    }
    return $value;
  }

  /**
   * hook-handler: before_delete_post
   * Delete a product by its id
   * 
   * @param integer $product_id   
   */
  public function before_delete_product( $product_id ) {
    WPF_Field_Product_DS::instance()->delete_by_product( $product_id );
  }  

  /**
   * hook-handler: wpf_units
   * 
   * Define wpf units
   * @param  array $units
   * @return array
   */
  public function wpf_units( $units ) {
    $units['g'] = __( 'g', 'wpf' );
    $units['kg'] = __( 'kg', 'wpf' );
    $units['mm'] = __( 'mm', 'wpf' );
    $units['cm'] = __( 'cm', 'wpf' );  
    $units['m'] = __( 'm', 'wpf' );
    $units['lbs'] = __( 'lbs', 'wpf' );
    $units['oz'] = __( 'oz', 'wpf' );
    $units['in'] = __( 'in', 'wpf' );
    $units['yd'] = __( 'yd', 'wpf' );
    return $units;
  }

  /**
   * The edit page redirect 
   * 
   * @param  integer  $field_id 
   * @param  boolean $show_sumbit_message         
   */
  private function edit_page_redirect( $field_id, $show_sumbit_message = false ) {
    wp_redirect( 
      esc_url_raw( 
        add_query_arg( 
          array(
            'page' => self::EDIT_PAGE,
            'field_id' => $field_id,
            'action' => 'edit',
            'submitted' => $show_sumbit_message
          ),
          admin_url( self::PARENT_MENU )
        ) 
    ) );
    exit;     
  }

  /**
   * The list page redirect   
   */
  private function list_page_redirect() {
    wp_redirect( 
      esc_url_raw( 
        add_query_arg( 
          array(
            'page' => self::LIST_PAGE          
          ),
          admin_url( self::PARENT_MENU )
        ) 
    ) );
    exit;     
  }

  /**
   * Get add field url   
   * @param  string $destination 
   * @return string              
   */
  private function get_add_url( $destination = self::LIST_PAGE ) {
    return add_query_arg(
        array(
          'page' => self::ADD_PAGE,          
          'destination' => $destination
        ),
        admin_url('admin.php')
      );
  }
  
  /**
   * Get edit field url
   * 
   * @param  integer $field_id    
   * @param  string $destination 
   * @return string              
   */
  private function get_edit_url( $field_id, $destination = self::LIST_PAGE ) {
    return add_query_arg(
      array(          
        'page' => self::EDIT_PAGE,          
        'field_id' => $field_id,
        'destination' => $destination,          
        'action' => 'edit'
      ),
      admin_url('admin.php')
    );
  }

  /**
   * Get delete field url
   * 
   * @param  integer $field_id    
   * @param  string $destination 
   * @return string              
   */
  private function get_delete_url( $field_id, $destination = self::LIST_PAGE ) {
    return wp_nonce_url( add_query_arg(
      array(          
        'page' => self::EDIT_PAGE,          
        'field_id' => $field_id,
        'destination' => $destination,          
        'action' => 'delete'
      ),
      admin_url('admin.php')
    ), 'delete', 'wpf_nonce' );
  } 
}

