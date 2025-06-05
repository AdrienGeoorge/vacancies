const initializeCookies = () => {
    const banner = document.getElementById('cookie-banner')
    const cookieConsent = localStorage.getItem('cookieConsent')

    if (!cookieConsent) {
        banner.style.display = 'block'
        document.getElementById('acceptCookies').addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'true')
            banner.style.display = 'none'
        })
    }
}

export default initializeCookies