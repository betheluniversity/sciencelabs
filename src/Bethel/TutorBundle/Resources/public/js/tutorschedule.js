$(document).ready(function() {

    // page is now ready, initialize the calendar...

    $('#calendar').fullCalendar({
        header: {
            left: 'agendaWeek, month',
            center: 'title',
            right: 'today prev,next'
        },
        timezone: 'local',
        events: function(start, end, timezone, callback) {
            $.ajax({
                url: Routing.generate('bethel_session_tutors_get',{
                    // We only grab the sessions that are between start and end
                    // these are parameters passed to us by fullCalendar indicating
                    // what the user can currently see.
                    start: start.format('DD-MM-YYYY'),
                    end: end.format('DD-MM-YYYY')
                }),
                dataType: 'json',
                success: function(msg) {
                    var events = [];
                    for(key in msg.tutorSessions) {
                        var session = msg.tutorSessions[key];
                        var tutorName = session.tutor.first_name + ' ' + session.tutor.last_name;
                        // Moment stuff to take create moments from the JSON data
                        // and combine the start time on the tutor sessions with the
                        // date on the session.
                        var date = $.fullCalendar.moment(session.session.date, 'YYYY-MM-DDTHH:mm:ssZ');
                        if(session.sched_time_in && session.sched_time_out) {
                            var startTime = $.fullCalendar.moment(session.sched_time_in, 'YYYY-MM-DDTHH:mm:ssZ');
                            var endTime = $.fullCalendar.moment(session.sched_time_out, 'YYYY-MM-DDTHH:mm:ssZ');
                            var startMoment = date.clone();
                            startMoment
                                .hour(startTime.hour())
                                .minute(startTime.minute());
                            var endMoment = date.clone();
                            endMoment
                                .hour(endTime.hour())
                                .minute(endTime.minute());
                        } else {
                            var startTime = null;
                            var endTime = null;
                        }

                        if(startTime && endTime) {
                            // Color sessions which allow substitutes differently
                            if(session.substitutable) {
                                events.push({
                                    title: tutorName,
                                    start: startMoment,
                                    end: endMoment,
                                    className: ['fc-event','fc-event-sub'],
                                    url: Routing.generate('tutor_session', {
                                        id: session.id
                                    })
                                });
                            } else {
                                events.push({
                                    title: tutorName,
                                    start: startMoment,
                                    end: endMoment,
                                    className: ['fc-event','fc-event-no-sub'],
                                    url: Routing.generate('tutor_session', {
                                        id: session.id
                                    })
                                });
                            }
                        } else {
                            // If there is not a scheduled time in and out, then the tutor was
                            // unscheduled for this session. We need to highlight those sessions.
                            events.push({
                                title: tutorName,
                                start: date,
                                allDay: true,
                                end: endMoment,
                                color: '#DA1736',
                                className: ['fc-event','fc-event-unscheduled'],
                                url: Routing.generate('tutor_session', {
                                    id: session.id
                                })
                            });
                        }
                    }
                    callback(events);
                }
            });
        }
    });

    $('#show-sub-events').click(function() {
        $('.fc-event-unscheduled').toggle();
        $('.fc-event-no-sub').toggle();
        $('.fc-event-sub').show();
    });

    $('#show-no-sub-events').click(function() {
        $('.fc-event-unscheduled').toggle();
        $('.fc-event-no-sub').show();
        $('.fc-event-sub').toggle();
    });

    $('#show-unscheduled-events').click(function() {
        $('.fc-event-unscheduled').show();
        $('.fc-event-no-sub').toggle();
        $('.fc-event-sub').toggle();
    });

});