const select = document.getElementById('transport_form_type')

if (select) {
    const init = () => {
        document.querySelector('.subscription').classList.add('hidden')
        document.querySelector('.company').classList.add('hidden')
        document.querySelector('.car').classList.add('hidden')
        document.querySelector('.departure').classList.add('hidden')
        document.querySelector('.destination').classList.add('hidden')
        document.querySelector('.price').classList.remove('hidden')
        document.querySelector('.payment').classList.remove('hidden')
        document.querySelector('.transport-payed-by').classList.remove('hidden')

        if (select.value !== '') {
            if (select.options[select.value].innerHTML === 'Transports en commun') {
                document.querySelector('.subscription').classList.remove('hidden')
                document.querySelector('.company').classList.remove('hidden')
                document.querySelector('.transport-payed-by').classList.add('hidden')
            }

            if (select.options[select.value].innerHTML === 'Voiture') {
                document.querySelector('.car').classList.remove('hidden')
                document.querySelector('.price').classList.add('hidden')
            }

            if (select.options[select.value].innerHTML !== 'Transports en commun' &&
                select.options[select.value].innerHTML !== 'Voiture') {
                document.querySelector('.departure').classList.remove('hidden')
                document.querySelector('.destination').classList.remove('hidden')
            }
        }
    }

    const defaultValue = select.querySelector('option[value=""]')
    defaultValue.disabled = true
    init()

    select.addEventListener('change', () => {
        init()
    })
}