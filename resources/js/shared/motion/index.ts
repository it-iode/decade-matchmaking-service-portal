/**
 * Shared motion foundation. Import animation helpers from `@shared/motion`.
 *
 * - Components: `Reveal`, `Stagger`, `StaggerItem`, `PageTransition`
 * - Variants (for applying motion directly to `motion.ul`/`motion.li` etc.):
 *   `fadeIn`, `fadeInUp`, `scaleIn`, `staggerContainer`, `staggerItem`
 * - Tokens: `durations`, `easeInstitutional`, `springGentle`, `slideDistance`
 *
 * See ./README.md for when to use each and the reduced-motion contract.
 */
export { Reveal, Stagger, StaggerItem, PageTransition } from './components';
export {
    fadeIn,
    fadeInUp,
    scaleIn,
    staggerContainer,
    staggerItem,
} from './variants';
export {
    durations,
    easeInstitutional,
    springGentle,
    slideDistance,
} from './tokens';
