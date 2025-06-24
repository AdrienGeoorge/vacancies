const departureDate = document.getElementById('trip_departureDate')
const returnDate = document.getElementById('trip_returnDate')

departureDate.addEventListener('change', () => {
    returnDate.min = departureDate.value
})