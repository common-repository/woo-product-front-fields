(function( $ ) {  
  
  $(document).ready( function() {              
      if ( $( '.wpf-color' ).size() ) {
        $( '.wpf-color' ).each(function() {
            let $textInput = $(this);
            $textInput.wpColorPicker({
                change: function( target, ui ) {
                    $textInput.trigger('change');
                }
            });
        });
      }
  });

})( jQuery );