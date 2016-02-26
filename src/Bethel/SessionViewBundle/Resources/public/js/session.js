// This file was broken. But I also needed to add the ability to sessions to any date (past/future).

$(document).ready(function() {
//    // We're pulling in the active semester so we can get start and end dates
//    var jqXHR = $.ajax({
//        headers: {
//            "Accept": "application/json",
//            "Content-type": "application/json"
//        },
//        url: Routing.generate('bethel_semester_active_get')
//    });
//    jqXHR.done(semesterFetched);
    var msg = '';
    semesterFetched(msg);
});

function semesterFetched(msg) {

    // only allow future dates
    //var now = new Date();
    //var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    //var semesterStart = moment(msg.semester.start_date).toDate();
    //var firstViable = today > semesterStart ? today : semesterStart;
    //var lastViable = moment(msg.semester.end_date).toDate();

    //var firstViable = moment(msg.semester.start_date).toDate();
    //var lastViable = moment(msg.semester.end_date).toDate();

    //newDatePicker(firstViable, lastViable);
    newDatePicker();
}

function newDatePicker() {
    var startDatePicker = new Pikaday({
        field: document.getElementById('bethel_entitybundle_session_date'),
        format: 'MM/DD/YYYY'
        // pull in the minimum and maximum date for the current semester
        //minDate: minDate,
        //maxDate: maxDate
    });

    // I don't like declaring separate listeners for the same event
    // but I can't avoid it without significant changes here.
    $("#bethel_entitybundle_session_schedule").change(function() {
        startDatePicker.destroy();
    });
}