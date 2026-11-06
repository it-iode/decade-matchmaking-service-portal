import React from 'react';
import { motion, useReducedMotion } from 'motion/react';
import { durations, easeInstitutional, slideDistance } from './tokens';
import { staggerContainer, staggerItem } from './variants';

/**
 * Accessibility contract: every wrapper below honours the user's "reduce motion"
 * preference. When it is set the element renders statically (no transforms, no
 * fade), so nothing moves. `<MotionConfig reducedMotion="user">` in app.tsx wires
 * the preference up globally; these guards make the intent explicit per wrapper.
 */

interface RevealProps {
    children: React.ReactNode;
    className?: string;
    /** Delay before the reveal starts, in seconds. */
    delay?: number;
    id?: string;
}

/** Fade + small rise the first time the element scrolls into view. */
export function Reveal({ children, className, delay = 0, id }: Readonly<RevealProps>) {
    const reduce = useReducedMotion();

    if (reduce) {
        return (
            <div className={className} id={id}>
                {children}
            </div>
        );
    }

    return (
        <motion.div
            id={id}
            className={className}
            initial={{ opacity: 0, y: slideDistance }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '-10%' }}
            transition={{ duration: durations.base, ease: easeInstitutional, delay }}
        >
            {children}
        </motion.div>
    );
}

interface StaggerProps {
    children: React.ReactNode;
    className?: string;
    /** Reveal when scrolled into view (default), or immediately on mount. */
    inView?: boolean;
}

/**
 * Container that sequences the reveal of its {@link StaggerItem} children. Use it
 * as the grid/list element itself (pass the layout classes via `className`).
 */
export function Stagger({ children, className, inView = true }: Readonly<StaggerProps>) {
    const reduce = useReducedMotion();

    if (reduce) {
        return <div className={className}>{children}</div>;
    }

    const trigger = inView
        ? { whileInView: 'visible' as const, viewport: { once: true, margin: '-10%' } }
        : { animate: 'visible' as const };

    return (
        <motion.div className={className} initial="hidden" variants={staggerContainer} {...trigger}>
            {children}
        </motion.div>
    );
}

interface StaggerItemProps {
    children: React.ReactNode;
    className?: string;
}

/** Direct child of a {@link Stagger}; reveals in sequence with its siblings. */
export function StaggerItem({ children, className }: Readonly<StaggerItemProps>) {
    const reduce = useReducedMotion();

    if (reduce) {
        return <div className={className}>{children}</div>;
    }

    return (
        <motion.div className={className} variants={staggerItem}>
            {children}
        </motion.div>
    );
}

interface PageTransitionProps {
    children: React.ReactNode;
    className?: string;
}

/**
 * Mount-only page enter transition. Give it a `key` tied to the current Inertia
 * page (e.g. `usePage().component`) so it replays on every navigation.
 */
export function PageTransition({ children, className }: Readonly<PageTransitionProps>) {
    const reduce = useReducedMotion();

    if (reduce) {
        return <div className={className}>{children}</div>;
    }

    return (
        <motion.div
            className={className}
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: durations.base, ease: easeInstitutional }}
        >
            {children}
        </motion.div>
    );
}
