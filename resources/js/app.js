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

/**
 * Confetti celebrations
 */
import confetti from 'canvas-confetti';

document.addEventListener('alpine:init', () => {
    Alpine.directive('auto-animate', (el) => autoAnimate(el));
});

/**
 * Celebrate with confetti - triggered via Livewire events
 */
window.celebrate = (type = 'default') => {
    const defaults = {
        origin: { y: 0.7 },
        zIndex: 9999,
    };

    if (type === 'completion') {
        // Big celebration for completing something (e.g., BNPL fully paid)
        const end = Date.now() + 1000;
        const colors = ['#10b981', '#34d399', '#6ee7b7', '#fbbf24', '#f59e0b'];

        (function frame() {
            confetti({
                ...defaults,
                particleCount: 3,
                angle: 60,
                spread: 55,
                origin: { x: 0 },
                colors: colors,
            });
            confetti({
                ...defaults,
                particleCount: 3,
                angle: 120,
                spread: 55,
                origin: { x: 1 },
                colors: colors,
            });

            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        })();
    } else {
        // Quick burst for single payment
        confetti({
            ...defaults,
            particleCount: 50,
            spread: 60,
            colors: ['#10b981', '#34d399', '#6ee7b7'],
        });
    }
};

// Listen for Livewire celebration events
document.addEventListener('livewire:init', () => {
    Livewire.on('celebrate', (event) => {
        window.celebrate(event.type || 'default');
    });
});
