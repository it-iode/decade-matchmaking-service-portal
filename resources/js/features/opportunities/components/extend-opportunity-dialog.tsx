import React from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogBody, DialogDescription, DialogTitle } from '@ui/primitives/dialog';
import { Field, Label } from '@ui/primitives/fieldset';
import { Input } from '@ui/primitives/input';
import { Button } from '@ui/primitives/button';
import { Opportunity } from '../types/opportunity.types';

interface ExtendOpportunityDialogProps {
    isOpen: boolean;
    onClose: () => void;
    opportunity: Opportunity;
}

export default function ExtendOpportunityDialog({ 
    isOpen, 
    onClose, 
    opportunity 
}: ExtendOpportunityDialogProps) {
    const { data, setData, post, processing, errors } = useForm({
        closing_date: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('opportunity.extend', opportunity.id), {
            onSuccess: () => {
                onClose();
                setData('closing_date', '');
            },
            onError: () => {
                // Errors are handled by the errors object
            }
        });
    };

    const getTomorrowDate = () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    };

    return (
        <Dialog open={isOpen} onClose={onClose}>
            <DialogTitle>Extend Opportunity Closing Date</DialogTitle>
            <DialogDescription>
                Extend the closing date for: <strong>{opportunity.title}</strong>
            </DialogDescription>
            <DialogBody>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Field>
                        <Label>Current Closing Date</Label>
                        <div className="mt-1 text-sm text-gray-700 p-2 bg-gray-50 rounded-md">
                            {new Date(opportunity.closing_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            })}
                        </div>
                    </Field>
                    
                    <Field>
                        <Label htmlFor="closing_date">New Closing Date</Label>
                        <Input
                            id="closing_date"
                            type="date"
                            value={data.closing_date}
                            onChange={(e) => setData('closing_date', e.target.value)}
                            min={getTomorrowDate()}
                            required
                            className="mt-1"
                        />
                        {errors.closing_date && (
                            <div className="mt-1 text-sm text-red-600">
                                {errors.closing_date}
                            </div>
                        )}
                    </Field>
                    
                    <div className="flex justify-end gap-3 pt-4">
                        <Button 
                            type="button" 
                            outline 
                            onClick={onClose}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={processing}
                        >
                            {processing ? 'Extending...' : 'Extend Closing Date'}
                        </Button>
                    </div>
                </form>
            </DialogBody>
        </Dialog>
    );
}