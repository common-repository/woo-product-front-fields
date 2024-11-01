(function( $ ) {  
  
  $(document).ready( function() {       
      if ( 'WPF_Slider_Widget' === $( '.wpf-edit-form #widget' ).val() ) {        
        let settingsExist = 'undefined' != typeof wpf_slider_widget,        
            $textInput = $( '.wpf-edit-form #field_default_text' ).attr( 'type', 'number' ),
            $slider = $( "<div></div>" ).insertAfter( '.wpf-edit-form #field_default_text' );
        $slider.slider({
          range: 'min', 
          value: $textInput.val(),
          step: settingsExist ? wpf_slider_widget.settings.step : 1,
          min:  settingsExist ? wpf_slider_widget.settings.min : 0,
          max: settingsExist ? wpf_slider_widget.settings.max : 100,
          slide: function( event, ui ) {
              $textInput.val( ui.value );
          }
        });
        $textInput.change( function () {            
          $slider.slider( 'value', parseInt( this.value ) );
        } );        
      }      
  });

})( jQuery );