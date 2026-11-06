import React from 'react';
import { Dialog, DialogActions, DialogBody, DialogDescription, DialogTitle } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { FieldRenderer } from '@ui/organisms/forms';
import { XMarkIcon, LockClosedIcon } from '@heroicons/react/20/solid';
import { OpportunityFormOptions } from '@features/opportunities/types';
import { useODCTravelSupportForm } from '@features/odc-travel-support';

interface ODCTravelSupportUploadDialogProps {
    open: boolean;
    onClose: () => void;
    formOptions?: OpportunityFormOptions;
}

export default function ODCTravelSupportUploadDialog({ open, onClose, formOptions }: Readonly<ODCTravelSupportUploadDialogProps>) {
    const { form, fields, handleFieldChange, handleSubmit } = useODCTravelSupportForm({ formOptions, onSuccess: onClose });

    return (
        <Dialog size="3xl" open={open} onClose={onClose} closeOnBackdropClick={false}>
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-medium text-firefly-700 dark:text-firefly-300">2027 Ocean Decade Conference (ODC)</p>
                    <DialogTitle>Upload a 2027 ODC travel support opportunity</DialogTitle>
                </div>
                <Button
                    plain
                    onClick={onClose}
                    className="-m-1.5 rounded-md p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-firefly-500 dark:hover:bg-white/5"
                    aria-label="Close dialog"
                >
                    <XMarkIcon data-slot="icon" className="size-5" aria-hidden="true" />
                </Button>
            </div>

            <form onSubmit={handleSubmit} noValidate>
                <DialogBody className="space-y-5">
                    <div className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800/60">
                        <div className="flex items-center gap-3">
                            <LockClosedIcon className="size-4 shrink-0 text-gray-400" aria-hidden="true" />
                            <span className="text-gray-500 dark:text-gray-400">Type of opportunity</span>
                            <span className="font-medium text-gray-900 dark:text-gray-100">2027 ODC Travel Support</span>
                        </div>
                        {form.errors.type && (
                            <p className="mt-1.5 text-xs text-red-600 dark:text-red-400">{form.errors.type}</p>
                        )}
                    </div>

                    {fields.map(field => (
                        <FieldRenderer
                            key={field.id}
                            name={field.id}
                            field={field}
                            value={form.data[field.id as keyof typeof form.data]}
                            error={form.errors[field.id as keyof typeof form.errors]}
                            onChange={handleFieldChange}
                            formData={form.data}
                        />
                    ))}
                </DialogBody>

                <DialogActions className="items-center justify-between">
                    <span className="text-xs text-gray-500 dark:text-gray-400">No partner registration needed.</span>
                    <div className="flex gap-2">
                        <Button type="button" outline onClick={onClose} disabled={form.processing}>
                            Cancel
                        </Button>
                        <Button type="submit" color="firefly" disabled={form.processing}>
                            {form.processing ? 'Submitting...' : 'Submit opportunity'}
                        </Button>
                    </div>
                </DialogActions>
            </form>
        </Dialog>
    );
}
