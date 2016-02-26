$(document).ready(function() {
    initDatePickers();

    function getSemesterByTermAndYear() {
        // Fetching the Semester entity from the API based on the year and
        // term selected in the transition form
        var jqXHR = $.ajax({
            headers: {
                "Accept": "application/json",
                "Content-type": "application/json"
            },
            url: Routing.generate('bethel_semester_year_get', {
                year: $("#bethel_entitybundle_semester_year").val(),
                term: $("#bethel_entitybundle_semester_term").val()
            })
        });
        jqXHR.always(changeDates);
    }

    function changeDates(msg) {
        // If we're passed a response, we fill the dates into the form
        // otherwise, we set the values of the inputs to an empty string.
        var startDate = '';
        var endDate = '';

        if(msg.semester) {
            startDate = moment(msg.semester.start_date).format('MM/DD/YYYY');
            endDate = moment(msg.semester.end_date).format('MM/DD/YYYY');
        }
        $("#bethel_entitybundle_semester_startDate").val(startDate);
        $("#bethel_entitybundle_semester_endDate").val(endDate);
    }

    // We make an initial call to the API for the currently selected date
    getSemesterByTermAndYear();

    // ... and then subsequent calls when the term or year values change
    $("#bethel_entitybundle_semester_term").change(function() {
        getSemesterByTermAndYear()
    });
    $("#bethel_entitybundle_semester_year").change(function() {
        getSemesterByTermAndYear()
    });
});

function initDatePickers() {
    var startDatePicker = new Pikaday({
        field: document.getElementById('bethel_entitybundle_semester_startDate'),
        format: 'MM/DD/YYYY'
    });

    var endDatePicker = new Pikaday({
        field: document.getElementById('bethel_entitybundle_semester_endDate'),
        format: 'MM/DD/YYYY'
    });
}