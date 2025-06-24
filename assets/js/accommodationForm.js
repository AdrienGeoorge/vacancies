const arrivalDate = document.getElementById('accommodation_arrivalDate')
const departureDate = document.getElementById('accommodation_departureDate')

arrivalDate.addEventListener('change', () => {
    departureDate.min = arrivalDate.value
})