import { Button } from '@ui/primitives/button'
import { Dialog, DialogActions, DialogBody, DialogDescription, DialogTitle } from '@ui/primitives/dialog'
import { Notification } from '@/types'

interface NotificationDialogViewProps {
    isOpen: boolean;
    onClose: () => void;
    notification: Notification | null;
}

export function NotificationDialogView({ isOpen, onClose, notification }: NotificationDialogViewProps) {
    if (!notification) return null;

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <Dialog open={isOpen} onClose={onClose}>
            <DialogTitle>{notification.title}</DialogTitle>
            <DialogDescription>
                Notification details and information
            </DialogDescription>
            <DialogBody>
                <div className="space-y-4">
                    <div>
                        <h4 className="font-medium text-gray-900 dark:text-gray-100">Description</h4>
                        {/*
                         * Using dangerouslySetInnerHTML to render HTML content from the backend.
                         * Security Note: Only safe because content comes from trusted Laravel backend.
                         * If using external/user content, sanitization would be required.
                         */}
                        <div
                            className="mt-1 text-gray-600 dark:text-gray-400"
                            dangerouslySetInnerHTML={{ __html: notification.description }}
                        />
                    </div>
                    <div>
                        <h4 className="font-medium text-gray-900 dark:text-gray-100">Status</h4>
                        <p className="mt-1">
                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                notification.is_read
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                    : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                            }`}>
                                {notification.is_read ? 'Read' : 'Unread'}
                            </span>
                        </p>
                    </div>
                    <div>
                        <h4 className="font-medium text-gray-900 dark:text-gray-100">Date Created</h4>
                        <p className="mt-1 text-gray-600 dark:text-gray-400">{formatDate(notification.created_at)}</p>
                    </div>
                </div>
            </DialogBody>
            <DialogActions>
                <Button plain onClick={onClose}>
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    )
}
