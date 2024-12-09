const select = document.getElementById('transport_form_type')

if (select) {
    const defaultValue = select.querySelector('option[value=""]')
    defaultValue.disabled = true

    select.addEventListener('change', () => {
        document.querySelector('.subscription').classList.add('hidden')
        document.querySelector('.departure').classList.add('hidden')
        document.querySelector('.destination').classList.add('hidden')

        if (select.options[select.value].innerHTML === 'Transports en commun') {
            document.querySelector('.subscription').classList.remove('hidden')
        }

        if (select.options[select.value].innerHTML !== 'Transports en commun') {
            document.querySelector('.departure').classList.remove('hidden')
            document.querySelector('.destination').classList.remove('hidden')
        }
    })
}