$(document).ready(function() {

    $.extend($.fn.dataTableExt.oStdClasses, {
        // give some foundation classes to our controls
        sWrapper: '',
        sLength: 'large-4 columns ',
        sFilter: 'large-5 columns ',
        sInfo: 'large-4 left ',
        sPaging: 'right ',
        sPageButton: 'button small',
        sPageButtonDisabled: 'disabled',
        sPageButtonActive: 'success'
    });

    var sessionTable = $('#sessionTable').dataTable({
        "bPaginate": false,
        "bFilter": false,
        "bInfo": false
    } );

    var studentTable = $('#studentTable').dataTable({
        "bPaginate": false,
        "bFilter": false,
        "bInfo": false
    } );
});