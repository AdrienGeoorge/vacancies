const getPlanningTitle = (info) => {
    const {view: {type: viewType, title: defaultTitle}, start, end} = info

    let title = defaultTitle
    const startDate = new Date(start)
    const endDate = new Date(end)

    const formatDate = (date, options) => new Intl.DateTimeFormat('fr-FR', options).format(date)

    if (viewType === 'listWeek' || viewType === 'timeGridWeek') {
        const startDay = formatDate(startDate, {day: 'numeric'})
        const endDay = formatDate(endDate, {day: 'numeric'}) - 1
        const startMonth = formatDate(startDate, {month: 'short'})
        const endMonth = formatDate(endDate, {month: 'short'})

        if (startMonth === endMonth) {
            title = `Semaine du ${startDay} au ${endDay} ${startMonth}`
        } else {
            title = `Semaine du ${startDay} ${startMonth} au ${endDay} ${endMonth}`
        }
    } else if (viewType === 'listDay'  || viewType === 'timeGridDay') {
        const day = formatDate(startDate, {day: 'numeric'})
        const month = formatDate(startDate, {month: 'long'})

        title = `Journée du ${day} ${month}`
    } else {
        title = info.view.title
    }

    return title
}

const formatTime = minutes => {
    const m = minutes % 60
    const h = (minutes - m) / 60

    return h > 0
        ? `${h}h${m > 0 ? ` ${m} min.` : ''} de trajet`
        : `${m} min. de trajet`
}

const formatDate = date => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Les mois sont indexés à 0
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

export {getPlanningTitle, formatTime, formatDate}