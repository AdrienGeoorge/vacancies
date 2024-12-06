import './styles/app.css'
import htmx from 'htmx.org'

window.htmx = htmx

const notifications = document.querySelectorAll('.close-notification')

notifications.forEach(el => {
    el.addEventListener('click', () => {
        el.closest('.notification').remove()
    })
})