import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
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

const EMPTY_FORM_DATA: ODCTravelSupportFormData = {
    co_organizers: [],
    title: '',
    type: ODC_TRAVEL_SUPPORT_TYPE,
    closing_date: '',
    target_audience: [],
    target_audience_other: '',
    summary: '',
    url: '',
    key_words: [],
};

/**
 * Restore a draft kept in localStorage. Signing in remounts the submission
 * page (password: redirect visit; OAuth: full navigation), which would lose
 * unsubmitted entries without it — and the draft also survives closing the
 * tab. Storage is treated as untrusted: only values with the expected shape
 * are kept, and `type` is always forced.
 */
function readDraft(key: string): ODCTravelSupportFormData {
    try {
        const parsed: unknown = JSON.parse(window.localStorage.getItem(key) ?? 'null');
        if (typeof parsed !== 'object' || parsed === null) {
            return EMPTY_FORM_DATA;
        }
        const draft = parsed as Record<string, unknown>;
        const text = (value: unknown): string => (typeof value === 'string' ? value : '');
        const list = (value: unknown): string[] =>
            Array.isArray(value) ? value.filter((item): item is string => typeof item === 'string') : [];

        return {
            co_organizers: list(draft.co_organizers),
            title: text(draft.title),
            type: ODC_TRAVEL_SUPPORT_TYPE,
            closing_date: text(draft.closing_date),
            target_audience: list(draft.target_audience),
            target_audience_other: text(draft.target_audience_other),
            summary: text(draft.summary),
            url: text(draft.url),
            key_words: list(draft.key_words),
        };
    } catch {
        return EMPTY_FORM_DATA;
    }
}

function clearDraft(key: string): void {
    try {
        window.localStorage.removeItem(key);
    } catch {
        // Storage unavailable (private mode, blocked site data) — nothing to clear.
    }
}

interface UseODCTravelSupportFormOptions {
    formOptions?: OpportunityFormOptions;
    onSuccess?: () => void;
    /**
     * localStorage key for a per-device draft of the entries. Pass it on the
     * submission page so a guest who signs in before submitting gets their
     * entries back when the page remounts — or when they come back later.
     * Cleared on successful submit.
     */
    draftKey?: string;
}

export function useODCTravelSupportForm({ formOptions, onSuccess, draftKey }: UseODCTravelSupportFormOptions = {}) {
    // Read the draft once per mount, not on every render.
    const [initialData] = useState<ODCTravelSupportFormData>(() => (draftKey ? readDraft(draftKey) : EMPTY_FORM_DATA));

    const form = useForm<ODCTravelSupportFormData>(initialData);

    useEffect(() => {
        if (!draftKey) {
            return;
        }
        try {
            // An untouched form keeps no draft, so an abandoned empty draft
            // never comes back and clearing the form also clears the draft.
            if (JSON.stringify(form.data) === JSON.stringify(EMPTY_FORM_DATA)) {
                window.localStorage.removeItem(draftKey);
            } else {
                window.localStorage.setItem(draftKey, JSON.stringify(form.data));
            }
        } catch {
            // Storage unavailable — the page still works, entries just won't survive a remount.
        }
    }, [draftKey, form.data]);

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
            onSuccess: page => {
                // A redirect without validation errors is not proof the
                // opportunity was created (the server redirects with an error
                // flash on failure too) — only drop the entries on the
                // confirmed success flash.
                const flash = (page.props as { flash?: { success?: string | null } }).flash;
                if (!flash?.success) {
                    return;
                }
                form.setDefaults(EMPTY_FORM_DATA);
                form.reset();
                if (draftKey) {
                    clearDraft(draftKey);
                }
                onSuccess?.();
            },
        });
    };

    return { form, fields, handleFieldChange, handleSubmit };
}
