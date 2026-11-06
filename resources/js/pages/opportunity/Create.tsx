import {Head} from '@inertiajs/react';
import React from 'react';
import { FrontendLayout } from '@layouts/index';
import { opportunityFormFields } from '@features/opportunities/config';
import {Opportunity, OpportunityFormOptions} from '@/types';
import { FieldRenderer } from '@ui/organisms/forms';
import {useOpportunityForm} from '@features/opportunities/hooks';
import {Button} from '@ui/primitives/button';

interface CreateOpportunityProps {
    opportunity?: Opportunity;
    formOptions?: OpportunityFormOptions;
}

// Helper function to map opportunity field keys to formOptions keys
function getOptionsKeyForOpportunity(fieldKey: string): string | null {
    const keyMap: Record<string, string> = {
        'implementation_location': 'implementation_location', // This is handled dynamically in the hook
        'type': 'opportunity_types',
        'target_audience': 'target_audience',
        'coverage_activity': 'coverage_activity',
        'target_languages': 'target_languages',
        'thematic_areas': 'thematic_areas'
    };
    return keyMap[fieldKey] || null;
}

export default function CreateOpportunity({opportunity, formOptions}: Readonly<CreateOpportunityProps>) {
    const {
        form,
        currentStep,
        handleSubmit,
        handleFieldChange,
        implementationOptions,
    } = useOpportunityForm(opportunity, formOptions);


    return (
        <FrontendLayout>
            <Head title="Create Opportunity"/>
            <div>
                <form>
                    <input type="hidden" name="id" value={form.data.id}/>
                    {/* Current Step Fields */}
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{opportunityFormFields[currentStep].label}</h2>
                        {Object.entries(opportunityFormFields[currentStep].fields).map(([key, field]) => {
                            const fieldWithOptions = {...field};

                            // Assign dynamic options based on field type
                            if (key === 'implementation_location') {
                                // Use dynamic options from hook for implementation location
                                fieldWithOptions.options = implementationOptions;
                            } else {
                                const optionsKey = getOptionsKeyForOpportunity(key);
                                if (optionsKey) {
                                    fieldWithOptions.options = formOptions?.[optionsKey as keyof OpportunityFormOptions] || [];
                                }
                            }

                            type FormDataKeys = keyof typeof form.data;
                            return (
                                <FieldRenderer
                                    key={key}
                                    name={key}
                                    field={fieldWithOptions}
                                    value={form.data[key as FormDataKeys]}
                                    error={form.errors[key as FormDataKeys]}
                                    onChange={handleFieldChange}
                                    formData={form.data}
                                />
                            );
                        })}
                    </div>

                    {/* Navigation Buttons */}
                    <div className="flex justify-between mt-6">
                        <Button
                            type="submit"
                            disabled={form.processing}
                            onClick={() => {
                                handleSubmit();
                            }}
                            color={'firefly'}
                            className="px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {form.processing ? 'Submitting Opportunity...' : 'Submit Opportunity'}
                        </Button>
                    </div>
                </form>
            </div>
        </FrontendLayout>
    );
}
