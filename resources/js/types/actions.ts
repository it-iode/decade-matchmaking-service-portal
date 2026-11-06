/**
 * Action Provider Pattern - TypeScript Interfaces
 *
 * Simplified type system for backend-driven actions with three core handler types:
 * - route: Navigation actions with optional new tab support
 * - dialog: Modal dialogs with type-safe props through DialogPropsMap
 * - file_upload: Document upload actions with validation
 *
 * Architecture Benefits:
 * - Simple and focused on essential action types
 * - Type-safe dialog props through DialogPropsMap registry
 * - Discriminated unions for automatic type narrowing
 * - Easy to understand and maintain
 * - Full IntelliSense support
 */

import type { OCDRequestStatus } from '@/features/requests/types/request.types';

/**
 * Valid icon names that can be used in actions.
 * These must match the icons available in the icon mapper.
 */
export type ValidIconName =
    | 'arrow-down-tray'
    | 'check'
    | 'question-mark-circle'
    | 'pencil-square'
    | 'trash'
    | 'document-text'
    | 'document-arrow-up'
    | 'plus'
    | 'pencil'
    | 'arrow-right'
    | 'x-mark'
    | 'pause'
    | 'play'
    | 'eye';

/**
 * Valid button colors supported by Catalyst UI.
 */
export type ButtonColor =
    | 'red'
    | 'green'
    | 'blue'
    | 'yellow'
    | 'indigo'
    | 'purple'
    | 'orange'
    | 'cyan'
    | 'emerald'
    | 'pink';

/**
 * HTTP methods supported for action routes.
 */
export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

/**
 * Visual styling configuration for an action.
 * Matches backend ActionStyle array structure.
 */
export interface ActionStyle {
    color?: ButtonColor;
    icon: ValidIconName;
    variant?: 'solid' | 'outline' | 'plain';
}

/**
 * File upload configuration for file upload actions.
 */
export interface FileUploadMetadata {
    accept?: string;
    maxSize?: number;
    multiple?: boolean;
    endpoint?: string;
    documentType?: string;
    title?: string;
    description?: string;
    validationRules?: {
        required?: boolean;
        mimeTypes?: string[];
        maxSizeBytes?: number;
    };
}

/**
 * Supported action handler types - simplified to route, dialog, and file_upload.
 */
export type ActionHandler = 'route' | 'dialog' | 'file_upload';

/**
 * Route handler metadata - for navigation actions.
 */
export interface RouteHandlerMetadata {
    handler: 'route';
    /** Open in new tab/window */
    open_in_new_tab?: boolean;
}

/**
 * Dialog handler metadata - for modal dialogs.
 */
export interface DialogHandlerMetadata {
    handler: 'dialog';
    /** Component name to render */
    dialog_component: string;
    /** Props to pass to the dialog component */
    dialog_props?: DialogPropsMap[keyof DialogPropsMap];
}

/**
 * File upload handler metadata - for file upload actions.
 */
export interface FileUploadHandlerMetadata {
    handler: 'file_upload';
    file_upload: FileUploadMetadata;
}

/**
 * Type-safe dialog props mapping.
 * Add your dialog components and their props here for full type safety.
 *
 * Example:
 * ```typescript
 * export interface DialogPropsMap {
 *     MyDialog: {
 *         userId: number;
 *         mode: 'create' | 'edit';
 *     };
 * }
 * ```
 */
export interface DialogPropsMap {
    UpdateStatusDialog: {
        requestId: number;
        currentStatus?: OCDRequestStatus;
        availableStatuses: OCDRequestStatus[];
    };
    ConfirmationDialog: {
        title: string;
        message: string;
        confirmText?: string;
        cancelText?: string;
        variant?: 'danger' | 'warning' | 'info';
    };
    FileUploadDialogProps: {
        accept?: string;
        maxSize?: number;
        multiple?: boolean;
        endpoint: string;
        documentType?: string;
        title?: string;
        description?: string;
        validationRules?: {
            required?: boolean;
            mimeTypes?: string[];
            maxSizeBytes?: number;
        };
    };
    // Add more dialog types as needed
    [key: string]: Record<string, any>; // Fallback for untyped dialogs
}

/**
 * Handler-specific metadata using discriminated unions.
 * TypeScript automatically selects the correct interface based on handler type.
 */
export type HandlerMetadata = RouteHandlerMetadata | DialogHandlerMetadata | FileUploadHandlerMetadata;

/**
 * Complete action configuration from backend.
 */
export interface EntityAction {
    key: string;
    label: string;
    route: string | null;
    method: HttpMethod;
    enabled: boolean;
    style?: ActionStyle;
    confirm?: string;
    data?: Record<string, unknown>;
    metadata?: HandlerMetadata;
}

/**
 * Type guard functions for runtime type checking with full type narrowing.
 *
 * Usage:
 * ```typescript
 * if (ActionHandlerGuards.isDialogHandler(action.metadata)) {
 *     // TypeScript knows metadata is DialogHandlerMetadata
 *     const props = action.metadata.dialog_props;
 * }
 * ```
 */
export const ActionHandlerGuards = {
    isRouteHandler: (metadata?: HandlerMetadata): metadata is RouteHandlerMetadata =>
        metadata?.handler === 'route',

    isDialogHandler: (metadata?: HandlerMetadata): metadata is DialogHandlerMetadata =>
        metadata?.handler === 'dialog',

    isFileUploadHandler: (metadata?: HandlerMetadata): metadata is FileUploadHandlerMetadata =>
        metadata?.handler === 'file_upload',
};