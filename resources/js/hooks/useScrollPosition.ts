import { useEffect, useState, useRef, useCallback } from 'react';

interface UseScrollPositionOptions {
    threshold?: number;
    enabled?: boolean;
}

/**
 * Custom hook to detect scroll position and return whether
 * the page has been scrolled past a specified threshold.
 *
 * Uses requestAnimationFrame throttling for optimal performance
 * and handles iOS elastic scrolling edge cases.
 *
 * @param options.threshold - Scroll distance in pixels to trigger (default: 50)
 * @param options.enabled - Whether scroll detection is active (default: true)
 * @returns boolean indicating if page is scrolled past threshold
 *
 * @example
 * const isScrolled = useScrollPosition({ threshold: 100, enabled: true });
 */
export function useScrollPosition({
    threshold = 50,
    enabled = true,
}: UseScrollPositionOptions = {}): boolean {
    const [isScrolled, setIsScrolled] = useState(false);
    const ticking = useRef(false);
    const isInitialized = useRef(false);

    const handleScroll = useCallback(() => {
        if (!ticking.current) {
            window.requestAnimationFrame(() => {
                // Clamp scrollY to prevent negative values (iOS bounce)
                const scrollY = Math.max(0, window.scrollY);
                setIsScrolled(scrollY > threshold);
                ticking.current = false;
            });
            ticking.current = true;
        }
    }, [threshold]);

    useEffect(() => {
        if (!enabled) {
            setIsScrolled(false);
            isInitialized.current = false;
            return;
        }

        // Set initial state immediately (client-side only)
        // This prevents hydration mismatch by synchronously setting state
        if (!isInitialized.current) {
            const scrollY = Math.max(0, window.scrollY);
            setIsScrolled(scrollY > threshold);
            isInitialized.current = true;
        }

        // Add scroll listener with passive option for better performance
        window.addEventListener('scroll', handleScroll, { passive: true });

        return () => window.removeEventListener('scroll', handleScroll);
    }, [threshold, enabled, handleScroll]);

    return isScrolled;
}