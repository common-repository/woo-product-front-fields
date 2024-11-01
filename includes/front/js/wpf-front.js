(function( $ ) {
  $(document).ready(function() {
    $( '.wpf-widget-input' )
      .on('change', function() {      
      let $field = $(this), 
          $productPrice = $field
                    .closest( '.entry-summary' )
                    .find( '.price' ), value;        
      if ( $field.hasClass( 'wpf-multiwidget' ) ) {
        $field = $field.closest( '.wpf-checkboxes-group' );     
      }                
      let formData = $field.closest( 'form' ).serializeArray(); 
      
      let data = {          
        action: 'wc_price',
        product_id: wpf_product.id,
        form_data: formData          
      };
      let isSaleProduct = $productPrice.find( 'ins' ).length > 0;                
      let $throbber = $(`<img src="${wpf_product.throbber_path}" />`)
                      .appendTo( $field );
      $( "form.cart :input, form.cart select" ).prop( 'disabled', true );             
      $.post( wpf_product.ajax_url, data, function( result ) {  
        if ( true === result.status ) {            
          if ( isSaleProduct ) {
            $productPrice
              .find( 'del' )
              .html( result.data.updated_formatted_regular_price );
            $productPrice
              .find( 'ins' )
              .html( result.data.updated_formatted_sale_price );
          } else {
            $productPrice
              .html( result.data.updated_formatted_regular_price );
          }            
          // update "sticky container" price if it exists (storefront theme is active)
          if ( $( '.storefront-sticky-add-to-cart__content-price' ).length ) {
            if ( isSaleProduct ) {
              $( '.storefront-sticky-add-to-cart__content-price del' ).html(
                result.data.updated_formatted_regular_price
              );
              $( '.storefront-sticky-add-to-cart__content-price ins' ).html(
                result.data.updated_formatted_sale_price                                
              );
            } else {
              $( '.storefront-sticky-add-to-cart__content-price' ).html(
                result.data.updated_formatted_regular_price
              );  
            }              
          }
          $throbber.remove();
          $( "form.cart :input, form.cart select" ).prop( 'disabled', false );
        }
      });      
    });

    /* 
      PIZZA Size example

      $( '#pizza_size input' ).change( function() {
      let checkedIngridients = [];
      $('#ingridients input:checked').each(function() {
        checkedIngridients.push( $(this).val() );
      });      
      window.location.href = window.location.pathname + "?" + 
        $.param( {
          'wpf': '1',          
          'pizza_size': $(this).val(),
          'ingridients': checkedIngridients.join(',')
        } );
    } );*/

    /*
    @TODO - Field dependency feature
    ---    
    $( '.wpf-field' ).each( function() {
      let $el = $(this),
          targetId = $el.attr( 'data-target' ),
          expectedValue = $el.attr( 'data-value' );
          targetOperation = $el.attr( 'data-op' );
      if ( typeof targetId !== typeof undefined && 
           false !== targetId && 
           typeof expectedValue !== typeof undefined 
           && false !== expectedValue ) {
        let $targetContainer = $( '#' + targetId ),
            type = $targetContainer.attr( 'data-type' ),
            $targetInput;                
        $targetContainer.find( '.wpf-widget-input' ).change( function() {          
          let formData = $(this).closest( 'form' ).serializeArray();       
          let data = {          
            action: 'compare_dependency',
            product_id: wpf_product.id,
            target_field_id: targetId,
            operation: targetOperation,
            expected_value: expectedValue,
            target_type: type,
            form_data: formData          
          };
          $.post( wpf_product.ajax_url, data, function( result ) {              
            if ( true === result.status ) {
              if ( status.compare ) {
                $el.removeClass( 'wpf-hidden' );
              } else {
                $el.addClass( 'wpf-hidden' );
              }
            } 
          } );                   
        } );
      }
    } );
    */
  });
})( jQuery );