import "quill/dist/quill.core.css"
import "quill/dist/quill.snow.css"
import './styles/app.css'

const notifications = document.querySelectorAll('.close-notification')

notifications.forEach(el => {
    el.addEventListener('click', () => {
        el.closest('.notification').remove()
    })
})