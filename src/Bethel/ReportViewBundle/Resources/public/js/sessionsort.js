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

    $('.sessionTable').each(function() {
        $(this).dataTable({
            "columnDefs": [
                {
                    "className": "text-center",
                    "orderable": false,
                    "targets": [5]
                }
            ],
            "order": [[0, 'asc'],[1, 'asc']],
            "bPaginate": false,
            "bFilter": false,
            "bInfo": false
        });
    });
});