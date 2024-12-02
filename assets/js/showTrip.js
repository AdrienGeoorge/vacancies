import Chart from 'chart.js/auto'
import ChartDataLabels from 'chartjs-plugin-datalabels'
import axios from "axios"
import Routing from "fos-router"

const tripId = document.getElementById('tripId')
const ctx = document.getElementById('budgetChart').getContext('2d')

const data = axios({
    method: 'get',
    url: Routing.generate('trip_get_budget', {'id': tripId.value}),
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
    .then(response => {
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
                        formatter: (value, ctx) => {
                            return value + ' €'
                        },
                        color: '#fff',
                    }
                },
            },
            plugins: [ChartDataLabels],
        })
    })
    .catch(() => {
    })