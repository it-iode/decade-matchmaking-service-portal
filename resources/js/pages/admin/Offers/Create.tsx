import React from 'react';
import {Head} from '@inertiajs/react';
import {SidebarLayout} from '@layouts/index';
import {RequestOffer} from '@/types';
import {useOfferForm} from '@features/offers/hooks';
import {offerFormFields} from '@features/offers/config';
import {PageHeader} from "@ui/molecules/page-header";
import {Button} from '@ui/primitives/button';
import {ChevronLeftIcon} from '@heroicons/react/16/solid';
import {FieldRenderer} from '@ui/organisms/forms';
import {Fieldset, Legend} from "@ui/primitives/fieldset";

function getOptionsKey(fieldKey: string): string | null {
    const keyMap: Record<string, string> = {
        'request_id': 'availableRequests',
        'partner_id': 'partners'
    };
    return keyMap[fieldKey] || null;
}

export interface OfferFormOptions {
    availableRequests: Array<{ value: string; label: string }>;
    partners: Array<{ value: number; label: string }>;
}

interface CreateOfferPageProps {
    offer: RequestOffer;
    formOptions?: OfferFormOptions
}

export default function CreateOffer({
                                        offer,
                                        formOptions,
                                    }: Readonly<CreateOfferPageProps>) {
    const isEditing = Boolean(offer?.id);

    const {
        form,
        step,
        handleSubmit,
        handleFieldChange,
    } = useOfferForm({
        partners: formOptions?.partners || [],
        availableRequests: formOptions?.availableRequests || [],
        offer: offer,
        isEditing: isEditing,
    });
    const currentStepData = offerFormFields[step - 1];
    const validateCurrentStep = (): boolean => {
        const requiredFields = Object.entries(currentStepData.fields)
            .filter(([_, field]) => field.required)
            .map(([name, _]) => name);

        return requiredFields.every(fieldName => {
            const value = form.data[fieldName as keyof typeof form.data];
            if (Array.isArray(value)) {
                return value.length > 0;
            }
            return value !== null && value !== undefined && value !== '';
        });
    };

    const canProceed = validateCurrentStep();

    const handleFormSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        handleSubmit();
    };

    return (
        <SidebarLayout>
            <Head title={offer.id ? 'Edit Offer #' + offer.id : 'Create a new Offer'}/>

            <div className="space-y-6">
                <PageHeader
                    title={offer.id ? `Edit Offer #${offer.id}` : 'Create a new Offer'}
                />

                {/* Form */}
                <div>
                    <form onSubmit={handleFormSubmit} className="space-y-6">
                        <Fieldset>
                            <Legend>
                                {currentStepData.label}
                            </Legend>

                            <div className="space-y-6">
                                {Object.entries(currentStepData.fields).map(([fieldName, field]) => {
                                    // Check if there are formOptions for this field key and assign them
                                    const fieldWithOptions = {...field};

                                    // Map field keys to formOptions keys
                                    const optionsKey = getOptionsKey(fieldName);
                                    if (optionsKey && Array.isArray(formOptions?.[optionsKey as keyof OfferFormOptions])) {
                                        fieldWithOptions.options = formOptions[optionsKey as keyof OfferFormOptions] as Array<{
                                            value: string | number;
                                            label: string
                                        }>;
                                    }

                                    return (
                                        <FieldRenderer
                                            key={fieldName}
                                            name={fieldName}
                                            field={fieldWithOptions}
                                            value={form.data[fieldName as keyof typeof form.data]}
                                            error={form.errors[fieldName as keyof typeof form.errors]}
                                            onChange={handleFieldChange}
                                            formData={form.data}
                                        />

                                    );
                                })}
                            </div>
                        </Fieldset>

                        {/* Navigation buttons */}
                        <div className="flex items-center justify-between">
                            <div>
                                <Button
                                    type="button"
                                    outline
                                    href={route('admin.offer.list')}
                                >
                                    <ChevronLeftIcon data-slot="icon"/>
                                    Back to Offers
                                </Button>
                            </div>

                            <div className="flex items-center gap-3">
                                <Button
                                    type="submit"
                                    disabled={form.processing || !canProceed}
                                    className={!canProceed ? 'opacity-50 cursor-not-allowed' : ''}
                                >
                                    {(() => {
                                        if (form.processing) return 'Saving...';
                                        return isEditing ? 'Update Offer' : 'Create Offer';
                                    })()}
                                </Button>
                            </div>
                        </div>
                    </form>

                    {/* Global errors */}
                    {Object.keys(form.errors).length > 0 && (
                        <div className="mt-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                            <div className="text-sm text-red-800 dark:text-red-200">
                                <strong>Please correct the following errors:</strong>
                                <ul className="mt-2 list-disc list-inside space-y-1">
                                    {Object.entries(form.errors).map(([field, message]) => (
                                        <li key={field}>{message}</li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </SidebarLayout>
    );
}
