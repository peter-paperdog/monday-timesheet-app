import './bootstrap';

import Alpine from 'alpinejs';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'timeGridWeek',
            slotDuration: '00:30:00',
            firstDay: 1, // 0 = Sunday, 1 = Monday
            allDaySlot: false, // Disables the all-day row
            slotMinTime: '08:00:00',
            slotMaxTime: '22:00:00',
            events: '/timesheet-events' // Adjust this endpoint based on your Laravel API
        });

        calendar.render();
    }
});

window.Alpine = Alpine;

Alpine.start();
