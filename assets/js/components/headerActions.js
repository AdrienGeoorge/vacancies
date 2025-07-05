const initializeHeaderActions = () => {
    const userMenuButton = document.querySelector('.showUserMenu')
    if (userMenuButton) {
        userMenuButton.addEventListener('click', () => {
            const userMenu = document.querySelector('.userMenu')
            if (userMenu.classList.contains('hidden')) {
                userMenu.classList.add('flex')
                userMenu.classList.remove('hidden')
            } else {
                userMenu.classList.remove('flex')
                userMenu.classList.add('hidden')
            }
        })
    }
}

export default initializeHeaderActions