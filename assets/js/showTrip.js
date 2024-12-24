import Chart from 'chart.js/auto'
import ChartDataLabels from 'chartjs-plugin-datalabels'
import axios from "axios"
import Routing from "fos-router"

import {Calendar} from '@fullcalendar/core'
import allLocales from '@fullcalendar/core/locales-all'
import listPlugin from '@fullcalendar/list'
import {getPlanningTitle} from "./components/dateFunctions"

const tripId = document.getElementById('tripId')
const ctx = document.getElementById('budgetChart')
let calendarEl = document.getElementById('calendar')

if (tripId && ctx) {
    const budgetNone = document.getElementById('budgetNone')

    axios({
        method: 'get',
        url: Routing.generate('trip_get_budget', {'trip': tripId.value}),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
        .then(response => {
            if (response.data.paid > 0 || response.data.toPay > 0) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Payé', 'A payer'],
                        datasets: [{
                            label: ' Montant',
                            data: [response.data.paid, response.data.toPay],
                            backgroundColor: [
                                'rgb(20 184 166)',
                                'rgb(239 68 68)',
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        tooltips: {
                            enabled: false
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            datalabels: {
                                formatter: (value) => {
                                    return value + '€'
                                },
                                color: '#fff',
                            }
                        },
                    },
                    plugins: [ChartDataLabels],
                })
            } else {
                ctx.classList.add('hidden')
                budgetNone.innerHTML = '<div class="text-center text-sm">Le graphique ne peut pas charger pour le moment car aucune dépense n\'a été saisie pour ce voyage.</div>'
            }
        })
        .catch(() => {
        })
}

if (tripId && calendarEl) {
    axios({
        method: 'get',
        url: Routing.generate('trip_planning_get', {'trip': tripId.value}),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
        .then(response => {
            let calendar = new Calendar(calendarEl, {
                plugins: [listPlugin],
                locales: allLocales,
                locale: 'fr',
                datesSet: function (info) {
                    document.querySelector('.fc-toolbar-title').textContent = getPlanningTitle(info)
                },
                customButtons: {
                    editPlanning: {
                        text: 'Consulter ou modifier',
                        click: function () {
                            window.location.href = Routing.generate('trip_planning_index', {'trip': tripId.value})
                        }
                    }
                },
                firstDay: 1,
                initialView: 'listWeek',
                headerToolbar: {
                    left: 'title',
                    right: 'prev,next'
                },
                footerToolbar: {
                    left: 'editPlanning',
                    right: 'listWeek,listDay'
                },
                buttonText: {
                    week: 'Semaine',
                    day: 'Jour',
                },
                height: '100%',
                validRange: {
                    start: response.data.start,
                    end: response.data.end
                },
                events: response.data.events,
                eventDidMount: info => {
                    console.log('ici');
                    if (info.event.extendedProps.description) {
                        let timeToGo = document.createElement('p')
                        timeToGo.classList.add('text-wrap', 'text-sm')
                        timeToGo.textContent = info.event.extendedProps.description
                        info.el.querySelector('.fc-list-event-title').insertAdjacentElement('beforeend', timeToGo)
                    }
                },
            })
            calendar.render()
        })
        .catch(() => {
        })
}