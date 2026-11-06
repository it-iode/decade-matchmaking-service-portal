import {useForm} from '@inertiajs/react';
import {useEffect, useState, useRef} from 'react';
import { settingsFormFields } from '@features/settings/config';
import {Settings} from '@/types';

interface UseSettingsFormProps {
    settings?: Settings;
    isEditing?: boolean;
}

export function useSettingsForm({settings, isEditing = false}: UseSettingsFormProps) {
    // Initialize form data
    const initialFormData = {
        site_name: '',
        site_description: '',
        homepage_youtube_video: '',
        show_odc_travel_support_section: '1',
        organizations_csv: null,
        portal_guide: '',
        user_guide: '',
        partner_guide: '',
        successful_matches_metric: '',
        fully_closed_matches_metric: '',
        request_in_implementation_metric: '',
        committed_funding_metric: '',
        open_partner_opportunities_metric: '',
        mandrill_api_key:'',
        linkedin_client_id: '',
        linkedin_client_secret: '',
        google_client_id: '',
        google_client_secret: '',
    };

    const form = useForm(initialFormData);

    // Store initial values to track changes
    const initialValues = useRef(initialFormData);

    const [step, setStep] = useState(1);
    const steps = settingsFormFields.map((s) => s.label);
    const [errorSteps, setErrorSteps] = useState<number[]>([]);
    const [changedFields, setChangedFields] = useState<Set<string>>(new Set());

    const handleNext = () => setStep(prev => Math.min(prev + 1, steps.length));
    const handleBack = () => setStep(prev => Math.max(prev - 1, 1));

    const handleSubmit = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        form.clearErrors();
        setErrorSteps([]);
        
        const changedData = getChangedFieldsData();

        if (Object.keys(changedData).length === 0) {
            return;
        }

        const submitUrl = isEditing
            ? route('admin.settings.update')
            : route('admin.settings.store');

        // Store original form data temporarily
        const originalFormData = {...form.data};
        
        // Set form data to only changed fields for submission
        form.setData(changedData as any);
        
        // Direct submission - Inertia handles state preservation
        form.post(submitUrl, {
            preserveScroll: false,
            onSuccess: () => {
                // Simple success handling - Inertia preserves form state automatically
                setChangedFields(new Set());
                
                // Update initial values with the new values after successful submission
                Object.keys(changedData).forEach(key => {
                    (initialValues.current as any)[key] = changedData[key];
                });

                // Reset CSV file after successful upload and restore full form structure
                form.setData({
                    ...originalFormData,
                    ...changedData,
                    organizations_csv: null
                });
            },
            onError: (errors: any) => {
                // Restore original form data structure
                form.setData(originalFormData);
                
                // Simple error handling - Inertia preserves form state automatically
                // Just handle error steps
                const stepsWithError = processFormErrors(errors);
                setErrorSteps(stepsWithError);
                
                if (stepsWithError.length > 0) {
                    setStep(stepsWithError[0]);
                }
            }
        });
    };

    type FormDataKeys = keyof typeof form.data;

    useEffect(() => {
        if (settings) {
            // Update initial values and form data with existing settings
            const updatedData: any = {...initialFormData};

            Object.entries(settings).forEach(([key, value]) => {
                if (key in form.data && value !== null && value !== undefined) {
                    updatedData[key] = String(value) || '';
                }
            });

            // Update initial values reference
            initialValues.current = updatedData;

            // Update form data
            form.setData(updatedData);
        }
    }, [settings]);

    const handleFieldChange = (name: string, value: any) => {
        form.setData(name as FormDataKeys, value);

        // Track which fields have changed
        const newChangedFields = new Set(changedFields);
        if (hasFieldChanged(name, value)) {
            newChangedFields.add(name);
        } else {
            newChangedFields.delete(name);
        }
        setChangedFields(newChangedFields);
    };

    // Handle file uploads specifically
    const handleFileChange = (fieldName: string, file: File | null) => {
        handleFieldChange(fieldName, file);
    };

    // Function to detect if a field has changed
    const hasFieldChanged = (fieldName: string, currentValue: any): boolean => {
        const initialValue = (initialValues.current as any)[fieldName];
        // For regular fields, compare string values
        return currentValue !== initialValue;
    };

    // Function to get only changed fields for submission
    const getChangedFieldsData = () => {
        const changedData: Record<string, any> = {};

        Object.keys(form.data).forEach(key => {
            if (hasFieldChanged(key, (form.data as any)[key])) {
                changedData[key] = (form.data as any)[key];
            }
        });

        return changedData;
    };

    // Get current step data for easier access in components
    const getCurrentStepFields = () => {
        if (step <= 0 || step > settingsFormFields.length) return {};
        return settingsFormFields[step - 1].fields;
    };

    // Check if current step has any errors
    const currentStepHasErrors = () => {
        return errorSteps.includes(step);
    };

    // Process form errors to identify which steps have errors
    const processFormErrors = (errors: any): number[] => {
        const stepsWithError: number[] = [];
        Object.keys(errors).forEach(field => {
            const idx = settingsFormFields.findIndex(step => step.fields[field]);
            if (idx !== -1 && !stepsWithError.includes(idx + 1)) {
                stepsWithError.push(idx + 1);
            }
        });
        return stepsWithError;
    };

    return {
        form,
        step,
        steps,
        errorSteps,
        changedFields,
        handleNext,
        handleBack,
        handleSubmit,
        handleFieldChange,
        handleFileChange,
        setStep,
        getCurrentStepFields,
        currentStepHasErrors,
        hasFieldChanged,
        getChangedFieldsData,
    };
}
