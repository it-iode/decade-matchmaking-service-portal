import React, { useState, useCallback } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@ui/primitives/button';
import { ChevronLeftIcon } from '@heroicons/react/16/solid';
import { FieldRenderer } from '@ui/organisms/forms';
import { UIField } from '@/types';
import {Fieldset,Legend} from "@ui/primitives/fieldset";

export interface UIStep {
    label: string;
    fields: Record<string, UIField>;
}

interface FormProviderProps {
    steps: UIStep[];
    initialData: Record<string, any>;
    submitUrl: string;
    method?: 'POST' | 'PUT' | 'PATCH';
    backUrl?: string;
    backLabel?: string;
    submitLabel?: string;
    dynamicOptions?: Record<string, Array<{ value: string; label: string }>>;
}

export function FormProvider({
    steps,
    initialData,
    submitUrl,
    method = 'POST',
    backUrl,
    backLabel = 'Back',
    submitLabel = 'Save',
    dynamicOptions = {}
}: Readonly<FormProviderProps>) {
    const [currentStep, setCurrentStep] = useState(0);

    const { data, setData, post, put, patch, processing, errors, reset } = useForm(initialData);

    const currentStepData = steps[currentStep];
    const isLastStep = currentStep === steps.length - 1;
    const isFirstStep = currentStep === 0;

    // Populate dynamic options into fields
    const populateFieldOptions = (field: UIField, fieldName: string): UIField => {
        if (dynamicOptions[fieldName]) {
            return {
                ...field,
                options: dynamicOptions[fieldName]
            };
        }
        return field;
    };

    const handleFieldChange = useCallback((name: string, value: any) => {
        setData(name, value);
    }, [setData]);

    const handleNext = () => {
        if (!isLastStep) {
            setCurrentStep(prev => prev + 1);
        }
    };

    const handlePrevious = () => {
        if (!isFirstStep) {
            setCurrentStep(prev => prev - 1);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const submitMethod = method === 'POST' ? post : method === 'PUT' ? put : patch;
        submitMethod(submitUrl, {
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            }
        });
    };

    const validateCurrentStep = (): boolean => {
        const requiredFields = Object.entries(currentStepData.fields)
            .filter(([_, field]) => field.required)
            .map(([name, _]) => name);

        return requiredFields.every(fieldName => {
            const value = data[fieldName];
            if (Array.isArray(value)) {
                return value.length > 0;
            }
            return value !== null && value !== undefined && value !== '';
        });
    };

    const canProceed = validateCurrentStep();

    return (
        <div>
            <form onSubmit={handleSubmit} className="space-y-6">
                <Fieldset>
                   <Legend>
                   {currentStepData.label}
                   </Legend>

                    <div className="space-y-6">
                        {Object.entries(currentStepData.fields).map(([fieldName, field]) => {
                            const populatedField = populateFieldOptions(field, fieldName);

                            return (
                                <FieldRenderer
                                    key={fieldName}
                                    name={fieldName}
                                    field={populatedField}
                                    value={data[fieldName]}
                                    error={errors[fieldName]}
                                    onChange={handleFieldChange}
                                    formData={data}
                                />
                            );
                        })}
                    </div>
                </Fieldset>

                {/* Navigation buttons */}
                <div className="flex items-center justify-between">
                    <div>
                        {backUrl && (
                            <Button
                                type="button"
                                outline
                                href={backUrl}
                            >
                                <ChevronLeftIcon data-slot="icon" />
                                {backLabel}
                            </Button>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Previous Step Button */}
                        {!isFirstStep && (
                            <Button
                                type="button"
                                outline
                                onClick={handlePrevious}
                            >
                                Previous
                            </Button>
                        )}

                        {/* Next Step or Submit Button */}
                        {isLastStep ? (
                            <Button
                                type="submit"
                                disabled={processing || !canProceed}
                                className={!canProceed ? 'opacity-50 cursor-not-allowed' : ''}
                            >
                                {processing ? 'Saving...' : submitLabel}
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                onClick={handleNext}
                                disabled={!canProceed}
                                className={!canProceed ? 'opacity-50 cursor-not-allowed' : ''}
                            >
                                Next
                            </Button>
                        )}
                    </div>
                </div>
            </form>

            {/* Global errors */}
            {Object.keys(errors).length > 0 && (
                <div className="mt-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                    <div className="text-sm text-red-800 dark:text-red-200">
                        <strong>Please correct the following errors:</strong>
                        <ul className="mt-2 list-disc list-inside space-y-1">
                            {Object.entries(errors).map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}
        </div>
    );
}
