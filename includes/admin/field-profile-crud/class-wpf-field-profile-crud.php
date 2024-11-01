<?php

defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @since   1.0.0
 */
class WPF_Field_Profile_CRUD {    
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

  const LIST_PAGE         = 'wpf_field_profiles_list';
  const ADD_PAGE          = 'wpf_field_profile_add';
  const EDIT_PAGE         = 'wpf_field_profile_edit';
  const FORM_ID           = 'wpf_field_profile_form';
  const FORM_SHORTCODE    = 'wpf_field_profile_form';  
  const FIELDS_OPTION_ID  = 'wpf_field_profiles';
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
   * Get all field profiles 
   * 
   * @return array
   */
  private function get_items() {
    return wpf_get_field_profiles();
  }     
   
  /**
   * hook handler: admin_enqueue_scripts
   * Register the stylesheets for the admin area.                 
   */
  public function enqueue_styles( $hook ) {                
    if ( isset( $_GET['page'] ) && ( 
         self::ADD_PAGE == $_GET['page'] || 
         self::EDIT_PAGE == $_GET['page'] ) ) {
      wp_enqueue_style( get_class( $this ), plugin_dir_url( __FILE__ ) . 'css/wpf-field-profile-crud.css', array(), $this->version, 'all' );
      wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
    }
  }  

  /**
   * hook handler: admin_enqueue_scripts
   * Register the JavaScript for the admin area.   
   */
  public function enqueue_scripts( $hook ) {           
    if ( (isset( $_GET['page'] ) && ( 
         self::ADD_PAGE == $_GET['page'] || 
         self::EDIT_PAGE == $_GET['page'] ) ) ||
         ( 'edit.php' === $hook && isset( $_GET['post_type'] ) 
           && 'product' == $_GET['post_type'] ) ) {
      $script_id = get_class( $this );
      $items = $this->get_items();
      foreach ( $items as $id => $item ) {
        $items[$id]['url'] = add_query_arg( 
          array(
            'post_type' => 'product',
            'fpid' => $id
          ),
          admin_url( 'post-new.php' )
      );
      }
      wp_enqueue_script( $script_id, plugin_dir_url( __FILE__ ) . 'js/wpf-field-profile.js', array( 'jquery', 'jquery-tiptip', 'jquery-ui-sortable' ), $this->version, false );    
      wp_localize_script(
        $script_id,
        'wpf_field_profiles',
        array(                        
          'ajax_url' => admin_url( 'admin-ajax.php' ),
          'items' => $items
        )
      );     
    }   
  }     

  /**
   * hook handler: admin_menu
   * Add submenu pages: Field profile list, Add field profile form, Edit field profile form       
   */
  public function admin_menu_pages() {
    $list_page_name = __( 'WPF Field Profiles', 'wpf' );
    add_submenu_page( self::PARENT_MENU, $list_page_name, $list_page_name, 'manage_wpf_fields', self::LIST_PAGE, array( $this, 'list_menu_page_handler' ) );
    $add_form_name = __( 'Add field profile', 'wpf' );
    add_submenu_page( null, $add_form_name, $add_form_name, 'manage_wpf_fields', self::ADD_PAGE, array( $this, 'add_form_menu_page_handler' ) );
    $edit_form_name =  __( 'Edit field profile', 'wpf' );
    add_submenu_page( null, $edit_form_name, $edit_form_name, 'manage_wpf_fields', self::EDIT_PAGE, array( $this, 'edit_form_menu_page_handler' ) );
  }

  /**
   * The list page handler of the add_submenu_page    
   */
  public function list_menu_page_handler() { 
    $items = $this->get_items();                         
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo __( 'WPF Field Profiles', 'wpf' ); ?></h1>
      <a href="<?php echo esc_url( $this->get_add_url() ); ?>" class="page-title-action"> <?php echo __( 'Add New', 'wpf' ); ?></a>    
      <table class="wp-list-table wpf-field-profiles widefat fixed striped">
        <thead>       
        <td scope="col" class="manage-column column-title"><?php echo __( 'Title', 'wpf' ); ?> </td>
        <td scope="col" class="manage-column column-actions"><?php echo __( 'Actions', 'wpf' ); ?></td>
        </thead>
        <tbody>  
        <?php if ( sizeof( $items ) ) : ?>
        <?php foreach ( $items as $item ) : ?>
          <tr data-id="<?php echo $field_item['id']; ?>">        
            <td><?php echo $item['name']; ?></td>                        
            <td><a href="<?php echo esc_url( $this->get_edit_url( 
                $item['id'] ) ); ?>">
                <?php echo __( 'Edit', 'wpf' ); ?>
                <a>
                <a href="<?php echo esc_url( $this->get_delete_url( 
                $item['id'] ) ); ?>"
                onclick="return confirm('<?php echo __( 'Are you sure you want to delete this profile? This action cannot be undone.', 'wpf' ); ?>');">
                <?php echo __( 'Delete', 'wpf' ); ?>
                <a>
            </td>
          </tr>    
        <?php endforeach; ?>
        <?php else : ?>
          <tr class="no-items"><td colspan="2"><?php echo __( 'No items found. You can add a new field profile by clicking on `Add new` button next to the page title', 'wpf' ); ?></td>
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
    $id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
    $id = esc_attr( $id );
    echo do_shortcode( "[" . self::FORM_SHORTCODE . " id='{$id}']" );
  }

  /**
   * hook handler: cmb2_admin_init
   * The cmb2 form    
   */
  public function form() {
    $profile_id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
    $cmb = new_cmb2_box( array(
      'id'           => self::FORM_ID,      
      'object_types' => array( 'post' ),     
      'hookup'       => false,
      'save_fields'  => false, 
      'classes' => array( !empty( $profile_id ) ? 'wpf-edit-form' : 'wpf-add-form' ),      
    ) ); 
    if ( !empty( $profile_id ) ) {
      $cmb->add_field( array(            
        'id'      => 'id',
        'type'    => 'hidden',
        'default' => esc_attr( $profile_id ),
      ) ); 
    }
    $cmb->add_field( array(
      'name'    => 'Name',        
      'id'      => 'name',    
      'type'    => 'text',
      'default' => '',      
      'attributes' => array(
        'required' => 'required',        
        'data-form-type' => !empty( $profile_id ) ? 'edit' : 'add'
      ),
    ) );   
    $cmb->add_field( array(        
      'id'      => 'saved_name',    
      'type'    => 'hidden',
      'default' => '',      
    ) ); 
    $fields = wpf_get_fields();
    foreach ( $fields as $key => $field ) {
      $fields[$key]['title'] = $field['title'] . ' ( '.$field['name'].' )';
    }
    $updated_fields = array_column( $fields, 'title', 'id' );
    $cmb->add_field( array(
      'name'    => 'Fields',        
      'id'      => 'fields',
      'type'    => 'select',
      'options' => array( '' => '-- Select a field --' ) + $updated_fields,
      'after' => ' <a class="button" id="wpf-add-field" href="#">Add</a>',
      'after_row'  => '<div id="wpf-field-slider"></div>',
      'default' => '',      
    ) );    
    $cmb->add_field( array(      
      'id'      => 'fids',    
      'type'    => 'hidden',
      'default' => ''
    ) );
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

    if ( empty(  $sanitized_values['name'] )   ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The name is required.', 'wpf' ) ) );    
    } 
    $profile_id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
    $edit_form = !empty( $profile_id );
    $except_current = $edit_form ? $sanitized_values['saved_name'] : '';            
    if ( !wpf_is_profile_name_unique( $sanitized_values['name'], $except_current ) ) {
      return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The name is used by another profile. Please rename this profile.', 'wpf' ) ) );    
    }                       
    
    $name = $sanitized_values['name'];
    $fids = $sanitized_values['fids'];

    $field_profile = $edit_form ? 
                      new WPF_Field_Profile( $profile_id ) : 
                      new WPF_Field_Profile();
    $field_profile->set_name( $name );    
    $field_profile->set_fields( $fids ); 
    $field_profile->save();
    $profile_id = $field_profile->get_id();  

    if ( $edit_form ) {
      do_action( 'wpf_field_profile_update', $field_profile, $sanitized_values );        
    } else {
      do_action( 'wpf_field_profile_create', $field_profile, $sanitized_values );
    }

    $this->edit_page_redirect( $profile_id, true );
  }
  
  /**
   * The fields form shortcoe
   * 
   * @param  array $atts
   * @return string    
   */
  public function form_shortcode( $atts = array() ) {        
    $id = isset( $atts['id'] ) ? $atts['id'] : '';             
    $output = '<div class="wrap"><h1 class="wp-heading-inline">';           
    $output .= empty( $id ) ? 
                __( 'Add new field profile', 'wpf' ) : 
                __( 'Edit field profile', 'wpf' );
    $output .= '</h1></div>';
    if ( isset( $id ) && isset( $_GET['wpf_nonce'] ) && wp_verify_nonce( $_GET['wpf_nonce'], 'delete' ) ) {
      $field_profile = new WPF_Field_Profile( $id );   
      do_action( 'wpf_field_profile_delete', $field_profile );    
      $field_profile->delete();      
      $this->list_page_redirect();      
    }
    $cmb = cmb2_get_metabox( self::FORM_ID, 'custom' );        
    
    $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
    $fids = isset( $_POST['fids'] ) && is_array( $_POST['fids'] ) ? array_map( 'sanitize_text_field', $_POST['fids'] ) : '';  
    if ( isset( $id ) && !empty( $id ) && empty( $name ) ) { 
      $items = $this->get_items();         
      $field_item = $items[ $id ];                       
      $name = $field_item['name'];
      $fids = isset( $field_item['fields'] ) ? $field_item['fields'] : '';      
    }
    
    if ( ! empty( $name ) ) {
      $cmb->update_field_property( 'name', 'default', $name );
      $cmb->update_field_property( 'saved_name', 'default', $name );
    }    
    if ( ! empty( $fids ) ) {
      $cmb->update_field_property( 'fields', 'attributes', 'data-fids='.$fids );
    }    

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
                    <p>'.sprintf( __( "The field profile has been saved.", 'wpf' ) ).'</p>
                  </div>';
    }   
    $output .= cmb2_get_metabox_form( $cmb, 'custom', array( 'save_button' => __( 'Save', 'wpf' ) ) );
    return $output;
  }

  /**
   * Add field profiles links to the Products menu   
   */
  public function field_profiles_links() {        
    if ( 'no' === get_option( 'wpf_field_profile_show_links', 'no' ) ) {
      return;
    }
    $items = $this->get_items(); 
    foreach ( $items as $id => $field_profile ) {
      $name = ucwords( $field_profile['name'] );
      $name = __( "Add New {$name}", 'wpf' );      
      add_submenu_page( 'edit.php?post_type=product', $name, $name, 'manage_wpf_fields', 'post-new.php?post_type=product&fpid='. $id ); 
    }          
  }  

  /**
   * The edit page redirect 
   * 
   * @param  integer  $id 
   * @param  boolean $show_sumbit_message         
   */
  private function edit_page_redirect( $id, $show_sumbit_message = false ) {
    wp_redirect( 
      esc_url_raw( 
        add_query_arg( 
          array(
            'page' => self::EDIT_PAGE,
            'id' => $id,
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
   * @param  integer $id    
   * @param  string $destination 
   * @return string              
   */
  private function get_edit_url( $id, $destination = self::LIST_PAGE ) {
    return add_query_arg(
      array(          
        'page' => self::EDIT_PAGE,          
        'id' => $id,
        'destination' => $destination,          
        'action' => 'edit'
      ),
      admin_url('admin.php')
    );
  }

  /**
   * Get delete field url
   * 
   * @param  integer $id    
   * @param  string $destination 
   * @return string              
   */
  private function get_delete_url( $id, $destination = self::LIST_PAGE ) {
    return wp_nonce_url( add_query_arg(
      array(          
        'page' => self::EDIT_PAGE,          
        'id' => $id,
        'destination' => $destination,          
        'action' => 'delete'
      ),
      admin_url('admin.php')
    ), 'delete', 'wpf_nonce' );
  }
}

