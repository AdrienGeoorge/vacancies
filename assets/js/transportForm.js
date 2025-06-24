const departureDate = document.getElementById('transport_form_departureDate')
const arrivalDate = document.getElementById('transport_form_arrivalDate')

departureDate.addEventListener('change', () => {
    arrivalDate.min = departureDate.value
})