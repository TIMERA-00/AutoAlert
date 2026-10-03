import { assistantWidget } from './assistant'

/**
 * Livewire 4 bundles its own Alpine and owns `window.Alpine`: importing
 * alpinejs here would start a second instance, and every Livewire morph would
 * then reconcile nodes against the wrong data stack. So the widget is
 * registered as an Alpine data provider on Livewire's instance instead.
 *
 * Livewire boots on DOMContentLoaded, and module scripts run before it, so
 * this listener is always registered in time.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('assistantWidget', assistantWidget)
})
