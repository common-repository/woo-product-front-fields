(function( $ ) {  
  
  $(document).ready( function() {       
      if ( 'WPF_Color_Widget' === $( '.wpf-edit-form #widget' ).val() ) {        
        $( '#field_default_text ').wpColorPicker();
      }      
      if ( $( '.wpf-field-value.wpf-color' ).size() ) {
        $( '.wpf-field-value.wpf-color' ).wpColorPicker();
      }
  });

})( jQuery );