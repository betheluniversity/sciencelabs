$(document).ready(function() {

    $.extend($.fn.dataTableExt.oStdClasses, {
        // give some foundation classes to our controls
        sWrapper: 'large-12 columns ',
        sLength: 'large-4 columns ',
        sFilter: 'large-8 columns ',
        sInfo: 'large-4 left ',
        sPaging: 'right ',
        sPageButton: 'button small',
        sPageButtonDisabled: 'disabled',
        sPageButtonActive: 'success'
    });

    var oTable = $('#userTable').dataTable({
        "columnDefs": [
            {
                "className": "text-center",
                "orderable": false,
                "targets": 4
            }
        ],
        "oLanguage": {
            "sLengthMenu": "Limit: _MENU_"
        },
        "aLengthMenu": [
            [50,100,-1],
            [50,100,"All"]
        ],
        pageLength: 100
    });

    /* Add a select menu for each TD element in the table footer */
    // userRoleFilter
    $("#userFilter td").each(function(i) {
        if(this.id == 'userRoleFilter') {
            $('select', this).change( function () {
                oTable.fnFilter( $(this).val(), i );
            });
        }
    });
});