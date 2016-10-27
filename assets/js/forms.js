$(document).ready(function() { 

  $('form.ajax').on('submit',function(e) {
    
    var submit_button = $(this).find('button[type=submit][data-loading-text]');
    if (submit_button.length > 0) {
      var submit_button_original_text = submit_button.html();
      submit_button.html(submit_button.attr('data-loading-text'));  
    }

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

          // We now use Handlebars here, to load the error HTML.
          var errors = error_messages.join('<br>');
          var message_template = Handlebars.compile('<div class="alert alert-{{type}} alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="message">{{{message}}}</span></div>');
          var message_html = message_template({type: "danger", message: errors});
          $('#messages').html(message_html);

          $('a[role=tab]:first').tab('show') // Select first tab

          $("html, body").animate({
            scrollTop: $('#messages').offset().top - 100
          }, 'fast');

          if (submit_button.length > 0) {            
            submit_button.html(submit_button_original_text);  
          }
          
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
          if (submit_button.length > 0) {            
            submit_button.html(submit_button_original_text);  
          }
        }
      }     
    });
  });
});