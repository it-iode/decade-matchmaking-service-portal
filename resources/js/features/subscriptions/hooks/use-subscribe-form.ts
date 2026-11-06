import { useForm } from '@inertiajs/react';
import { subscribeFormFields } from '../config';

interface UseSubscribeFormProps {
    onSuccess?: () => void;
}

export function useSubscribeForm({ onSuccess }: UseSubscribeFormProps = {}) {
    // Use Inertia's useForm (same pattern as other forms in the project)
    const form = useForm({
        user_id: null as number | null,
        request_id: null as number | null,
    });

    const steps = subscribeFormFields.map((s) => s.label);

    const handleSubmit = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        form.clearErrors();

        // Use Inertia's form.post - handles CSRF automatically
        form.post(route('admin.subscriptions.subscribe-user'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                if (onSuccess) onSuccess();
            },
        });
    };

    const handleFieldChange = (name: string, value: any) => {
        form.setData(name as keyof typeof form.data, value);
    };

    const getCurrentStepFields = () => {
        return subscribeFormFields[0].fields;
    };

    return {
        form,
        steps,
        handleSubmit,
        handleFieldChange,
        getCurrentStepFields,
    };
}
