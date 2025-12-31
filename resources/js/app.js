/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

/**
 * Auto-animate directive for Alpine.js
 * Provides smooth animations for list additions, removals, and reordering
 */
import { autoAnimate } from '@formkit/auto-animate';

document.addEventListener('alpine:init', () => {
    Alpine.directive('auto-animate', (el) => autoAnimate(el));
});
