import './bootstrap';

import Alpine from 'alpinejs';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var selectedUserId = calendarEl.dataset.userId; // Get user ID from data attribute

        var calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay,listWeek' // Added daily & list view
            },
            slotDuration: '00:15:00',
            allDaySlot: true,
            firstDay: 1, // Start the week on Monday
            slotMinTime: '06:00:00', // Start day at 6 AM
            slotMaxTime: '23:59:59', // End day at midnight
            events: function(fetchInfo, successCallback, failureCallback) {
                const userId = selectedUserId ? selectedUserId : 'default'; // Handle undefined case

                fetch(`/timesheet-events?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}&user_id=${userId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to fetch events');
                        }
                        return response.json();
                    })
                    .then(data => successCallback(data))
                    .catch(error => {
                        console.error("Error loading events:", error);
                        failureCallback(error);
                    });
            }
        });

        calendar.render();
    }
});

window.Alpine = Alpine;

Alpine.start();
