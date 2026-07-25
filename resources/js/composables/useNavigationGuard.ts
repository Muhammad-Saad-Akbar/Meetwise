import { onMounted, onUnmounted } from 'vue'

export function useNavigationGuard() {

    const handlePopState = () => {
        // When user clicks back/forward, push state again to keep them on the same page
        history.pushState(null, '', window.location.href)
    }

    const handleBeforeUnload = (e) => {
        // When user tries to reload or close the tab, show browser's confirmation dialog
        e.preventDefault()
        e.returnValue = ''
    }

    onMounted(() => {
        // Push an extra history entry so the back button has something to "go back" to
        // without actually leaving the page
        history.pushState(null, '', window.location.href)
        window.addEventListener('popstate', handlePopState)
        window.addEventListener('beforeunload', handleBeforeUnload)
    })

    onUnmounted(() => {
        window.removeEventListener('popstate', handlePopState)
        window.removeEventListener('beforeunload', handleBeforeUnload)
    })
}
