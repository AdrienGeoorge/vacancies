const eventStart = document.getElementById('planning_event_start')
const eventEnd = document.getElementById('planning_event_end')

eventStart.addEventListener('change', () => {
    eventEnd.min = eventStart.value
})