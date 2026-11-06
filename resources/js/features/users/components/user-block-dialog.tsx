import React, { useState } from 'react';
import { Dialog, DialogTitle, DialogDescription, DialogBody, DialogActions } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { UserManagement } from '@/types';
import { router } from '@inertiajs/react';

interface UserBlockDialogProps {
    isOpen: boolean;
    onClose: () => void;
    user: UserManagement | null;
    action: 'block' | 'unblock';
}

export function UserBlockDialog({ isOpen, onClose, user, action }: UserBlockDialogProps) {
    const [processing, setProcessing] = useState(false);

    const handleConfirm = () => {
        if (!user) return;

        setProcessing(true);
        router.post(
            route('users.block.toggle', user.id),
            { blocked: action === 'block' },
            {
                preserveScroll: true,
                onSuccess: () => {
                    onClose();
                },
                onFinish: () => {
                    setProcessing(false);
                }
            }
        );
    };

    return (
        <Dialog open={isOpen} onClose={onClose}>
            <DialogTitle>
                {action === 'block' ? 'Block User' : 'Unblock User'}
            </DialogTitle>
            <DialogDescription>
                {action === 'block'
                    ? `Are you sure you want to block ${user?.name}? They will be logged out and unable to access the system.`
                    : `Are you sure you want to unblock ${user?.name}? They will regain access to the system.`
                }
            </DialogDescription>
            <DialogBody>
                <p className="text-sm text-zinc-600 dark:text-zinc-400">
                    {action === 'block'
                        ? 'This action will immediately terminate all active sessions for this user.'
                        : 'The user will be able to log in and use the system again.'
                    }
                </p>
            </DialogBody>
            <DialogActions>
                <Button plain onClick={onClose}>
                    Cancel
                </Button>
                <Button
                    color={action === 'block' ? 'red' : 'green'}
                    onClick={handleConfirm}
                    disabled={processing}
                >
                    {processing
                        ? (action === 'block' ? 'Blocking...' : 'Unblocking...')
                        : (action === 'block' ? 'Block User' : 'Unblock User')
                    }
                </Button>
            </DialogActions>
        </Dialog>
    );
}
