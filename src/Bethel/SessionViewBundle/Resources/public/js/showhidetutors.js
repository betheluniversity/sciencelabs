$(document).ready(function() {
    $(".show-tutors, .show-courses").click(function() {
        $(this).next().toggle();
        if($(this).text() !== 'Show')
            $(this).text('Show');
        else
            $(this).text('Hide');
    });
});