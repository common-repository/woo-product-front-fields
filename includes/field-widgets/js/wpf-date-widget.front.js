(function( $ ) {  
  
  $(document).ready( function() {             
      let settingsExist = 'undefined' != typeof wpf_slider_widget,
        params = {};
      if ( settingsExist && undefined != wpf_slider_widget.settings.dateFormat ) {
        params['dateFormat'] = wpf_slider_widget.settings.dateFormat;
      }
      if ( $( '.wpf-date' ).size() ) {
        $( '.wpf-date' ).datepicker( params );
      }
  });

})( jQuery );