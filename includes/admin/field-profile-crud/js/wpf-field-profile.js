(function( $ ) {  

    $(document).ready(function() {               
        var $product_screen = $( '.edit-php.post-type-product' ),
        $title_action = $product_screen.find( '.page-title-action:first' );  
        if ( $title_action.length ) {                    
            $.each( wpf_field_profiles.items, function( key, item ) {            
                $title_action.after('<a href="' + item.url + '" class="page-title-action">Add New ' + item.name + '</a>');
            } );    
        }                
        
        $fieldsSortable = $( '#wpf-field-slider' );
        if ( $fieldsSortable.length ) {                                    
            $fieldsSortable.sortable( {
                stop: function ( event, ui ) {
                    refreshHiddenFids();
                }
            } );        

            let fieldsSelect = $( '#wpf_field_profile_form #fields' ),        
                fids = fieldsSelect.attr( 'data-fids' ),
                $fidsHidden = $( '#fids' );
            if ( '' !== fids && undefined != fids ) {
                fids = fids.split(',');
                for (let fid of fids ) {                    
                    let title = fieldsSelect.find( `option[value="${fid}"]` ).text();
                    addFieldToSortable( fid, title );
                }
                refreshHiddenFids();
                refreshDisabledOptions();
                $fieldsSortable.sortable( 'refresh' );
            }
            
            $( '#wpf_field_profile_form #wpf-add-field' ).click( function() {            
                let fieldId = fieldsSelect.val();
                if ( '' !== fieldId ) {
                    let title = fieldsSelect.find( 'option:selected' ).text();
                    addFieldToSortable( fieldId, title );                                               
                    fieldsSelect.val('');
                    refreshHiddenFids();
                    refreshDisabledOptions();
                    $fieldsSortable.sortable( 'refresh' );
                }
                return false;
            } );
        }
    });

    function addFieldToSortable( id, title ) {
        let $fieldsSortable = $( '#wpf-field-slider' ),
            $item = $( `<div class="item" value="${id}"><span class="title">${title}</span><a class="remove" href="#">x</a></div>` ).appendTo( $fieldsSortable );
            $item.find( '.remove' ).click( function() {
                $(this).closest( '.item' ).remove();
                refreshHiddenFids();
                refreshDisabledOptions();
            } );                                
    }

    function addHiddenFid( value ) {
        let $fidsHidden = $( '#fids' ),
            fids = $fidsHidden.val();
        if ( '' === fids ) {
            $fidsHidden.val( value );
        } else {
            $fidsHidden.val( fids + ',' + value );
        }
    }

    function refreshHiddenFids() {
        let $fidsHidden = $( '#fids' ),
            $fieldsSortableItems = $( '#wpf-field-slider .item' );
        let values = [];
        $fieldsSortableItems.each( function() {
            values.push( $(this).attr( 'value' ) );
        } );
        $fidsHidden.val( values.join(',') );        
    }

    function refreshDisabledOptions() {
        let fieldsSelect = $( '#wpf_field_profile_form #fields' ),
            fids = $( '#fids' ).val().split(',');
        fieldsSelect.find('option').prop( 'disabled', false );
        fieldsSelect.find('option').each( function() {
            let $option = $(this);
            if ( $.inArray( $option.val(), fids) !== -1 ) {
                $option.prop( 'disabled', true );
            }
        } );
    }

})( jQuery );