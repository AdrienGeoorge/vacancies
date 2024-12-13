import Chart from 'chart.js/auto'
import ChartDataLabels from 'chartjs-plugin-datalabels'
import axios from "axios"
import Routing from "fos-router"

const tripId = document.getElementById('tripId')
const ctx = document.getElementById('budgetChart')

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