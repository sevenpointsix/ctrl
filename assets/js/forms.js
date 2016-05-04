

// wait for the DOM to be loaded 
$(document).ready(function() { 
    
  var options = { 
        dataType: 'json',
        success: function(responseText, statusText, xhr, $form)  { 
          redirect = responseText.redirect;  
          // If we're trying to reload the page and drop to an anchor, the standard approach doesn't work.
          // See extensive notes on the Rosewood site about this.
          
          if ((redirect.split('#')[0] == window.location.pathname || redirect.split('#')[0] == window.location.href.split("#")[0]) && redirect.split('#')[1]) {
                        
            // We're redirecting to a hash link on the current page:
            hash = redirect.split('#')[1];            

           
            // Bit of a bodge to handle existing querystrings...
            if (window.location.search) {
              window.location.href = window.location.pathname + window.location.search + "&r=" + Math.round(Math.random()*100000) + "#" + hash;
            }
            else {
              window.location.href = window.location.pathname + "?r=" + Math.round(Math.random()*100000) + "#" + hash;  
            }
                        
          }
          else {
            console.log('No hash: '+ window.location.href);
            window.location.replace(redirect);
          }
        },
        error: function(jqXHR, textStatus, errorThrown ) { 

          if (jqXHR.status == 422) { // This is the status code Laravel returns when validation fails:
            response = $.parseJSON(jqXHR.responseText);           
            var error_messages = new Array();         
            $.each(response, function(field, error) {              
            // Field [name=field] now has the error error           
              $('[name="'+field+'"]').parents('div.form-group').addClass('has-error');                          
              error_messages.push(error);
            });         

            errors_div = $('#errors');
            
            // Clone the error div (unless we already have done!) and display it:
            error = $('#error-clone');

            if (error.length == 0) {
              error = errors_div.find('.template').clone().removeClass('hidden').removeClass('template').attr('id','error-clone');  
            }

            error.find('span.message').html(error_messages.join('<br>')); 
            
            error.appendTo(errors_div);
                        
            $("html, body").animate({
              scrollTop: errors_div.offset().top - 100
            }, 'fast');
            
          }
          else {
            // Major error; it's possible to update Handler.php to provide a nicer error in error.exception
            response = $.parseJSON(jqXHR.responseText);
            if (response.error.exception) {
              console.log(errorThrown+': '+response.error.exception);
            }
            else {
              console.log('Error');
            }
          }
      }
    }; 
 
    $('form.ajax').ajaxForm(options);
    
}); 