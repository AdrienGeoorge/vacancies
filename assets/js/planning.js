import axios from "axios"
import Routing from "fos-router"

import {Calendar} from '@fullcalendar/core'
import allLocales from '@fullcalendar/core/locales-all'
import timeGridPlugin from '@fullcalendar/timegrid'
import listPlugin from '@fullcalendar/list'
import interactionPlugin from '@fullcalendar/interaction'
import {getPlanningTitle, formatTime, formatDate} from "./components/dateFunctions"

const tripId = document.getElementById('tripId')
let calendarEl = document.getElementById('calendar')

if (tripId && calendarEl) {
    axios({
        method: 'get',
        url: Routing.generate('trip_planning_get', {'trip': tripId.value}),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
        .then(response => {
            let planning = new Calendar(calendarEl, {
                plugins: [timeGridPlugin, listPlugin, interactionPlugin],
                locales: allLocales,
                locale: 'fr',
                datesSet: function (info) {
                    const titleElement = document.querySelector('.fc-toolbar-title')
                    titleElement.textContent = getPlanningTitle(info)

                    const infoText = document.createElement('small')
                    infoText.classList.add('block')
                    infoText.textContent = 'Cliquez sur un évènement pour obtenir plus de détails.'
                    titleElement.insertAdjacentElement('beforeend', infoText)
                },
                firstDay: 1,
                initialView: 'timeGridFiveDay',
                headerToolbar: {
                    left: 'title',
                    right: 'prev,next'
                },
                footerToolbar: {
                    right: 'timeGridFiveDay,listDay'
                },
                views: {
                    timeGridFiveDay: {
                        type: 'timeGrid',
                        duration: { days: 5 },
                        buttonText: '5 jours'
                    }
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
                    if (info.view.type === 'timeGridFiveDay') {
                        if (info.event.extendedProps.timeToGo) {
                            let timeToGo = document.createElement('small')
                            timeToGo.classList.add('font-bold', 'text-wrap', 'text-black', 'time-to-go')
                            timeToGo.textContent = formatTime(info.event.extendedProps.timeToGo)
                            info.el.querySelector('.fc-event-time').insertAdjacentElement('afterend', timeToGo)
                        }

                        let eventType = document.createElement('small')
                        eventType.classList.add('font-bold', 'text-black', 'text-wrap', 'event-type')
                        eventType.textContent = info.event.extendedProps.type

                        if (info.event.extendedProps.timeToGo) {
                            info.el.querySelector('.time-to-go').insertAdjacentElement('afterend', eventType)
                        } else {
                            info.el.querySelector('.fc-event-time').insertAdjacentElement('afterend', eventType)
                        }
                    } else {
                        if (info.event.extendedProps.description) {
                            let timeToGoAndDesc = document.createElement('p')
                            timeToGoAndDesc.classList.add('text-wrap', 'text-sm')
                            if (info.event.extendedProps.timeToGo) timeToGoAndDesc.innerHTML += `<b>${formatTime(info.event.extendedProps.timeToGo)}</b><br><br>`
                            timeToGoAndDesc.innerHTML += `${info.event.extendedProps.description.replace(/\n/g, '<br>')}`
                            info.el.querySelector('.fc-list-event-title').insertAdjacentElement('beforeend', timeToGoAndDesc)
                        }
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
                    if (info.event.end) params.append('end', formatDate(info.event.end))

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
            alert('error', 'Une erreur est survenue lors du chargement du planning.')
        })
}