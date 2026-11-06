import React, {useState, useEffect} from 'react';
import {Dialog, DialogTitle, DialogDescription, DialogBody, DialogActions} from '@ui/primitives/dialog';
import {Button} from '@ui/primitives/button';
import {Field, Label} from '@ui/primitives/fieldset';
import {Checkbox, CheckboxField} from '@ui/primitives/checkbox';
import {UserManagement, RoleOption} from '@/types';
import {router} from '@inertiajs/react';

interface UserRoleDialogProps {
    isOpen: boolean;
    onClose: () => void;
    user: UserManagement | null;
    availableRoles: RoleOption[];
}

export function UserRoleDialog({isOpen, onClose, user, availableRoles}: Readonly<UserRoleDialogProps>) {
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (user) {
            setSelectedRoles(user.roles.map(r => r.name));
        }
    }, [user]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!user) return;

        setProcessing(true);
        router.post(
            route('users.roles.assign', {user: user.id}),
            {roles: selectedRoles},
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

    const toggleRole = (roleName: string) => {
        setSelectedRoles(prev =>
            prev.includes(roleName)
                ? prev.filter(r => r !== roleName)
                : [...prev, roleName]
        );
    };

    return (
        <Dialog open={isOpen} onClose={onClose}>
            <DialogTitle>Assign Roles</DialogTitle>
            <DialogDescription>
                Manage roles for {user?.name}
            </DialogDescription>
            <DialogBody>
                <form onSubmit={handleSubmit} id="role-form">
                    <Field>
                        <Label>Select Roles</Label>
                        <div className="space-y-2 mt-2">
                            {availableRoles.map((role) => (
                                <CheckboxField key={role.id}>
                                    <Checkbox
                                        checked={selectedRoles.includes(role.name)}
                                        onChange={() => toggleRole(role.name)}
                                    />
                                    <Label>{role.label}</Label>
                                </CheckboxField>
                            ))}
                        </div>
                    </Field>
                </form>
            </DialogBody>
            <DialogActions>
                <Button plain onClick={onClose}>
                    Cancel
                </Button>
                <Button type="submit" form="role-form" disabled={processing}>
                    {processing ? 'Saving...' : 'Save Roles'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
