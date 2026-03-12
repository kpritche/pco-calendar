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
                        
                        // Check visibility: skip if not visible on Church Center
                        if (eventDetails && eventDetails.attributes.visible_in_church_center === false) {
                            return null;
                        }

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

                        // Extract Tags and check for Standout status
                        let isStandout = false;
                        if (eventDetails && eventDetails.relationships.tags) {
                            const tagIds = eventDetails.relationships.tags.data.map(t => t.id);
                            isStandout = tagIds.some(id => (pcoSettings.standoutTags || []).includes(id));
                        }

                        // Extract Rooms from Resource Bookings
                        let roomNames = [];
                        if (instance.relationships.resource_bookings) {
                            const bookingIds = instance.relationships.resource_bookings.data.map(r => r.id);
                            bookingIds.forEach(bookingId => {
                                const booking = included.find(inc => inc.type === 'ResourceBooking' && inc.id === bookingId);
                                if (booking && booking.relationships.resource) {
                                    const resourceId = booking.relationships.resource.data.id;
                                    const resource = included.find(inc => inc.type === 'Resource' && inc.id === resourceId && inc.attributes.kind === 'Room');
                                    if (resource) {
                                        roomNames.push(resource.attributes.name);
                                    }
                                }
                            });
                        }
                        const roomString = roomNames.join(', ');
                        
                        return {
                            ...instance,
                            event_name: eventDetails ? eventDetails.attributes.name : 'Unknown Event',
                            event_summary: eventDetails ? eventDetails.attributes.summary : '',
                            event_location: roomString || (eventDetails ? eventDetails.attributes.location : ''),
                            calendar_id: calendarId,
                            calendar_name: calendarName,
                            is_standout: isStandout,
                            starts_at: new Date(instance.attributes.starts_at)
                        };
                    }).filter(event => event !== null);
                    
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
            const standoutClass = event.is_standout ? 'pco-standout' : '';
            const hasSummary = event.event_summary && event.event_summary.trim() !== '';
            const hasLocation = event.event_location && event.event_location.trim() !== '';
            const hasDetails = hasSummary; // Only summary expands now, location is always visible
            
            const detailsPrompt = hasDetails ? '<div class="pco-details-prompt">Click for details</div>' : '';
            const locationHtml = hasLocation ? `<span class="pco-event-room">@ ${event.event_location}</span>` : '';
            const summaryHtml = hasSummary ? `<div class="pco-event-description">${event.event_summary}</div>` : '';
            
            const $card = $(`
                <div class="pco-event-card ${standoutClass} ${hasDetails ? 'pco-has-summary' : ''}">
                    <div class="pco-event-date"><span class="pco-event-day">${d.getDate()}</span><span class="pco-event-month">${d.toLocaleString('default',{month:'short'})}</span></div>
                    <div class="pco-event-content">
                        <div class="pco-event-title">${event.event_name}</div>
                        <div class="pco-event-meta">
                            <span class="pco-event-time">${d.toLocaleString('en-US',{hour:'numeric',minute:'2-digit',hour12:true})}</span>
                            ${locationHtml}
                            <span class="pco-event-calendar-name">${event.calendar_name}</span>
                        </div>
                        ${detailsPrompt}
                        <div class="pco-event-summary" style="display:none;">
                            ${summaryHtml}
                        </div>
                    </div>
                </div>
            `);
            
            if (hasDetails) {
                $card.on('click', function() {
                    $(this).toggleClass('expanded');
                    $(this).find('.pco-event-summary').slideToggle(200);
                    const promptText = $(this).hasClass('expanded') ? 'Click to close' : 'Click for details';
                    $(this).find('.pco-details-prompt').text(promptText);
                });
            } else {
                $card.css('cursor', 'default');
            }

            $card.appendTo($eventList);
        });
    }
});
