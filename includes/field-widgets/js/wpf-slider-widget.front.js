(function( $ ) {  
  
  $(document).ready( function() {             
      if ( $( '.wpf-slider' ).size() ) {        
        let settingsExist = 'undefined' != typeof wpf_slider_widget;        
        $( '.wpf-slider' ).each( function() {  
          let $textInput = $(this);
          let $slider = $( "<div></div>" ).insertAfter( $textInput );        
          $slider.slider({
            range: 'min', 
            value: $textInput.val(),
            step: settingsExist ? wpf_slider_widget.settings.step : 1,
            min:  settingsExist ? wpf_slider_widget.settings.min : 0,
            max: settingsExist ? wpf_slider_widget.settings.max : 100,
            stop: function( event, ui ) {              
              $textInput.val( ui.value ).trigger('change');
            }
          });
          $textInput.change( function () {            
            $slider.slider( 'value', parseInt( this.value ) );
          } );
        });                
      }
  });

})( jQuery );