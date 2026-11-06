import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/react';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createRoot} from 'react-dom/client';
import {MotionConfig} from 'motion/react';
import {ConfirmationProvider} from '@ui/organisms/confirmation';
import {initDarkMode} from '@shared/utils/darkMode';

// Initialize dark mode detection
initDarkMode();

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({el, App, props}) {
        const root = createRoot(el);
        root.render(
            <MotionConfig reducedMotion="user">
                <ConfirmationProvider>
                    <App {...props} />
                </ConfirmationProvider>
            </MotionConfig>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
