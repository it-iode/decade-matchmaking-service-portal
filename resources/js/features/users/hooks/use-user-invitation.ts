import { useState, useCallback, FormEvent } from 'react';
import axios from 'axios';
import { InvitationResult, InvitationErrorResponse } from '@features/users';

interface UseUserInvitationOptions {
    inviteRoute?: string;
    onSuccess?: (result: InvitationResult) => void;
    onError?: (error: InvitationResult) => void;
}

interface UseUserInvitationReturn {
    name: string;
    setName: (name: string) => void;
    email: string;
    setEmail: (email: string) => void;
    isInviting: boolean;
    error: string | null;
    success: string | null;
    handleSubmit: (e: FormEvent) => Promise<void>;
    reset: () => void;
    clearMessages: () => void;
}

export function useUserInvitation(options: UseUserInvitationOptions = {}): UseUserInvitationReturn {
    const {
        inviteRoute = 'admin.users.invite',
        onSuccess,
        onError
    } = options;

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [isInviting, setIsInviting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const clearMessages = useCallback(() => {
        setError(null);
        setSuccess(null);
    }, []);

    const reset = useCallback(() => {
        setName('');
        setEmail('');
        setIsInviting(false);
        clearMessages();
    }, [clearMessages]);

    const handleSubmit = useCallback(async (e: FormEvent) => {
        e.preventDefault();

        const trimmedName = name.trim();
        const trimmedEmail = email.trim();
        if (!trimmedName || !trimmedEmail) {
            return;
        }

        setIsInviting(true);
        clearMessages();

        try {
            await axios.post(route(inviteRoute), { name: trimmedName, email: trimmedEmail });

            const successMessage = `Invitation sent to ${trimmedEmail}`;
            setSuccess(successMessage);
            setName('');
            setEmail('');

            const result: InvitationResult = {
                success: true,
                message: successMessage,
                email: trimmedEmail,
                name: trimmedName
            };
            onSuccess?.(result);
        } catch (err: unknown) {
            const axiosError = err as InvitationErrorResponse;
            let errorMessage: string;

            if (axiosError.response?.status === 422) {
                errorMessage = axiosError.response.data?.errors?.name?.[0]
                    || axiosError.response.data?.errors?.email?.[0]
                    || 'Invalid input';
            } else {
                errorMessage = axiosError.response?.data?.message
                    || axiosError.response?.data?.error
                    || 'Failed to send invitation';
            }

            setError(errorMessage);

            const result: InvitationResult = {
                success: false,
                message: errorMessage,
                email: trimmedEmail,
                name: trimmedName
            };
            onError?.(result);
        } finally {
            setIsInviting(false);
        }
    }, [name, email, inviteRoute, onSuccess, onError, clearMessages]);

    return {
        name,
        setName,
        email,
        setEmail,
        isInviting,
        error,
        success,
        handleSubmit,
        reset,
        clearMessages
    };
}
