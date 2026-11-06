import React, { useCallback, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogActions, DialogBody, DialogDescription, DialogTitle } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { Text } from '@ui/primitives/text';
import { Field, Label } from '@ui/primitives/fieldset';
import { Combobox, ComboboxOption, ComboboxLabel } from '@ui/primitives/combobox';
import { useAsyncSearch } from '@/shared/hooks';
import { useSubscribeForm } from '../hooks/use-subscribe-form';
import { SubscriptionFormOptions } from '../types/subscription.types';

type SelectOption = { value: number; label: string };

interface CreateSubscriptionDialogProps {
    isOpen: boolean;
    onClose: () => void;
    requests: SubscriptionFormOptions['requests'];
}

export function CreateSubscriptionDialog({
    isOpen,
    onClose,
    requests,
}: CreateSubscriptionDialogProps) {
    const [selectedUser, setSelectedUser] = useState<SelectOption | undefined>(undefined);
    const [selectedRequest, setSelectedRequest] = useState<SelectOption | undefined>(undefined);

    const transformUser = useCallback(
        (item: Record<string, unknown>) => ({
            value: item.id as number,
            label: `${item.name} (${item.email})`,
        }),
        []
    );

    const { options: userOptions, isLoading: isSearchingUsers, search: searchUsers, clear: clearUserSearch } = useAsyncSearch({
        routeName: 'admin.users.search',
        transformItem: transformUser,
    });

    const subscribeForm = useSubscribeForm({
        onSuccess: () => {
            router.reload();
            handleClose();
        },
    });

    const requestOptions = useMemo(() => requests, [requests]);

    const handleUserChange = (option: SelectOption) => {
        setSelectedUser(option);
        subscribeForm.handleFieldChange('user_id', option?.value ?? null);
    };

    const handleRequestChange = (option: SelectOption) => {
        setSelectedRequest(option);
        subscribeForm.handleFieldChange('request_id', option?.value ?? null);
    };

    const handleClose = () => {
        subscribeForm.form.reset();
        setSelectedUser(undefined);
        setSelectedRequest(undefined);
        clearUserSearch();
        onClose();
    };

    return (
        <Dialog open={isOpen} onClose={handleClose}>
            <DialogTitle>Subscribe User to Request</DialogTitle>
            <DialogDescription>
                Subscribe a user to receive updates about a specific capacity development request.
            </DialogDescription>
            <DialogBody>
                <div className="space-y-4">
                    <Field>
                        <Label>User</Label>
                        <Combobox
                            value={selectedUser}
                            onChange={handleUserChange}
                            displayValue={(val: SelectOption | null) => val?.label ?? ''}
                            placeholder="Search users by name or email..."
                            options={userOptions}
                            filter={() => true}
                            onInputChange={searchUsers}
                            portal={true}
                        >
                            {(option: SelectOption) => (
                                <ComboboxOption key={option.value} value={option}>
                                    <ComboboxLabel>{option.label}</ComboboxLabel>
                                </ComboboxOption>
                            )}
                        </Combobox>
                        {isSearchingUsers && (
                            <Text className="text-xs text-zinc-500 mt-1">Searching...</Text>
                        )}
                        {subscribeForm.form.errors.user_id && (
                            <Text className="text-sm text-red-600 mt-1">
                                {subscribeForm.form.errors.user_id}
                            </Text>
                        )}
                    </Field>

                    <Field>
                        <Label>Request</Label>
                        <Combobox
                            value={selectedRequest}
                            onChange={handleRequestChange}
                            displayValue={(val: SelectOption | null) => val?.label ?? ''}
                            placeholder="Select a request..."
                            options={requestOptions}
                            portal={true}
                        >
                            {(option: SelectOption) => (
                                <ComboboxOption key={option.value} value={option}>
                                    <ComboboxLabel>{option.label}</ComboboxLabel>
                                </ComboboxOption>
                            )}
                        </Combobox>
                        {subscribeForm.form.errors.request_id && (
                            <Text className="text-sm text-red-600 mt-1">
                                {subscribeForm.form.errors.request_id}
                            </Text>
                        )}
                    </Field>

                    {(subscribeForm.form.errors as Record<string, string>).general && (
                        <Text className="text-red-600 text-sm">
                            {(subscribeForm.form.errors as Record<string, string>).general}
                        </Text>
                    )}
                </div>
            </DialogBody>
            <DialogActions>
                <Button
                    plain
                    onClick={handleClose}
                    disabled={subscribeForm.form.processing}
                >
                    Cancel
                </Button>
                <Button
                    onClick={subscribeForm.handleSubmit}
                    disabled={
                        subscribeForm.form.processing ||
                        !subscribeForm.form.data.user_id ||
                        !subscribeForm.form.data.request_id
                    }
                >
                    {subscribeForm.form.processing ? 'Creating...' : 'Create Subscription'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
