const initializePasswordIcons = () => {
    const passwordInputs = document.querySelectorAll('input[type="password"]')

    passwordInputs.forEach(el => {
        el.nextElementSibling.addEventListener('click', () => {
            const input = el
            const isPassword = input.type === 'password'

            input.type = isPassword ? 'text' : 'password'

            // Change l'icône en fonction de l'état
            el.nextElementSibling.innerHTML = isPassword
                ? `
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.964 9.964 0 012.052-3.368M6.72 6.72A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.961 9.961 0 01-4.233 5.233M3 3l18 18" />
        </svg>`
                : `
        <!-- Icône œil ouvert -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>`
        })
    })
}

export default initializePasswordIcons