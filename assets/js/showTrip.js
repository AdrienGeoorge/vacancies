import axios from "axios"
import Routing from "fos-router"
import Quill from 'quill'

const tripId = document.getElementById('tripId')
const editor = document.getElementById('editor')

if (tripId) {
    if (editor) {
        let timer
        const quill = new Quill(editor, {
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'link'],

                    [{'size': ['small', false, 'large']}, {'header': 1}, {'header': 2}, {'header': 3}],
                    [{'list': 'ordered'}, {'list': 'bullet'}, {'list': 'check'}],
                    [{'indent': '-1'}, {'indent': '+1'}],

                    [{'color': []}, {'background': []}],
                    [{'align': []}],

                    ['clean']
                ]
            },
            placeholder: 'Ajoutez des notes communes avec tous les autres voyageurs prenant part à cette aventure : par exemple mettez à disposition les liens vers les restaurants à faire...',
            theme: 'snow'
        })
        quill.on('text-change', (delta, oldDelta, source) => {
            clearTimeout(timer)
            timer = setTimeout(() => {
                const params = new URLSearchParams()
                params.append('blocNotes', quill.root.innerHTML)

                axios({
                    method: 'post',
                    url: Routing.generate('trip_update_bloc_notes', {'trip': tripId.value}),
                    data: params,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                    .then(() => {})
                    .catch(() => {
                        alert('error', 'Une erreur est survenue lors de la sauvegarde du bloc note.')
                    })
            }, 2000)
        });

    }
}