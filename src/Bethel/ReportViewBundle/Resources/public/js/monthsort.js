$(document).ready(function() {

    $.extend($.fn.dataTableExt.oStdClasses, {
        // give some foundation classes to our controls
        sWrapper: 'large-12 columns ',
        sLength: 'large-4 columns ',
        sFilter: 'large-12 columns ',
        sInfo: 'large-4 left ',
        sPaging: 'right ',
        sPageButton: 'button small',
        sPageButtonDisabled: 'disabled',
        sPageButtonActive: 'success'
    });

    var sessTable = $('#monthlySessionTable').dataTable({
        "order": [[0, 'asc'], [1, 'asc'], [2, 'asc'], [3, 'asc'], [4, 'desc']],
        "bPaginate": false,
        "oLanguage": {
            "sSearch": "Search:"
        }
    });

    var schedTable = $('#scheduleTable').dataTable({
        "order": [[1, 'asc'], [0, 'asc'], [2, 'asc'], [3, 'desc'], [4, 'desc']],
        "bPaginate": false,
        "oLanguage": {
            "sSearch": "Search:"
        }
    });
});