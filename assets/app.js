import "quill/dist/quill.core.css"
import "quill/dist/quill.snow.css"
import './styles/app.css'

import './js/components/alert'
import initializeCookies from './js/components/cookies.js'
import initializePasswordIcons from './js/components/passwordIcon.js'

initializeCookies()
initializePasswordIcons()

const notifications = document.querySelectorAll('.close-notification')
notifications.forEach(el => {
    el.addEventListener('click', () => {
        el.closest('.notification').remove()
    })
})

