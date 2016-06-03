$(document).ready(function() { 

  $('form.ajax').on('submit',function(e) {
    
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
    
    e.preventDefault(e);

    $.ajax({
      type:"POST",
      url:$(this).attr('action'),
      data:$(this).serialize(),
      dataType: 'json',
      success: function(responseText, statusText, xhr, $form)  { 
        redirect = responseText.redirect;  
        window.location.replace(redirect);
        // What's the best way to add a .notify here?
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

          $('a[role=tab]:first').tab('show') // Select first tab

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
    });
  });
});