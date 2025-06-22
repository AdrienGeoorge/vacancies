import axios from "axios"
import Routing from "fos-router"

const user = document.getElementById('userId')
const followButton = document.querySelector('.followButton')

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
                            followButton.innerHTML = 'Suivre'
                        }
                        break
                }
            })
            .catch(() => {
                alert('error', 'Une erreur est survenue.')
            })
    })
}