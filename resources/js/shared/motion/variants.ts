import type { Variants } from 'motion/react';
import { durations, easeInstitutional, slideDistance } from './tokens';

/**
 * Reusable animation variants. Every reveal uses the same `hidden` -> `visible`
 * state names so parents (stagger containers) can drive their children.
 */

export const fadeIn: Variants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { duration: durations.base, ease: easeInstitutional },
    },
};

export const fadeInUp: Variants = {
    hidden: { opacity: 0, y: slideDistance },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: durations.base, ease: easeInstitutional },
    },
};

export const scaleIn: Variants = {
    hidden: { opacity: 0, scale: 0.98 },
    visible: {
        opacity: 1,
        scale: 1,
        transition: { duration: durations.base, ease: easeInstitutional },
    },
};

/** Container that sequences its children's reveals. */
export const staggerContainer: Variants = {
    hidden: {},
    visible: {
        transition: { staggerChildren: 0.08, delayChildren: 0.05 },
    },
};

/** Item variant for children of a {@link staggerContainer}. */
export const staggerItem: Variants = fadeInUp;
