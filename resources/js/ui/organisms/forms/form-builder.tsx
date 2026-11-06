import React, { useCallback, useState } from 'react';
import { UIField } from '@/types';
import FieldRenderer from './field-renderer';
import { Button } from '@ui/primitives/button';

interface FormBuilderProps {
    fields: UIField[];
    values: Record<string, any>;
    errors?: Record<string, string>;
    onChange: (name: string, value: any) => void;
    onSubmit: (values: Record<string, any>) => void;
    onValidate?: (values: Record<string, any>) => Record<string, string>;
    submitLabel?: string;
    loading?: boolean;
    disabled?: boolean;
    className?: string;
    fieldClassName?: string;
}

export function FormBuilder({
    fields,
    values,
    errors = {},
    onChange,
    onSubmit,
    onValidate,
    submitLabel = 'Submit',
    loading = false,
    disabled = false,
    className = '',
    fieldClassName
}: FormBuilderProps) {
    const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

    // Validate form
    const validateForm = useCallback((formValues: Record<string, any>): Record<string, string> => {
        const fieldErrors: Record<string, string> = {};

        fields.forEach(field => {
            const value = formValues[field.id];

            // Required field validation
            if (field.required && (!value || value.toString().trim() === '')) {
                fieldErrors[field.id] = `${field.label || field.id} is required`;
                return;
            }

            // Pattern validation
            if (field.pattern && value && !new RegExp(field.pattern).test(value)) {
                fieldErrors[field.id] = `${field.label || field.id} format is invalid`;
                return;
            }

            // Min/Max validation for numbers
            if (field.min !== undefined && value < field.min) {
                fieldErrors[field.id] = `${field.label || field.id} must be at least ${field.min}`;
                return;
            }

            if (field.max !== undefined && value > field.max) {
                fieldErrors[field.id] = `${field.label || field.id} cannot exceed ${field.max}`;
                return;
            }

            // MaxLength validation
            if (field.maxLength && value && value.toString().length > field.maxLength) {
                fieldErrors[field.id] = `${field.label || field.id} cannot exceed ${field.maxLength} characters`;
                return;
            }
        });

        // Custom validation if provided
        if (onValidate) {
            const customErrors = onValidate(formValues);
            Object.assign(fieldErrors, customErrors);
        }

        return fieldErrors;
    }, [fields, onValidate]);

    const handleSubmit = useCallback((e: React.FormEvent) => {
        e.preventDefault();

        const formErrors = validateForm(values);
        setValidationErrors(formErrors);

        if (Object.keys(formErrors).length === 0) {
            onSubmit(values);
        }
    }, [values, validateForm, onSubmit]);

    const handleFieldChange = useCallback((name: string, value: any) => {
        onChange(name, value);

        // Clear validation error for this field
        if (validationErrors[name]) {
            setValidationErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
    }, [onChange, validationErrors]);

    // Combine external errors with validation errors
    const allErrors = { ...validationErrors, ...errors };

    return (
        <form onSubmit={handleSubmit} className={`space-y-6 ${className}`}>
            {fields.map(field => (
                <FieldRenderer
                    key={field.id}
                    name={field.id}
                    field={field}
                    value={values[field.id]}
                    error={allErrors[field.id]}
                    onChange={handleFieldChange}
                    formData={values}
                    disabled={disabled || field.disabled}
                    className={fieldClassName}
                />
            ))}

            <div className="flex justify-end pt-6">
                <Button
                    type="submit"
                    disabled={loading || disabled}
                    className="px-6 py-2"
                >
                    {loading ? 'Submitting...' : submitLabel}
                </Button>
            </div>
        </form>
    );
}

export default FormBuilder;
