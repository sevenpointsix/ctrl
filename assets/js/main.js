// Enable tooltips
/* Not used
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
});
*/
/* TBC. Might use this lightbox in various places, such as opening images directly from a table row: http://ashleydw.github.io/lightbox/ */
$(document).delegate('*[data-toggle="lightbox"]', 'click', function(event) {
    event.preventDefault();
    $(this).ekkoLightbox();
}); 

// Set up some click events on the row buttons; now used on edit and list pages. "delete" only for now. Also init tooltips.
function init_row_buttons() {    	
    // Add the "delete" option
   	$('div.row-buttons').on('click','a.delete-item',function() {
   		var that = this; // Nicked this idea from Datatables; see below. Preserve $this for the bootbox callback
   		bootbox.confirm("Are you sure you want to delete this item?", function(result) {
   			if (result) {
   				var delete_link = $(that).attr('rel');
		   		var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
				$.ajax({
				    url: delete_link,
				    type: 'POST',
				    data: {_token: CSRF_TOKEN },
				    dataType: 'JSON',
				    success: function (data) {
				       $.notify({
							icon: 'fa fa-check-square-o fa-fw',				
							message: 'Item deleted',					
						},{
							type: "success",
							newest_on_top: true,
							delay: 2500,					
						});
						table.draw(); // Redraw the table (to reflect fact that a row has been removed/deleted)
				    }
				});
   			}	
   			else {	   				
   				// $('.dropdown.open .dropdown-toggle').dropdown('toggle');
   				$(that).parents('ul.dropdown-menu').dropdown('toggle'); // Close the "delete" dropdown
   			}		  
		});
		return false;
   	});
	// Tooltips
	$('[data-toggle="tooltip"]').tooltip();
		
}
