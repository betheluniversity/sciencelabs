
$(document).ready(function() {
    // We're pulling in the active semester so we can get start and end dates
    var jqXHR = $.ajax({
        headers: {
            "Accept": "application/json",
            "Content-type": "application/json"
        },
        url: Routing.generate('bethel_semester_view_get')
    });
    jqXHR.done(populateActive);
});

function populateActive(msg) {
    $('#form_semester').prepend('<option value="">==========</option>');
    $('#form_semester').prepend('<option value="" selected="selected">' + msg.semester.term + ' ' + msg.semester.year + '</option>');
}