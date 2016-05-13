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