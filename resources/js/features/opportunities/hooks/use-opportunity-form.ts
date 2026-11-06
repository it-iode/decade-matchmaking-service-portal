import {useForm} from '@inertiajs/react';
import {useEffect, useState, useRef} from 'react';
import { opportunityFormFields } from '../config';
import {Opportunity, OpportunityFormOptions} from '../types/opportunity.types';

export function useOpportunityForm(opportunity?: Opportunity, formOptions?: OpportunityFormOptions) {
    const form = useForm({
        id: opportunity?.id || '',
        co_organizers: opportunity?.co_organizers || '',
        title: opportunity?.title || '',
        type: opportunity?.type?.value || '',
        closing_date: opportunity?.closing_date || '',
        coverage_activity: opportunity?.coverage_activity?.value || '',
        implementation_location: opportunity?.implementation_location.map((item, index) => item.value) || '',
        thematic_areas: opportunity?.thematic_areas?.map((item, index) => item.value) || [],
        thematic_areas_other: opportunity?.thematic_areas_other || '',
        target_audience: opportunity?.target_audience?.map((item, index) => item.value) || [],
        target_audience_other: opportunity?.target_audience_other || '',
        target_languages: opportunity?.target_languages?.map((item, index) => item.value) || [],
        target_languages_other: opportunity?.target_audience_other || '',
        summary: opportunity?.summary || '',
        url: opportunity?.url || '',
        key_words: opportunity?.key_words?.map((item, index) => item) || '',
    });

    const [currentStep, setCurrentStep] = useState(0);
    const steps = opportunityFormFields.map((s) => s.label);
    const [errorSteps, setErrorSteps] = useState<number[]>([]);
    const [implementationOptions, setImplementationOptions] = useState<{ value: string; label: string }[]>([]);
    const previousCoverageActivity = useRef(form.data.coverage_activity);

    // Handle implementation location options based on coverage activity
    useEffect(() => {
        if (!formOptions) return;

        const coverageActivityChanged = previousCoverageActivity.current !== form.data.coverage_activity;

        switch (form.data.coverage_activity) {
            case 'country':
                setImplementationOptions(formOptions.countries ?? []);
                break;
            case 'regions':
                setImplementationOptions(formOptions.regions ?? []);
                break;
            case 'ocean-based':
                setImplementationOptions(formOptions.oceans ?? []);
                break;
            case 'global':
                setImplementationOptions([{value: 'Global', label: 'Global'}]);
                break;
            default:
                setImplementationOptions([]);
        }

        // Clear implementation location when:
        // 1. In create mode (no opportunity exists), OR
        // 2. Coverage activity has actually changed in edit mode
        if (!opportunity || coverageActivityChanged) {
            form.setData('implementation_location', [] as any);
        }

        // Update the ref to track the current value
        previousCoverageActivity.current = form.data.coverage_activity;
    }, [form.data.coverage_activity, formOptions]);

    const handleSubmit = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        form.clearErrors();
        setErrorSteps([]);
        form.post(route('opportunity.submit', {'id': opportunity?.id}), {
            preserveScroll: false,
            onSuccess: () => {
                // Handle success - could redirect or show success message
            },
            onError: (errors) => {
                const stepsWithError: number[] = [];
                Object.keys(errors).forEach(field => {
                    const idx = opportunityFormFields.findIndex(step => step.fields[field]);
                    if (idx !== -1 && !stepsWithError.includes(idx)) {
                        stepsWithError.push(idx);
                    }
                });
                setErrorSteps(stepsWithError);

                // Jump to first step with error
                if (stepsWithError.length > 0) {
                    setCurrentStep(stepsWithError[0]);
                }
            },
        });
    };

    const handleFieldChange = (name: string, value: any) => {
        form.setData(name as keyof typeof form.data, value);
    };

    return {
        form,
        currentStep,
        steps,
        errorSteps,
        implementationOptions,
        handleSubmit,
        handleFieldChange,
        setCurrentStep
    };
}
