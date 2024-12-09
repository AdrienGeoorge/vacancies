const addFormToCollection = (e) => {
    const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass)
    const item = document.createElement('div')
    item.classList.add('row')

    const item_title = document.createElement('div')
    item_title.classList.add('text-lg', 'font-poppins-semi-bold', 'mb-4')
    item_title.innerHTML = 'Dépense n°' + (parseInt(collectionHolder.dataset.index) + 1)

    item.innerHTML = item_title.outerHTML + collectionHolder.dataset.prototype.replace(/__name__/g, collectionHolder.dataset.index)

    collectionHolder.appendChild(item)
    collectionHolder.dataset.index++

    addTagFormDeleteLink(item)
}

const addTagFormDeleteLink = (item) => {
    const removeFormButton = document.createElement('button')
    removeFormButton.innerText = 'Supprimer'
    removeFormButton.classList.add('tracking-wide', 'bg-red-500', 'text-white', 'py-2', 'px-5', 'rounded-full', 'w-fit',
        'm-auto', 'mt-2', 'flex', 'items-center', 'justify-center', 'hover:bg-red-700', 'transition-all', 'duration-300',
        'ease-in-out', 'focus:shadow-outline', 'focus:outline-none')

    item.append(removeFormButton)

    removeFormButton.addEventListener('click', (e) => {
        e.preventDefault()
        item.remove()
    })
}

document
    .querySelectorAll('.add_item_link')
    .forEach(btn => {
        btn.addEventListener("click", addFormToCollection)
    })

document
    .querySelectorAll('.additionalExpansive .row')
    .forEach((tag) => {
        addTagFormDeleteLink(tag)
    })