/**
 * Secure Icon Mapper
 *
 * Maps icon names from backend to React components using an allowlist approach.
 * This prevents XSS vulnerabilities by only allowing explicitly defined icons.
 */

import {
    ArrowDownTrayIcon,
    CheckIcon,
    QuestionMarkCircleIcon,
    PencilSquareIcon,
    TrashIcon,
    DocumentTextIcon,
    DocumentArrowUpIcon,
    PlusIcon,
    PencilIcon,
    ArrowRightIcon,
    XMarkIcon,
    PauseIcon,
    PlayIcon,
    EyeIcon,
} from '@heroicons/react/16/solid';
import type { ValidIconName } from '@/types/actions';
import type React from 'react';

/**
 * Allowlist of valid icon mappings.
 * Only icons in this map can be rendered.
 */
const ICON_MAP: Record<ValidIconName, React.ComponentType<React.ComponentProps<'svg'>>> = {
    'arrow-down-tray': ArrowDownTrayIcon,
    'check': CheckIcon,
    'question-mark-circle': QuestionMarkCircleIcon,
    'pencil-square': PencilSquareIcon,
    'trash': TrashIcon,
    'document-text': DocumentTextIcon,
    'document-arrow-up': DocumentArrowUpIcon,
    'plus': PlusIcon,
    'pencil': PencilIcon,
    'arrow-right': ArrowRightIcon,
    'x-mark': XMarkIcon,
    'pause': PauseIcon,
    'play': PlayIcon,
    'eye': EyeIcon,
} as const;

/**
 * Get the React component for an icon name.
 *
 * @param iconName - Icon identifier from backend
 * @returns React component for the icon, or QuestionMarkCircleIcon as fallback
 *
 * @example
 * const Icon = getIconComponent('check');
 * return <Icon data-slot="icon" />;
 */
export function getIconComponent(iconName: string): React.ComponentType<React.ComponentProps<'svg'>> {
    // Type-safe lookup with fallback
    const icon = ICON_MAP[iconName as ValidIconName];
    
    if (!icon) {
        console.warn(`Unknown icon: "${iconName}". Using fallback icon.`);
        return QuestionMarkCircleIcon;
    }
    
    return icon;
}

/**
 * Check if an icon name is valid.
 *
 * @param iconName - Icon identifier to check
 * @returns true if icon exists in allowlist
 */
export function isValidIcon(iconName: string): iconName is ValidIconName {
    return iconName in ICON_MAP;
}
