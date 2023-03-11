/*
 * @package    plg_isrcset
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/plg_isrcset
 * @copyright  (C) 2023 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

/** @param {string} type */
const support = type => window && window[type]

const validAttribute = ['data-src', 'data-srcset']

const defaultConfig = {
    rootMargin: '0px',
    threshold: 0,
    enableAutoReload: false,
    load(element) {

        if (element.nodeName.toLowerCase() === 'video' && !element.getAttribute('data-src')) {
            if (element.children) {
                const childs = element.children
                let childSrc
                for (let i = 0; i <= childs.length - 1; i++) {
                    childSrc = childs[i].getAttribute('data-src')
                    if (childSrc) {
                        childs[i].src = childSrc
                    }
                }

                element.load()
            }
        }

        if (element.getAttribute('data-src')) {
            element.src = element.getAttribute('data-src')
        }

        if (element.getAttribute('data-srcset')) {
            element.setAttribute('srcset', element.getAttribute('data-srcset'))
        }
    },
    loaded() {}
}

function markAsLoaded(element) {
    element.setAttribute('data-loaded', true)
}

function preLoad(element) {
    if (element.getAttribute('data-placeholder-background')) {
        element.style.background = element.getAttribute('data-placeholder-background')
    }
}

const isLoaded = element => element.getAttribute('data-loaded') === 'true'

const onIntersection = (load, loaded) => (entries, observer) => {
    entries.forEach(entry => {
        if (entry.intersectionRatio > 0 || entry.isIntersecting) {
            observer.unobserve(entry.target)

            if (!isLoaded(entry.target)) {
                load(entry.target)
                markAsLoaded(entry.target)
                loaded(entry.target)
            }
        }
    })
}

const onMutation = load => entries => {
    entries.forEach(entry => {
        if (isLoaded(entry.target) && entry.type === 'attributes' && validAttribute.indexOf(entry.attributeName) > -1) {
            load(entry.target)
        }
    })
}

const getElements = (selector, root = document) => {
    if (selector instanceof Element) {
        return [selector]
    }

    if (selector instanceof NodeList) {
        return selector
    }

    return root.querySelectorAll(selector)
}

function isrcset(selector = '[data-srcset]', options = {}) {
    const {root, rootMargin, threshold, enableAutoReload, load, loaded} = Object.assign({}, defaultConfig, options)
    let observer
    let mutationObserver
    if (support('IntersectionObserver')) {
        observer = new IntersectionObserver(onIntersection(load, loaded), {
            root,
            rootMargin,
            threshold
        })
    }

    if (support('MutationObserver') && enableAutoReload) {
        mutationObserver = new MutationObserver(onMutation(load, loaded))
    }

    const elements = getElements(selector, root)
    for (let i = 0; i < elements.length; i++) {
        preLoad(elements[i])
    }

    return {
        observe() {
            const elements = getElements(selector, root)

            for (let i = 0; i < elements.length; i++) {
                if (isLoaded(elements[i])) {
                    continue
                }

                if (observer) {
                    if (mutationObserver && enableAutoReload) {
                        mutationObserver.observe(elements[i], {subtree: true, attributes: true, attributeFilter: validAttribute})
                    }

                    observer.observe(elements[i])
                    continue
                }

                load(elements[i])
                markAsLoaded(elements[i])
                loaded(elements[i])
            }
        },
        triggerLoad(element) {
            if (isLoaded(element)) {
                return
            }

            load(element)
            markAsLoaded(element)
            loaded(element)
        },
        observer,
        mutationObserver
    }
}

isrcset('img[data-srcset]', {
    rootMargin: '100px 100px',
    threshold: 0.1,
    enableAutoReload: true
}).observe();

isrcset('iframe[data-src]', {
    rootMargin: '100px 0px',
    threshold: 0.1,
    enableAutoReload: true
}).observe();