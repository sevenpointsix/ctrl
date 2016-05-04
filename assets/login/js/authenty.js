(function($) {
  
	// sign in form 1
	$('#signIn_1x').click(function (e) {  
	   
			var username = $.trim($('#un_1').val());
	    var password = $.trim($('#pw_1').val());

	    if ( username === '' || password === '' ) {
        $('#form_1 .fa-user').removeClass('success').addClass('fail');
				$('#form_1').addClass('fail');
	    } else {
	   		$('#form_1 .fa-user').removeClass('fail').addClass('success');
				$('#form_1').removeClass('fail').removeClass('animated');
				return false;
	    }
	});

})(jQuery);


