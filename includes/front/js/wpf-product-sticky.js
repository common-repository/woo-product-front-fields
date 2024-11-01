(function( $ ) {      
    $(document).ready(function() {        
        $( '.storefront-sticky-add-to-cart__content-price' ).html( 
            $( '.entry-summary .price' ).html()
        );
        $( '.storefront-sticky-add-to-cart__content-button' ).click(function() {
            $( 'form button[type="submit"][name="add-to-cart"] ')
                .trigger( 'click' );
            return false;
        });
    });
})( jQuery );