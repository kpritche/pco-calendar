jQuery(document).ready(function($) {
    const $container = $('#pco-agenda-container');
    if (!$container.length) return;

    const $filters = $('#pco-filters');
    const $eventList = $('#pco-event-list');
    
    let allEvents = [];
    let allCalendars = [];
    let activeCalendarIds = pcoSettings.defaultCalendars || [];

    fetchData();

    function fetchData() {
        $.ajax({
            url: pcoSettings.ajaxUrl,
            type: 'POST',
            data: { action: 'pco_fetch_events' },
            success: function(response) {
                if (response.success) {
                    allCalendars = response.data.calendars;

                    allEvents = (response.data.events.data || []).map(instance => {
                        const included = response.data.events.included || [];
                        const eventId = instance.relationships.event.data.id;
                        const eventDetails = included.find(inc => inc.type === 'Event' && inc.id === eventId);
                        
                        // Smart Calendar Mapping: Try Instance first, then Event
                        let calendarId = instance.relationships.calendar ? instance.relationships.calendar.data.id : null;
                        if (!calendarId && eventDetails && eventDetails.relationships.calendar) {
                            calendarId = eventDetails.relationships.calendar.data.id;
                        }

                        // Find calendar name in the included data OR in the allCalendars list
                        let calendarDetails = included.find(inc => inc.type === 'Calendar' && inc.id === calendarId);
                        let calendarName = 'Unknown Calendar';

                        if (calendarDetails) {
                            calendarName = calendarDetails.attributes.name;
                        } else if (allCalendars && allCalendars.length > 0) {
                            // Fallback: check the main calendars list we already fetched
                            const mainCal = allCalendars.find(c => c.id === calendarId);
                            if (mainCal) calendarName = mainCal.attributes.name;
                        }
                        
                        return {
                            ...instance,
                            event_name: eventDetails ? eventDetails.attributes.name : 'Unknown Event',
                            calendar_id: calendarId,
                            calendar_name: calendarName,
                            starts_at: new Date(instance.attributes.starts_at)
                        };
                    });
                    
                    renderFilters();
                    renderEvents();
                } else {
                    $eventList.html('<p class="pco-error">Error: ' + response.data + '</p>');
                }
            },
            error: function() {
                $eventList.html('<p class="pco-error">Could not connect to the server.</p>');
            }
        });
    }

    function renderFilters() {
        $filters.empty().toggle(allCalendars.length > 0);
        allCalendars.forEach(calendar => {
            const isActive = activeCalendarIds.includes(calendar.id);
            $('<div class="pco-filter-chip"></div>')
                .text(calendar.attributes.name).attr('data-id', calendar.id).toggleClass('active', isActive)
                .on('click', function() {
                    const id = $(this).attr('data-id');
                    $(this).toggleClass('active');
                    activeCalendarIds = activeCalendarIds.includes(id) ? activeCalendarIds.filter(i => i !== id) : [...activeCalendarIds, id];
                    renderEvents();
                }).appendTo($filters);
        });
    }

    function renderEvents() {
        $eventList.empty();
        const filtered = allEvents.filter(e => activeCalendarIds.length === 0 || activeCalendarIds.includes(e.calendar_id));
        if (filtered.length === 0) return $eventList.append('<div class="pco-no-results">No upcoming events found.</div>');
        filtered.forEach(event => {
            const d = event.starts_at;
            $(`
                <div class="pco-event-card">
                    <div class="pco-event-date"><span class="pco-event-day">${d.getDate()}</span><span class="pco-event-month">${d.toLocaleString('default',{month:'short'})}</span></div>
                    <div class="pco-event-content">
                        <div class="pco-event-title">${event.event_name}</div>
                        <div class="pco-event-meta"><span class="pco-event-time">${d.toLocaleString('en-US',{hour:'numeric',minute:'2-digit',hour12:true})}</span><span class="pco-event-calendar-name">${event.calendar_name}</span></div>
                    </div>
                </div>
            `).appendTo($eventList);
        });
    }
});
