const initializeHeaderActions = () => {
    const userMenuButton = document.querySelector('.show-user-menu')
    const userMenu = document.querySelector('.user-menu')

    const userNotificationsButton = document.querySelector('.show-user-notifications')
    const userNotifications = document.querySelector('.user-notifications')

    if (userMenuButton && userNotificationsButton) {
        userMenuButton.addEventListener('click', ev => {
            if (userMenu.classList.contains('hidden')) {
                userMenu.classList.add('flex')
                userMenu.classList.remove('hidden')
            } else {
                userMenu.classList.remove('flex')
                userMenu.classList.add('hidden')
            }
        })

        userNotificationsButton.addEventListener('click', ev => {
            if (userNotifications.classList.contains('hidden')) {
                userNotifications.classList.add('flex')
                userNotifications.classList.remove('hidden')
            } else {
                userNotifications.classList.remove('flex')
                userNotifications.classList.add('hidden')
            }
        })

        document.addEventListener('click', ev => {
            const isClickInsideUserMenu = userMenu.contains(ev.target) || userMenuButton.contains(ev.target)
            if (!isClickInsideUserMenu && !userMenu.classList.contains('hidden')) {
                userMenu.classList.remove('flex')
                userMenu.classList.add('hidden')
            }

            const isClickInsideUserNotifications = userNotifications.contains(ev.target) || userNotificationsButton.contains(ev.target)
            if (!isClickInsideUserNotifications && !userNotifications.classList.contains('hidden')) {
                userNotifications.classList.remove('flex')
                userNotifications.classList.add('hidden')
            }
        })
    }
}

export default initializeHeaderActions