import axios from "axios"
import Routing from "fos-router"

const user = document.getElementById('userId')
const followButton = document.querySelector('.followButton')
const countFollows = document.getElementById('countFollows')
const countFollowedBy = document.getElementById('countFollowedBy')

if (followButton) {
    followButton.addEventListener('click', () => {
        axios({
            method: 'post',
            url: Routing.generate('user_follow', {'user': user.value}),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.data)
            .then(data => {
                switch (data.status) {
                    case 'followed':
                        followButton.innerHTML = 'Ne plus suivre'
                        break
                    case 'waiting':
                        followButton.innerHTML = 'Demande de suivi en attente'
                        break
                    case 'deleted':
                        if (data.privateProfile === true) {
                            followButton.innerHTML = 'Demander à suivre'
                        } else {
                            followButton.innerHTML = 'Suivre ce voyageur'
                        }
                        break
                }

                countFollows.innerHTML = data.follows
                countFollowedBy.innerHTML = data.followedBy
            })
            .catch(() => {
                alert('error', 'Une erreur est survenue.')
            })
    })
}

const visibilityButton = document.querySelector('.canChangeVisibility')

if (visibilityButton) {
    const profileVisibility = document.querySelector('.profile-visibility')
    const profileVisibilityIcon = document.querySelector('.profile-visibility-icon')

    const privateIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"></path>'
    const publicIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"></path>'

    visibilityButton.addEventListener('click', () => {
        const params = new URLSearchParams()
        params.append('userId', user.value)

        axios({
            method: 'post',
            url: Routing.generate('user_change_visibility'),
            data: params,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.data)
            .then(data => {
                if (data.private === true) {
                    profileVisibility.innerHTML = 'Profil privé'
                    profileVisibilityIcon.innerHTML = privateIcon
                } else {
                    profileVisibility.innerHTML = 'Profil public'
                    profileVisibilityIcon.innerHTML = publicIcon
                }
            })
            .catch(err => {
                if (err.status === 403) alert('warning', 'Tu ne peux pas modifier la visibilité du profil d\'un autre utilisateur.')
                else alert('error', 'Une erreur est survenue.')
            })
    })
}