$(document).ready(function() {

    $.extend($.fn.dataTableExt.oStdClasses, {
        // give some foundation classes to our controls
        sWrapper: 'large-12 columns ',
        sLength: 'large-4 columns ',
        sFilter: 'large-12 columns ',
        sInfo: 'large-6 left ',
        sPaging: 'right ',
        sPageButton: 'button small',
        sPageButtonDisabled: 'disabled',
        sPageButtonActive: 'success'
    });

    var oTable = $('#userTable').dataTable({
        "columnDefs": [
            {
                "visible": false,
                "searchable": false,
                "targets": 0
            },
            {
                "className": "text-center",
                "orderable": false,
                "targets": 5
            },
            {
                "className": "text-center",
                "orderable": false,
                "targets": 6
            }
        ],
        "order": [[0, 'desc'],[1, 'asc'],[2, 'asc']],
        "bPaginate": false,
        "oLanguage": {
            "sSearch": "Search:"
        }
    });
});

$(".deactivateUsers").click(function() {
    $("td input:checked").each(
        function() {
            userObj = warnBeforeDelete($(this).val());
        }
    );
});

function deleteUser(userId, name) {
    $.ajax({
        url: Routing.generate('bethel_user_delete',{
            id: userId
        }),
        dataType: 'json',
        method: 'DELETE',
        success: function(msg) {
        },
        error: function(msg) {
            createAlert('warning', 'Deactivation failed')
        },
        complete: function() {
            console.log($("td input:checked").last().val());
            if($("td input:checked").last().val() == userId) {
                createAlert('success', 'Selected user(s) successfully deactivated');
                window.setTimeout(function() {
                    window.location.reload(true);
                },1000);
            }
        }
    });
}

function warnBeforeDelete(userId) {
    $.ajax({
        url: Routing.generate('bethel_user_get',{
            id: userId
        }),
        dataType: 'json',
        success: function(msg) {
            result = window.confirm("Do you want to deactivate " + msg.first_name + ' ' + msg.last_name + ' (' + msg.username + ')?');
            if(result) {
                deleteUser(userId, msg.first_name + ' ' + msg.last_name + ' (' + msg.username + ')');
            }
        }
    });
}