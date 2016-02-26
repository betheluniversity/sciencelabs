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

    var oTable = $('#scheduleTable').dataTable({
        "columnDefs": [
            {
                // 0 is our hidden DOW integer column
                "visible": false,
                "searchable": false,
                "targets": 0
            },
            {
                "className": "text-center",
                "orderable": false,
                "targets": 7
            }
        ],
        "order": [[0, 'asc'],[3, 'desc']],
        "bPaginate": false,
        "oLanguage": {
            "sSearch": "Search:"
        }
    } );
});
