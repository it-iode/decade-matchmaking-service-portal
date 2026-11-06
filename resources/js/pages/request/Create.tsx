import { FrontendLayout } from '@layouts/index';
import React from 'react';
import {Head} from '@inertiajs/react';
import {OCDRequest} from '@/types';
import { requestFormFields } from '@features/requests/config';
import { FieldRenderer } from '@ui/organisms/forms';
import {useRequestForm} from '@features/requests/hooks';


interface RequestFormOptions {
    delivery_countries?: Array<{ value: string; label: string }>;
    regions?: Array<{ value: string; label: string }>;
    oceans?: Array<{ value: string; label: string }>;
    decade_challenges?: Array<{ value: string; label: string }>;
    support_types?: Array<{ value: string; label: string }>;
    target_audience?: Array<{ value: string; label: string }>;
    target_languages?: Array<{ value: string; label: string }>;
    delivery_format?: Array<{ value: string; label: string }>;
    opportunity_types?: Array<{ value: string; label: string }>;
    related_activity?: Array<{ value: string; label: string }>;
    yes_no?: Array<{ value: string; label: string }>;
    project_stage?: Array<{ value: string; label: string }>;
}

type Mode = 'submit' | 'draft';
type Id = '';
// Helper function to map field keys to formOptions keys
function getOptionsKey(fieldKey: string): string | null {
    const keyMap: Record<string, string> = {
        'delivery_countries': 'delivery_countries',
        'decade_challenges': 'decade_challenges',
        'support_types': 'support_types',
        'target_audience': 'target_audience',
        'target_languages': 'target_languages',
        'delivery_format': 'delivery_format',
        'related_activity': 'related_activity',
        'is_related_decade_action': 'yes_no',
        'has_significant_changes': 'yes_no',
        'has_partner': 'yes_no',
        'partner_confirmed': 'yes_no',
        'needs_financial_support': 'yes_no',
        'request_link_type': 'yes_no',
        'project_stage': 'project_stage'
    };
    return keyMap[fieldKey] || null;
}

type RequestFormProps = {
    formOptions: RequestFormOptions;
    request?: OCDRequest;
}

export default function RequestForm({request, formOptions}: Readonly<RequestFormProps>) {
    const {
        form,
        step,
        steps,
        errorSteps,
        handleNext,
        handleBack,
        handleSubmit,
        handleFieldChange,
        setStep
    } = useRequestForm(request);

    return (
        <FrontendLayout>
            <Head title="Submit Request"/>
            <form className="mx-auto">
                <input type="hidden" name="id" value={form.data.id}/>
                <input type="hidden" name="mode" value={form.data.mode}/>
                {/* Stepper */}
                <div className="flex mb-6">
                    {steps.map((label, idx) => (
                        <div key={label} className="flex-1" onClick={() => {
                            setStep(idx + 1)
                        }}>
                            <div
                                className={`w-8 h-8 mx-auto rounded-full text-center leading-8 ${step === idx + 1 ? 'bg-firefly-600 text-white dark:bg-firefly-500' : 'bg-firefly-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                                } ${errorSteps.includes(idx + 1) && step !== idx + 1 ? 'bg-red-600 text-white dark:bg-red-500' : ''}`}
                            >
                                {idx + 1}
                            </div>
                            <div
                                className={`text-xl text-center mt-2 text-gray-900 dark:text-gray-100 ${errorSteps.includes(idx + 1) && step !== idx + 1 ? 'text-red-600 dark:text-red-400' : ''}`}>{label}</div>
                        </div>
                    ))}
                </div>
                {Object.entries(requestFormFields[step - 1].fields).map(([key, field]) => {
                    type FormDataKeys = keyof typeof form.data;
                    // Check if there are formOptions for this field key and assign them
                    const fieldWithOptions = {...field};

                    // Map field keys to formOptions keys
                    const optionsKey = getOptionsKey(key);
                    if (optionsKey && formOptions?.[optionsKey as keyof RequestFormOptions]) {
                        fieldWithOptions.options = formOptions[optionsKey as keyof RequestFormOptions];
                    }

                    return (
                        <FieldRenderer
                            key={key}
                            name={key}
                            field={fieldWithOptions}
                            value={(form.data as any)[key as FormDataKeys]}
                            error={form.errors[key as FormDataKeys]}
                            onChange={handleFieldChange}
                            formData={form.data}
                        />
                    );
                })}

                {/* Navigation Buttons */}
                <div className="flex justify-between mt-6">
                    <button
                        type="button"
                        onClick={handleBack}
                        disabled={step === 1}
                        className="px-4 py-2 bg-firefly-600 text-white rounded hover:bg-firefly-700 dark:bg-firefly-500 dark:hover:bg-firefly-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Back
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            handleSubmit('draft');
                        }}
                        disabled={form.processing}
                        className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {form.processing? 'Saving...' : 'Save Draft'}
                    </button>
                    {step < 5 ? (
                        <button
                            type="button"
                            onClick={handleNext}
                            className="px-4 py-2 bg-firefly-600 text-white rounded hover:bg-firefly-700 dark:bg-firefly-500 dark:hover:bg-firefly-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={form.processing}
                        >
                            Next
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={() => {
                                handleSubmit('submit');
                            }}
                            disabled={form.processing}
                            className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {form.processing ? 'Submitting...' : 'Submit'}
                        </button>
                    )}
                </div>
            </form>
        </FrontendLayout>
    );
}
