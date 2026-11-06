/**
 * Simple dark mode detection and initialization
 * Detects system preference and applies dark class to html element
 */
export function initDarkMode(): void {
    // Check system preference
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    // Apply dark class based on system preference
    if (darkModeMediaQuery.matches) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    // Listen for system preference changes
    darkModeMediaQuery.addEventListener('change', (e) => {
        if (e.matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });
}
