(function( $ ) {  
  
  $(document).ready(function() {     
    // make fields table sortable    
    if ( $( '.wpf_front_fields_options' ).length ) {      
      $( '#wpf_front_fields_options .wpf-data' ).sortable( {
        stop: function( event, ui ) {
          let weight = 0;
          $(this).find( '.wpf-field-item' ).each( function() {
            $(this).find( 'input[name*="wpf_weight"]' ).val( weight );
            weight++;
          } );                  
        }
      } );
    }       

    $( '#wpf-fields-picker option' ).each( function() {
      let $option = $(this), value = $option.val();
      if ( '' !== value ) {
        let $activeCheckbox = $( `input[name="wpf_is_active_${value}"]` );
        if ( $activeCheckbox.is( ':checked' ) ) {
          $option.prop( 'disabled', true );
        } else {
          $option.prop( 'disabled', false );
        }
      }
    } );

    $( '#wpf-add-field' ).click( function() {   
      let $selectedOption = $( '#wpf-fields-picker option:selected' ),
          value = $selectedOption.val();     
      if ( '' !== value ) {        
        $( `input[name="wpf_is_active_${value}"]` )
          .trigger( 'click' )
          .closest( '.wpf-field-item' )
          .addClass( 'checked' );
        $selectedOption.prop( 'disabled', true );
        $( '#wpf-fields-picker' ).val('').trigger( 'change' );
      }
    } );

    wpfRecalculatePrices();    

    $( '#_regular_price' ).change( function() {
      wpfRecalculatePrices();
    } );

    $( '#_sale_price' ).change( function() {
      wpfRecalculatePrices();
    } );

    $( '.wpf-field-item' ).each(function() {
      let $item = $(this),
      $activeCheckbox        = $item.find( '.wpf-active-checkbox' ),        
      $overrideLink          = $item.find( '.wpf-override-link' ),        
      $overrideContent       = $item.find( '.wpf-override-content' ),        
      $overridePriceCheckbox = $item.find( '.wpf-override-price' ),
      $overrideValueCheckbox = $item.find( '.wpf-override-value' ),        
      $priceField = $item.find( '.wpf-price-field' ),
      $valueField = $item.find( '.wpf-field-value' );

      $priceField.change(function() {
        wpfRecalculatePrices();
      });
      $valueField.change(function() {
        wpfRecalculatePrices();
      });

      $activeCheckbox.change(function() {        
        if ( $(this).is( ':checked' ) ) {          
          $overrideLink.removeClass( 'disabled' );
          if ( $overrideLink.hasClass( 'expanded' ) ) {
            $overrideContent.show();
          }
        } else {          
          $overrideLink.addClass( 'disabled' );
          $overrideContent.hide();          
          $( `#wpf-fields-picker option[value="${$(this).attr('data-name')}"]` )
            .prop( 'disabled', false );
          $(this).closest( '.wpf-field-item' )
                 .removeClass( 'checked' );
        }
        wpfRecalculatePrices();
      });        
      $overridePriceCheckbox.change(function() {
        let targetName = $(this).attr( 'target-name' ),
            $priceSection = $( `[data-id="${targetName}"]` );
        if ( $(this).is( ':checked' ) ) {
          $priceSection.find( '.override' ).show();
          $priceSection.find( '.default' ).hide();          
        } else {
          $priceSection.find( '.override' ).hide();
          $priceSection.find( '.default' ).show();          
        }
        wpfRecalculatePrices();
      });
      $overrideValueCheckbox.change(function() {
        let $elements = $item.find( '.wpf-field-value' ),
            targetName = $(this).attr( 'target-name' ),
            $valueSection = $( `[data-id="${targetName}"]` );        
        if ( $(this).is( ':checked' ) ) {
          $valueSection.find( '.override' ).show();
          $valueSection.find( '.default' ).hide();          
        } else {
          $valueSection.find( '.override' ).hide();
          $valueSection.find( '.default' ).show();          
        }
        wpfRecalculatePrices();
      });      
      $overrideLink.click(function() {
        if ( $(this).hasClass( 'disabled' ) ) {
          return false;
        }
        if ( $(this).hasClass( 'expanded' ) ) {
          $(this).removeClass( 'expanded' );
          $overrideContent.hide();
        } else {
          $(this).addClass( 'expanded' );
          $overrideContent.show();
        }
        return false;
      });      
    });
  });

  let getBasePrice = () => {
    let regularPrice = $( '#_regular_price' ).val(),
        salePrice = $( '#_sale_price' ).val(),
        price = 'unset';
    if ( '' !== salePrice ) {
      return salePrice;
    }
    if ( '' !== regularPrice ) {
      return regularPrice;
    }    
    return price;
  }

  let wpfRecalculatePrices = () => {

    let basePrice = getBasePrice(), 
        wpfPrice = 0;
    if ( 'unset' === basePrice ) {
      $( '.wpf-updated-price-value').text('The regular price is unset');
      return 'The product price should be set to apply front fields';
    }

    let wpfData = {}, wpfFieldValues = {};      
    $( '.wpf-field-item' ).each( function() {
      let $item = $(this),    
        $isActive              = $item.find( '.wpf-active-checkbox' ),    
        $overridePriceCheckbox = $item.find( '.wpf-override-price' ),
        $overrideValueCheckbox = $item.find( '.wpf-override-value' ),        
        $price                 = $item.find( '.wpf-price-field' ),
        $value                 = $item.find( '.wpf-field-value' ),
        fieldName              = $overrideValueCheckbox.attr( 'data-name' ),
        type                   = $overrideValueCheckbox.attr( 'data-type' ),
        chargeType             = $overrideValueCheckbox.attr( 'data-charge-type' ),
        charge                 = 0,
        value                  = '',
        $radioItem, $priceField;                
      if ( $isActive.is( ':checked' ) ) {        
        wpfData[fieldName] = {
          charge_type: chargeType,
          type
        };
        switch ( type ) {
          case 'text':          
            // override value
            value = $overrideValueCheckbox.is( ':checked' ) ?
                    $value.val() : 
                    $overrideValueCheckbox.attr( 'data-default-value' );            
            // override price
            charge = $overridePriceCheckbox.is( ':checked' ) ?
                          $price.val() : $price.attr( 'data-default-price' );            
            if ( '' !== value && $.isNumeric( charge ) ) {            
              wpfData[fieldName]['value'] = value;
              wpfData[fieldName]['charge'] = charge;
            }
            break;
          case 'checkbox':
            // override value
            value = $overrideValueCheckbox.is( ':checked' ) ?
                        $value.is( ':checked' ) : 
                        $overrideValueCheckbox.attr( 'data-default-value' );
            // override price
            charge = $overridePriceCheckbox.is( ':checked' ) ?
                          $price.val() : $price.attr( 'data-default-price' );
            if ( value === true && $.isNumeric( charge ) ) {            
              wpfData[fieldName]['value'] = value;
              wpfData[fieldName]['charge'] = charge;
            }
            break;
          case 'single_option':                  
            // override value                  
            value = $overrideValueCheckbox.is( ':checked' ) ?
                        $item.find( '.wpf-field-value:checked' ).val() : 
                        $overrideValueCheckbox.attr( 'data-default-value' );            
            if ( '' === value ) {
              break;
            }
            $radioItem = $item.find(`.wpf-radio-item[data-option="${value}"]`);          
            $priceField = $radioItem.find( '.wpf-price-field' );          
            charge = $radioItem.find( '.wpf-override-price' ).is( ':checked' ) ? 
                          $priceField.val() :
                          $priceField.attr( 'data-default-price' );            
            if ( $.isNumeric( charge ) ) {            
              wpfData[fieldName]['value'] = value;
              wpfData[fieldName]['charge'] = charge;
            }
            break;
          case 'multi_option':
            // override value
            value = $overrideValueCheckbox.is( ':checked' ) ?
                        $item.find( '.wpf-field-value:checked' ).map(
                          function () {return this.value;
                        }).get().join( ',' ) : 
                        $overrideValueCheckbox.attr( 'data-default-value' );
            if ( '' === value ) {
              break;
            }
            let totalCharge = 0, values = value.split( ',' );
            for ( let optionId of values ) {
              $radioItem = $item.find(`.wpf-radio-item[data-option="${optionId}"]`);
              $priceField = $radioItem.find( '.wpf-price-field' );
              charge = $radioItem.find( '.wpf-override-price' ).is( ':checked' ) ? 
                          $priceField.val() :
                          $priceField.attr( 'data-default-price' );
              if ( $.isNumeric( charge ) ) {
                totalCharge += parseFloat( charge );
              }
            }
            wpfData[fieldName]['value'] = values;
            wpfData[fieldName]['charge'] = totalCharge;            
            break;
        } 
        wpfFieldValues[fieldName] = wpfData[fieldName]['value'];
      }        
    } );    
    $.post( wpf_product_form.ajax_url, 
      {          
        action: 'product_form_recalculate',            
        wpf_data: wpfData,
        wpf_field_values: wpfFieldValues
      }, 
      function( result ) {        
        if ( result.status ) {          
          basePrice = parseFloat( basePrice );              
          wpfPrice = basePrice + parseFloat( result.charges );    
          $( '.wpf-base-price-value').text( basePrice.toFixed(2) );
          $( '.wpf-updated-price-value').text( wpfPrice.toFixed(2) );
        }
      } 
    );
  }

})( jQuery );