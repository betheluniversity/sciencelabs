
// Initialize Foundation JS components
$(document).foundation({
    alert: {
        animation_speed: 500,
        animation: 'fadeOut'
    }
});

$(document).ready(function() {
    // Replacing the HTML5 time input with dropdowns for browsers that
    // do not support it.
    if(!timeInputSupport()) {
        var $visibleTimeFields = $('input[type="time"]:visible');
        $visibleTimeFields.hide();
        $visibleTimeFields.each(function() {
            $(this).after('<div class="timepicker"></div>');

            var hourSelect = $('<select class="hourSelect large-4 columns" />').css('-moz-appearance','none');
            var minuteSelect = $('<select class="minuteSelect large-4 columns" />').css('-moz-appearance','none');
            for(var i = 0; i < 12; i++) {
                hourSelect.append("<option>" + (i+1) + "</option>");
            }
            for(i = 0; i < 60; i+=5) {
                minuteSelect.append(
                    "<option>" +
                    (i < 10 ? "0" + i : i) +
                    "</option>"
                );
            }
            var meridiemSelect = $('<select class="meridiemSelect large-4 columns"><option>AM</option><option>PM</option></select>').css('-moz-appearance','none');

            var timeSplit = $(this).val().split(':');
            var twelveHour = twentyFourToTwelve(timeSplit[0],timeSplit[1]);

            $(this).siblings('.timepicker')
                .append(hourSelect.val(twelveHour.hour))
                .append(minuteSelect.val(twelveHour.min))
                .append(meridiemSelect.val(twelveHour.meridiem));
        });

        $('.timepicker').children('select').change(function() {
            $(this).parent().timeChange();
        });
    }
});

(function( $ ){
    $.fn.timeChange = function() {
        var hour = $(this).children('.hourSelect').val();
        var min = $(this).children('.minuteSelect').val();
        var meridiem = $(this).children('.meridiemSelect').val();
        var time = twelveToTwentyFour(hour,min,meridiem);

        $(this).siblings('input[type="time"]').val(time);
    };
})( jQuery );

function twelveToTwentyFour(hour,min,meridiem) {
    if(hour != 12) {
        hour = hour < 10 ? '0' + hour : hour;
        var time = meridiem == 'AM' ? hour + ':' + min : (parseInt(hour) + 12) + ':' + min;
    } else {
        var time = meridiem == 'PM' ? hour + ':' + min : '00:' + min;
    }

    return time;
}

function twentyFourToTwelve(hour,min) {
    var time = {};

    if(hour < 13 && hour > 0) {
        time.meridiem = 'AM';
        time.hour = hour;
    } else if(hour >= 13) {
        time.hour = hour - 12;
        time.meridiem = 'PM';
    } else if (hour == 0) {
        time.meridiem = 'AM';
        time.hour = 12;
    }

    time.min = min;

    return time;
}

function timeInputSupport() {
    try {
        var input = document.createElement("input");

        input.type = "time";

        if (input.type === "time") {
            return true;
        } else {
            return false;
        }
    } catch(e) {
        return false;
    }
}

// JavaScript function to create a Foundation alert box
function createAlert(type,msg) {
    var alertNodeString = '<div data-alert class="alert-box ' + type + ' radius">' +
    '<strong>' + capitaliseFirstLetter(type) + '</strong>: ' + msg +
    '<a href="#" class="close">&times;</a></div>';
    var alertNode = $.parseHTML(alertNodeString);
    $(alertNode).click(function() {
        $(this).css({
            top: "0px"
        }).animate({
            top: "-39px",
            opacity: "toggle"
        }, 500);
    });
    $("#alert-container").append(alertNode);
}

function capitaliseFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

