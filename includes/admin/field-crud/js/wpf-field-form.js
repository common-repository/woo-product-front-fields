(function( $ ) {
  $(document).ready(function() {          
    $( '#wpf_field_form input[name="submit-cmb"]' ).click( function() {
      let use_images = 'image' === $( '#wpf_field_form #options_extra' ).val(),
          fieldType = $( '#wpf_field_form #widget' ).val();
      // check only if Image is picked for `Options extra` field and if element has
      // `allow_images` property
      if ( ! use_images || ! wpf_field_form.wpf_widgets[fieldType]['allow_images'] ) {
        return true;
      }      
      // check if all images has been choosen for every option
      let $items  = $( '#wpf_field_form .cmb2-id-field-options-group .cmb-type-file' );      
      for ( let i = 0; i < $items.size(); i++ ) {
        let $item = $($items[i]);
        if ( ! $item.find( '.img-status img' ).size() ) {
          alert( 'Add an image for each option' );          
          return false;
        }        
      }      
    } ); 
    
    $( '#wpf_field_form #chargeable' ).change( function() {      
      if ( $(this).is( ':checked' ) ) {        
        toggleOptionPrices( 'show' );
        refreshElementsVisibility( $( '#wpf_field_form #widget' ).val() );        
      } else {        
        toggleOptionPrices( 'hide' );
        refreshElementsVisibility( $( '#wpf_field_form #widget' ).val() );        
      }
    } );    
        
    // setup woocommerce tiptip for wpf fields form
    $( '#wpf_field_form .woocommerce-help-tip' ).tipTip({
      'attribute': 'data-tip',
      'fadeIn': 50,
      'fadeOut': 50,
      'delay': 200
    });

    $( '#wpf_field_form button.cmb-remove-group-row-button' ).each(function() {
      let $deleteButton = $(this);
      let $group = $deleteButton.parents( '.cmb-repeatable-grouping' );
      $group.find( '.field-option-id' ).parent().parent().hide();
      $group.find( '> button.cmb-remove-group-row' ).remove();
      $group.find( '.cmbhandle').remove();
      $group.find( '.cmbhandle-title').remove();
      // check if field option is present in the database and warn a user
      $deleteButton.click( function() {
        let optionId = $group.find( '.field-option-id' ).val(),
            fieldId = $( '#wpf_field_form #field_id' ).val();
        let data = {          
          action: 'field_option_delete',
          option_id: optionId,
          field_id: fieldId
        };        
        var flag = false;
        $.ajax({
          type: 'POST',
          url: wpf_field_form.ajax_url,
          data: data,
          success: function( result ) {                      
            if ( result.count == 0 || ( result.count > 0 && confirm( `Are you sure you want to delete an option that was overridden for ${result.count} product(s)? This action cannot be undone.` ) ) ) {              
              flag = true;              
            }
          },          
          async: false
        });        
        return flag;        
      });
    });

    // field name auto-creation based on Field title
    $( '#wpf_field_form #title' ).keyup(function() {      
      if ( $(this).attr( 'data-form-type' ) !== 'add' ) {
        return false;
      }
      let value = $.trim( $(this).val() )
                    .toLowerCase()
                    .replace( /[^\w\s]/gi, '' )
                    .replace( / /g, '_' );
      $( '#wpf_field_form #name' ).val( value );      
    });        

    $( '#wpf_field_form #widget' ).change(function() {
      let fieldType = $(this).val();
      if ( true === wpf_field_form.wpf_widgets[fieldType]['allow_images'] ) {
        $( '#wpf_field_form .cmb2-id-options-extra' ).show();
      } else {
        $( '#wpf_field_form .cmb2-id-options-extra' ).hide();
        $( '#wpf_field_form #options_extra' ).val('');
      }      
      $( '#wpf_field_form #options_extra' ).trigger('change');
      
      $( '#wpf_field_form #required' ).trigger( 'change' );
      
      let data = {
        action: 'get_charge_types',            
        widget: fieldType
      };
      $.post( wpf_field_form.ajax_url, data, function( result ) {        
        if ( ! result.status ) {
          return;
        }
        $chargeType = $( '#wpf_field_form #charge_type' );
        $chargeType.html('');

        let defaultValue = $chargeType.attr('data-value');
        var existsValue = false;
        $.each( result.chargeTypes, function( key, value ) {
            if ( ! existsValue ) {
              existsValue = ( key === defaultValue );              
            }          
            $chargeType.append($("<option/>", {
                value: key,
                text: value
            } ) );
          if ( existsValue ) {
            $chargeType.val( defaultValue ).change();
          } else {
            $chargeType[0].selectedIndex = 0;
          }
        });              
      } );         
      if ( undefined === $(this).attr( 'data-first-change' ) ) {
        refreshElementsVisibility( fieldType );        
        $(this).attr( 'data-first-change', true );
      } else {  
        refreshElementsVisibility( fieldType );
        // clean text default field because it can be switched to different text type, 
        // for example from Text and Color or Date        
        $( '#wpf_field_form .cmb2-id-field-default-text' )
          .hide().find( '#field_default_text' ).val('');        
        $( '#wpf_field_form .cmb2-id-field-default-checkbox' ).hide();      
        $( '#wpf_field_form .cmb2-id-field-default-option-single' ).hide();
        $( '#wpf_field_form .cmb2-id-field-default-option-multiple' ).hide();        
        if ( ! $( '.wpf-submit-notice' ).size() ) {
          $( 'input[name="submit-cmb"]' ).after( '<div class="wpf-submit-notice">Submit a form to set up a default value for this field.</div>');
        }
      }        
    }).trigger('change'); 

    $( '#wpf_field_form #options_extra' ).change( function() { 
      let fieldType = $( '#wpf_field_form #widget' ).val(); 
      switch ( $(this).val() ) {
        case 'image':          
          toggleOptionImages( 'show' );
          break;
        case '':
          toggleOptionImages( 'hide' );
          break;
      }            
    } ).change();

    // when Required is true - single_option fields should have a default value
    $( '#wpf_field_form #required' ).change( function() {
      let fieldType = $( '#wpf_field_form #widget' ).val(),
          type = wpf_field_form.wpf_widgets[fieldType]['type'];
      if ( 'single_option' === type ) {
        let firstOption = $( '#field_default_option_single option:first' );
        if ( $(this).is( ':checked' ) && '' === firstOption.val() ) {
          firstOption.remove();
          $( '#field_default_option_single' )[0].selectedIndex = 0;
        } else if ( '' !== firstOption.val() ) {
          $( '#field_default_option_single' ).prepend( $("<option/>", {
              value: '',
              text: '---- None ----'
          } ) );
        }        
      }
    } );

    /*
     *********************************
     @TODO - Field dependency feature 
     ********************************* 
    $( '#add_dependency' ).change( function() {
      if ( $(this).is( ':checked' ) ) {
        $( '.cmb2-id-field-dependency-group' ).show();
        //$( '.cmb2-id-dependency-field' ).show().find( '#dependency_field' ).trigger('change');                
      } else {
        $( '.cmb2-id-field-dependency-group' ).hide();        
      }
    } ).trigger( 'change' );

    $( '.cmb2-id-field-dependency-group select[id*="dependency_field"]' ).change( function() {
      let fieldName = $(this).val(),          
          data = {
            action: 'get_field_info',            
            name: fieldName            
          }; 
      if ( '' === fieldName ) {
        return false;
      }
      $.post( wpf_field_form.ajax_url, data, function( result ) {        
        if ( ! result.status ) {
          return;
        }        

        let $dependencyRadio = $( '.cmb2-id-field-dependency-group div[class*="dependency-value-radio"]' ),
            $dependencyCheckbox = $( '.cmb2-id-field-dependency-group div[class*="dependency-value-checkbox"]' ),
            $dependencyText = $( '.cmb2-id-field-dependency-group div[class*="dependency-value-text"]' ),
            $dependencyOperation = $( '.cmb2-id-field-dependency-group div[class*="dependency-operation"]' ), 
            $dependencyValue;
  
        let type = wpf_field_form.wpf_widgets[result.type]['type'];

        switch ( result.type ) {
          case 'single_option':
          case 'multi_option':          
            $dependencyValue = $( '.cmb2-id-field-dependency-group select[id*="dependency_value_radio"]' );
            $dependencyValue.html('');
            $.each( result.options, function( key, value ) {                    
              $dependencyValue.append( $("<option/>", {
                  value: key,
                  text: value
              } ) );        
            }); 
            let value = $dependencyValue.attr( 'data-value' );            
            if ( undefined != value ) {
              $dependencyValue.val( value );
              $dependencyValue.removeAttr( 'data-value' );
            } else {
              // select first option
              $dependencyValue[0].selectedIndex = 0;
            }
            $dependencyRadio.show();
            $dependencyText.hide();
            $dependencyCheckbox.hide();
            $dependencyOperation.hide();
            break;
          case 'text':
            $dependencyValue = $( '.cmb2-id-field-dependency-group select[id*="dependency_value_text"]' );
            $dependencyRadio.hide();
            $dependencyText.show();
            $dependencyCheckbox.hide();
            $dependencyOperation.show();
            break;
          case 'checkbox':
            $dependencyValue = $( '.cmb2-id-field-dependency-group select[id*="dependency_value_checkbox"]' );
            $dependencyRadio.hide();
            $dependencyText.hide();
            $dependencyCheckbox.show();
            $dependencyOperation.hide();
            break;
        }
      } );      
    } ).trigger( 'change' );
    */
  });

  let refreshElementsVisibility = ( fieldType ) => {      
    $( '#wpf_field_form #options_extra' ).trigger('change');
    let isChargeable = $( '#wpf_field_form #chargeable' ).is( ':checked' );
    if ( isChargeable ) {      
      $( '#wpf_field_form .cmb2-id-charge-type' ).show();
    } else {      
      $( '#wpf_field_form .cmb2-id-charge-type' ).hide();
    }
    let type = wpf_field_form.wpf_widgets[fieldType]['type'];
    switch ( type ) {
      case 'text':
      case 'checkbox':                
        if ( isChargeable ) {
          $( '#wpf_field_form .cmb2-id-charge' ).show();
          $( '#wpf_field_form .cmb2-id-charge-type' ).show();
        } else {
          $( '#wpf_field_form .cmb2-id-charge' ).hide();
          $( '#wpf_field_form .cmb2-id-charge-type' ).hide();
        }
        $( '#wpf_field_form .cmb2-id-field-default-option-single' ).hide();
        $( '#wpf_field_form .cmb2-id-field-default-option-multiple' ).hide();
        $( '#wpf_field_form .cmb2-id-field-options-group' ).hide();        
        removeGroupValidation();                  
        if ( 'text' === type ) {
          $( '#wpf_field_form .cmb2-id-field-default-text' ).show();
          $( '#wpf_field_form .cmb2-id-field-default-checkbox' ).hide();
        } else if ( 'checkbox' === type ) {
          $( '#wpf_field_form .cmb2-id-field-default-text' ).hide();
          $( '#wpf_field_form .cmb2-id-field-default-checkbox' ).show();  
        }
        break;
      case 'single_option':                      
        $( '#wpf_field_form .cmb2-id-charge' ).hide();   
        $( '#wpf_field_form .cmb2-id-field-default-text' ).hide();
          $( '#wpf_field_form .cmb2-id-field-default-checkbox' ).hide();        
        $( '#wpf_field_form .cmb2-id-field-default-option-single' ).show();
        $( '#wpf_field_form .cmb2-id-field-default-option-multiple' ).hide();
        $( '#wpf_field_form .cmb2-id-field-options-group' ).show();
        addGroupValidation();
        if ( $( '#wpf_field_form .cmb2-id-field-options-group .postbox' ).size() < 2 ) {
          $( '#wpf_field_form .cmb2-id-field-default-option-single' ).hide();
        }
        break;                           
      case 'multi_option':         
        $( '#wpf_field_form .cmb2-id-charge' ).hide();
        $( '#wpf_field_form .cmb2-id-field-default-option-single' ).hide();
        $( '#wpf_field_form .cmb2-id-field-default-text' ).hide();
          $( '#wpf_field_form .cmb2-id-field-default-checkbox' ).hide();        
        $( '#wpf_field_form .cmb2-id-field-default-option-multiple' ).show();
        $( '#wpf_field_form .cmb2-id-field-options-group' ).show();
        addGroupValidation();
        if ( $( '#wpf_field_form .cmb2-id-field-options-group .postbox' ).size() < 2 ) {
          $( '#wpf_field_form .cmb2-id-field-default-option-multiple' ).hide();
        }  
        break;            
    }
    if ( 'single_option' === type || 'multi_option' === type ) {
      if ( isChargeable ) {
        toggleOptionPrices( 'show' );
      } else {
        toggleOptionPrices( 'hide' );
      }
    }
  }
  
  let toggleOptionPrices = ( state = 'show' ) => {    
    switch ( state ) {
      case 'show':        
        $( '#wpf_field_form .cmb2-id-field-options-group div[class*="field-option-price"]' ).show();        
        break;
      case 'hide':              
        $( '#wpf_field_form .cmb2-id-field-options-group div[class*="field-option-price"]' ).hide();
        break;
    }    
  }

  let toggleOptionImages = ( state = 'show' ) => {    
    switch ( state ) {
      case 'show':
        $( '#wpf_field_form .cmb2-id-field-options-group .cmb-type-file' ).show();        
        break;
      case 'hide':        
        $( '#wpf_field_form .cmb2-id-field-options-group .cmb-type-file' ).hide();
        break;
    }    
  }

  let addGroupValidation = () => {
    $( '#wpf_field_form .cmb2-id-field-options-group input[name*="field_option_title"]' ).each(function() {
      let $item = $(this),
        pattern = $item.attr('data-hidden-pattern');
      if (pattern != undefined ) {
        $item.prop( 'required', true );
        $item.prop( 'pattern', $item.attr('data-hidden-pattern') );
      }
    });      
  }

  let removeGroupValidation = () => {    
    $( '#wpf_field_form .cmb2-id-field-options-group input[name*="field_option_title"]' )
      .removeAttr( 'required' )
      .removeAttr( 'pattern' );
  }

})( jQuery );