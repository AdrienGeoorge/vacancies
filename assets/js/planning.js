import axios from "axios"
import Routing from "fos-router"

import {Calendar} from '@fullcalendar/core'
import allLocales from '@fullcalendar/core/locales-all'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const tripId = document.getElementById('tripId')
let calendarEl = document.getElementById('calendar')

const formatTime = minutes => {
    const m = minutes % 60
    const h = (minutes - m) / 60

    return (h > 0 ? h + 'h' + m : m + ' min.') + ' de trajet'
}

const formatDate = date => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Les mois sont indexés à 0
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

if (tripId && calendarEl) {
    axios({
        method: 'get',
        url: Routing.generate('trip_planning_get', {'trip': tripId.value}),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
        .then(response => {
            let planning = new Calendar(calendarEl, {
                plugins: [timeGridPlugin, interactionPlugin],
                locales: allLocales,
                locale: 'fr',
                datesSet: function (info) {
                    const {view: {type: viewType, title: defaultTitle}, start, end} = info

                    let title = defaultTitle
                    const startDate = new Date(start)
                    const endDate = new Date(end)

                    const formatDate = (date, options) => new Intl.DateTimeFormat('fr-FR', options).format(date)

                    if (viewType === 'timeGridWeek') {
                        const startDay = formatDate(startDate, {day: 'numeric'})
                        const endDay = formatDate(endDate, {day: 'numeric'}) - 1
                        const startMonth = formatDate(startDate, {month: 'short'})
                        const endMonth = formatDate(endDate, {month: 'short'})

                        if (startMonth === endMonth) {
                            title = `Semaine du ${startDay} au ${endDay} ${startMonth}`
                        } else {
                            title = `Semaine du ${startDay} ${startMonth} au ${endDay} ${endMonth}`
                        }
                    } else if (viewType === 'timeGridDay') {
                        const day = formatDate(startDate, {day: 'numeric'})
                        const month = formatDate(startDate, {month: 'long'})

                        title = `Journée du ${day} ${month}`
                    }

                    const titleElement = document.querySelector('.fc-toolbar-title')
                    titleElement.textContent = title

                    const infoText = document.createElement('small')
                    infoText.classList.add('block')
                    infoText.textContent = 'Cliquez sur un évènement pour obtenir plus de détails.'
                    titleElement.insertAdjacentElement('beforeend', infoText)
                },
                firstDay: 1,
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'title',
                    right: 'prev,next'
                },
                footerToolbar: {
                    right: 'timeGridWeek,timeGridDay'
                },
                buttonText: {
                    week: 'Semaine',
                    day: 'Jour',
                },
                validRange: {
                    start: response.data.start,
                    end: response.data.end
                },
                editable: true,
                events: response.data.events,
                eventDidMount: info => {
                    if (info.event.extendedProps.timeToGo) {
                        let timeToGo = document.createElement('small')
                        timeToGo.classList.add('font-bold', 'text-wrap', 'text-black', 'time-to-go')
                        timeToGo.textContent = formatTime(info.event.extendedProps.timeToGo)
                        // info.el.insertBefore(timeToGo, info.el.firstChild)
                        document.querySelector('.fc-event-time').insertAdjacentElement('afterend', timeToGo)

                        let eventType = document.createElement('small')
                        eventType.classList.add('font-bold', 'text-black', 'text-wrap', 'event-type')
                        eventType.textContent = info.event.extendedProps.type
                        document.querySelector('.time-to-go').insertAdjacentElement('afterend', eventType)
                    }
                },
                eventClick: info => {
                    window.location.href = Routing.generate('trip_planning_edit', {
                        'trip': tripId.value,
                        'event': info.event.id
                    })
                },
                eventDrop: info => {
                    const params = new URLSearchParams()
                    params.append('id', info.event.id)
                    params.append('start', formatDate(info.event.start))

                    axios({
                        method: 'post',
                        url: Routing.generate('trip_planning_drop_event', {'trip': tripId.value}),
                        data: params,
                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    })
                        .then()
                        .catch(() => {
                            alert('Une erreur est survenue lors du déplacement de l\'évènement.')
                        })
                },
                allDaySlot: false,
                slotDuration: '00:15:00',
                scrollTime: '08:00:00',
                height: '40em',
            })

            planning.render()
        })
        .catch(() => {
        })
}