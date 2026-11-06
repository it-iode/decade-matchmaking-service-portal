import {useForm} from '@inertiajs/react';
import {useEffect, useState} from 'react';
import { requestFormFields } from '../config';
import {OCDRequest} from '../types/request.types';

type Mode = 'submit' | 'draft';


export function useRequestForm(request?: OCDRequest) {

    const form = useForm({
        id: '',
        is_related_decade_action: '',
        unique_related_decade_action_id: '',
        first_name: '',
        last_name: '',
        email: '',
        capacity_development_title: '',
        has_significant_changes: '',
        changes_description: '',
        change_effect: '',
        request_link_type: '',
        project_stage: '',
        project_url: '',
        related_activity: '',
        decade_challenges: {primary: null, secondary: null, tertiary: null} as {
            primary: string | null;
            secondary: string | null;
            tertiary: string | null;
        },
        support_types: [] ,
        support_types_other: '',
        gap_description: '',
        has_partner: '',
        partner_name: '',
        partner_confirmed: '',
        needs_financial_support: '',
        budget_breakdown: '',
        support_months: '',
        completion_date: '',
        risks: '',
        personnel_expertise: '',
        direct_beneficiaries: '',
        direct_beneficiaries_number: '',
        expected_outcomes: '',
        success_metrics: '',
        long_term_impact: '',
        mode: 'submit' as Mode,
        target_audience: [] ,
        target_audience_other: '',
        target_languages: [] ,
        target_languages_other: '',
        delivery_format: '',
        delivery_countries: [] as string[],
    });

    const [step, setStep] = useState(1);
    const steps = requestFormFields.map((s) => s.label);
    const [errorSteps, setErrorSteps] = useState<number[]>([]);

    const handleNext = () => setStep(prev => Math.min(prev + 1, steps.length));
    const handleBack = () => setStep(prev => Math.max(prev - 1, 1));

    const handleSubmit = (mode: Mode) => {
        form.clearErrors();
        setErrorSteps([]);
        form.transform((data) => ({
            ...data,
            mode: mode,
        }));
        form.post(route('request.submit',{'id':request?.id}), {
            onSuccess: (page) => {
                if (mode === 'draft') {
                    let editId: string = '';
                } else {
                    // Optionally handle redirect for submit
                }
            },
            onError: (errors) => {
                const stepsWithError: number[] = [];
                Object.keys(errors).forEach(field => {
                    const idx = requestFormFields.findIndex(step => step.fields[field]);
                    if (idx !== -1 && !stepsWithError.includes(idx + 1)) {
                        stepsWithError.push(idx + 1);
                    }
                });
                setErrorSteps(stepsWithError);
            },
            onFinish: () => {
            }
        });
    };

    type FormDataKeys = keyof typeof form.data;

    useEffect(() => {
        if (request?.id) {
            form.setData('id', request.id.toString());
            Object.entries(request.detail).forEach(([key, value]) => {
                if (key in form.data && key !== 'id') {
                    // Handle array fields that come as objects with value/label pairs
                    if (Array.isArray(value) && ['support_types', 'target_audience', 'target_languages'].includes(key)) {
                        // Convert array of objects to array of values
                        const arrayValues = value.map(item =>
                            typeof item === 'object' && item !== null && 'value' in item
                                ? item.value
                                : item
                        ).filter(Boolean);
                        form.setData(key as FormDataKeys, arrayValues);
                    }
                    // Handle delivery_countries specifically as it expects string[]
                    else if (key === 'delivery_countries' && Array.isArray(value)) {
                        const countryValues = value.map(item =>
                            typeof item === 'object' && item !== null && 'value' in item
                                ? item.value
                                : String(item)
                        ).filter(Boolean);
                        form.setData(key as FormDataKeys, countryValues);
                    }
                    // Handle the ranked decade_challenges object (read shape -> form write shape)
                    else if (key === 'decade_challenges' && value && typeof value === 'object' && !Array.isArray(value)) {
                        const source = value as Record<'primary' | 'secondary' | 'tertiary', unknown>;
                        const pick = (rank: 'primary' | 'secondary' | 'tertiary'): string | null => {
                            const entry = source[rank];
                            if (entry && typeof entry === 'object' && 'value' in entry) {
                                return String((entry as {value: unknown}).value);
                            }
                            return typeof entry === 'string' && entry.length > 0 ? entry : null;
                        };
                        form.setData('decade_challenges', {
                            primary: pick('primary'),
                            secondary: pick('secondary'),
                            tertiary: pick('tertiary'),
                        });
                    }
                    // Handle primitive values (strings, numbers, booleans)
                    else if (value !== null && value !== undefined) {
                        // For non-array form fields, convert to string
                        // Skip arrays that weren't handled above to avoid type errors
                        if (!Array.isArray(value)) {
                            form.setData(key as FormDataKeys, String(value) || '');
                        }
                    }
                    // Fallback for null/undefined values
                    else {
                        form.setData(key as FormDataKeys, '');
                    }
                }
            });
        }
    }, []);

    const handleFieldChange = (name: string, value: any) => {
        form.setData(name as keyof typeof form.data, value);
    };

    return {
        form,
        step,
        steps,
        errorSteps,
        handleNext,
        handleBack,
        handleSubmit,
        handleFieldChange,
        setStep,
    };
}
