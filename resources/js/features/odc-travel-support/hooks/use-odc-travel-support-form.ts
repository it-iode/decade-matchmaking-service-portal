import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import { OptionDetail } from '@/types';
import { OpportunityFormOptions } from '@features/opportunities/types';
import { ODC_TRAVEL_SUPPORT_TYPE, odcTravelSupportFormFields } from '../config/odc-travel-support-form-fields';
import { TARGET_AUDIENCE_DETAILS } from '../config/target-audience-details';

export type ODCTravelSupportFormData = {
    co_organizers: string[];
    title: string;
    type: string;
    closing_date: string;
    target_audience: string[];
    target_audience_other: string;
    summary: string;
    url: string;
    key_words: string[];
};

interface UseODCTravelSupportFormOptions {
    formOptions?: OpportunityFormOptions;
    onSuccess?: () => void;
}

export function useODCTravelSupportForm({ formOptions, onSuccess }: UseODCTravelSupportFormOptions = {}) {
    const form = useForm<ODCTravelSupportFormData>({
        co_organizers: [],
        title: '',
        type: ODC_TRAVEL_SUPPORT_TYPE,
        closing_date: '',
        target_audience: [],
        target_audience_other: '',
        summary: '',
        url: '',
        key_words: [],
    });

    const optionsByField = useMemo<Record<string, { value: string | number; label: string; detail?: OptionDetail }[]>>(() => ({
        target_audience: (formOptions?.target_audience ?? []).map(option => ({
            ...option,
            detail: TARGET_AUDIENCE_DETAILS[String(option.value)],
        })),
    }), [formOptions]);

    const fields = useMemo(
        () => odcTravelSupportFormFields.map(field => ({
            ...field,
            options: optionsByField[field.id] ?? field.options,
        })),
        [optionsByField]
    );

    const handleFieldChange = (name: string, value: unknown) => {
        form.setData(name as keyof ODCTravelSupportFormData, value as never);
    };

    const handleSubmit = (e?: React.FormEvent) => {
        e?.preventDefault();
        form.clearErrors();
        form.post(route('opportunity.odc-travel-support.submit'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onSuccess?.();
            },
        });
    };

    return { form, fields, handleFieldChange, handleSubmit };
}
