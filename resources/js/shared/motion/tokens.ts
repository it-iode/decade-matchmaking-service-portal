import type { Transition } from 'motion/react';

/**
 * Motion design tokens for the Ocean Decade Portal.
 *
 * Tuned for a "subtle & professional" feel appropriate to a UNESCO/institutional
 * audience: short durations, gentle easing, restrained spring physics. Keep every
 * timing/easing decision here so motion stays consistent across the app.
 */

/** Durations in seconds. */
export const durations = {
    fast: 0.2,
    base: 0.3,
    slow: 0.4,
} as const;

/** Gentle ease-out cubic-bezier used for fades and slides. */
export const easeInstitutional: [number, number, number, number] = [0.22, 1, 0.36, 1];

/** Restrained spring for hover/tap feedback. */
export const springGentle: Transition = {
    type: 'spring',
    stiffness: 300,
    damping: 30,
};

/** Default distance (px) elements travel on a fade + slide reveal. */
export const slideDistance = 12;
