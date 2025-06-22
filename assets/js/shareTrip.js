import axios from "axios"
import Routing from "fos-router"

const modalShareButton = document.getElementById('share-modal-button')
const emailShareInput = document.getElementById('email-share')
const errorShare = document.getElementById('error-share')

if (modalShareButton) {
    if (!modalShareButton.classList.contains('init')) {
        modalShareButton.addEventListener('click', () => {
            const modalShare = document.getElementById('share-modal')
            modalShare.classList.remove('hidden')
            modalShare.classList.add('grid')
        })

        modalShareButton.classList.add('init')
    }
}

const closeModalShare = document.getElementById('close-share-modal')

if (closeModalShare) {
    closeModalShare.addEventListener('click', () => {
        const modalShare = document.getElementById('share-modal')
        modalShare.classList.add('hidden')
        modalShare.classList.remove('grid')
        errorShare.classList.add('hidden')
        emailShareInput.value = ''
    })
}

const formShareButton = document.getElementById('form-share-button')

if (formShareButton) {
    if (!formShareButton.classList.contains('init')) {
        formShareButton.addEventListener('click', e => {
            const tripId = document.getElementById('tripId')
            const emailShareInput = document.getElementById('email-share')
            const errorShare = document.getElementById('error-share')

            if (emailShareInput.value.match(/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
                const params = new URLSearchParams()
                params.append('email', emailShareInput.value)

                axios({
                    method: 'post',
                    url: Routing.generate('trip_share', {'trip': tripId.value}),
                    data: params,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                    .finally(() => {
                        location.reload()
                    })
            } else {
                errorShare.classList.remove('hidden')
            }
        })

        formShareButton.classList.add('init')
    }
}