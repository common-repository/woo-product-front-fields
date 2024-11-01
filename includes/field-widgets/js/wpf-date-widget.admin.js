(function( $ ) {  
  
  $(document).ready( function() { 
      let settingsExist = 'undefined' != typeof wpf_slider_widget,
        params = {};
      if ( settingsExist ) {
        params['dateFormat'] = 'dd-mm-yy';
      }

      if ( 'WPF_Date_Widget' === $( '.wpf-edit-form #widget' ).val() ) {        
        $( '#field_default_text ').datepicker( params );
      }

      if ( $( '.wpf-field-value.wpf-date' ).size() ) {
        $( '.wpf-field-value.wpf-date' ).datepicker( params );
      }
  });

})( jQuery );