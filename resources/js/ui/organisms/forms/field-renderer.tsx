import React, {useMemo, useCallback} from 'react';
import {UIField} from '@/types';
import {Field, Label, Description, ErrorMessage} from '@ui/primitives/fieldset';
import {Input} from '@ui/primitives/input';
import {Textarea} from '@ui/primitives/textarea';
import {RadioGroup, RadioField, Radio} from '@ui/primitives/radio';
import CSVUpload from './csv-upload';
import SelectField from './SelectField';
import MultiSelectField from './MultiSelectField';
import KeywordsField from './KeywordsField';
import RankedChallengeField from './RankedChallengeField';
import CheckboxGroupField from './CheckboxGroupField';

interface FieldRendererProps {
    name: string;
    field: UIField;
    value: any;
    error?: string;
    onChange: (name: string, value: any) => void;
    formData: any;
    onKeyDown?: (e: React.KeyboardEvent) => void;
    onBlur?: (e: React.FocusEvent) => void;
    disabled?: boolean;
    className?: string;
}

// Field type registry for custom field types
const fieldTypeRegistry = new Map<string, React.ComponentType<any>>();

export function registerFieldType(type: string, component: React.ComponentType<any>) {
    fieldTypeRegistry.set(type, component);
}

// Debounce utility
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
    let timeout: NodeJS.Timeout;
    return ((...args: any[]) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    }) as T;
}

export default function FieldRenderer({
                                          name,
                                          field,
                                          value,
                                          error,
                                          onChange,
                                          formData,
                                          onKeyDown,
                                          onBlur,
                                          disabled,
                                          className
                                      }: Readonly<FieldRendererProps>) {


    // Accessibility attributes
    const getAriaAttributes = useCallback((field: UIField, error?: string) => ({
        'aria-invalid': !!error,
        'aria-describedby': error ? `${field.id}-error` : undefined,
        'aria-required': field.required,
    }), []);


    // Debounced onChange for search fields
    const debouncedOnChange = useMemo(() =>
            field.type === 'search' ? debounce(onChange, 300) : onChange,
        [onChange, field.type]
    );

    // Keyboard event handling
    const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
        if (field.onKeyDown) {
            field.onKeyDown(e);
        }
        if (onKeyDown) {
            onKeyDown(e);
        }
    }, [field.onKeyDown, onKeyDown]);


    // Enhanced change handler with validation support
    const handleChange = useCallback((e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const newValue = e.currentTarget.value;
        (field.type === 'search' ? debouncedOnChange : onChange)(name, newValue);
    }, [field.type, debouncedOnChange, onChange, name]);

    // Render error helper
    const renderError = useCallback(() =>
            error && <ErrorMessage id={`${field.id}-error`}>{error}</ErrorMessage>,
        [error, field.id]
    );

    // Common field props
    const getCommonFieldProps = useCallback(() => ({
        id: field.id,
        required: field.required,
        disabled: field.disabled || disabled,
        readOnly: field.readOnly,
        autoFocus: field.autoFocus,
        autoComplete: field.autoComplete,
        onKeyDown: handleKeyDown,
        ...getAriaAttributes(field, error),
    }), [field, disabled, handleKeyDown, getAriaAttributes, error]);

    // Check for custom field type
    const CustomComponent = fieldTypeRegistry.get(field.type);
    if (CustomComponent) {
        return (
            <Field key={name} className={field.className || className || "mt-8"}>
                {field.label && <Label className="capitalize">{field.label}</Label>}
                {field.description && <Description className="capitalize">{field.description}</Description>}
                <CustomComponent
                    {...getCommonFieldProps()}
                    value={value}
                    onChange={(newValue: any) => onChange(name, newValue)}
                    field={field}
                    formData={formData}
                />
                {renderError()}
            </Field>
        );
    }


    if (field.show && !field.show(formData)) {
        return null;
    }

    switch (field.type) {
        case 'hidden':
            return (
                <Field>
                    <Input
                        key={name}
                        type="hidden"
                        id={field.id}
                        value={value ?? ''}
                        onChange={handleChange}
                    />
                </Field>

            );
        case 'date':
            return (
                <Field key={name} className="mt-8 grid grid-cols-subgrid sm:col-span-3">
                    {field.label && <Label className="capitalize">{field.label}</Label>}
                    {field.description && <Description className="capitalize">{field.description}</Description>}
                    <Input
                        type={field.type}
                        id={field.id}
                        required={field.required}
                        value={value && new Date(value).toISOString().slice(0, 10) || ''}
                        placeholder={field.placeholder ?? ''}
                        onChange={handleChange}
                        invalid={!!error}
                    />
                    {error && <ErrorMessage>{error}</ErrorMessage>}
                </Field>
            );
        case 'text':
        case 'email':
        case 'url':
        case 'number':
        case 'password':
        case 'search':
        case 'tel':
        case 'time':
        case 'datetime-local':
        case 'month':
        case 'week':
        case 'color':
        case 'range':
            return (
                <Field key={name} className="mt-8 grid grid-cols-subgrid sm:col-span-3">
                    {field.label && <Label className="capitalize">{field.label}</Label>}
                    {field.description && <Description className="capitalize">{field.description}</Description>}
                    <Input
                        type={field.type}
                        id={field.id}
                        required={field.required}
                        value={value ?? ''}
                        placeholder={field.placeholder ?? ''}
                        autoComplete={field.autoComplete}
                        onChange={handleChange}
                        invalid={!!error}
                    />
                    {error && <ErrorMessage>{error}</ErrorMessage>}
                </Field>
            );
        case 'textarea':
            return (
                <Field key={name} className="mt-8">
                    {field.label && <Label className="capitalize">{field.label}</Label>}
                    {field.description && <Description className="capitalize">{field.description}</Description>}
                    <Textarea
                        id={field.id}
                        required={field.required}
                        value={value ?? ''}
                        placeholder={field.placeholder ?? ''}
                        onChange={handleChange}
                        invalid={!!error}
                    />
                    {error && <ErrorMessage>{error}</ErrorMessage>}
                </Field>
            );
        case 'file':
            return (
                <Field key={name} className="mt-8">
                    {field.label && <Label className="capitalize">{field.label}</Label>}
                    {field.description && <Description className="capitalize">
                        {field.description}
                    </Description>}
                    <Input
                        type="file"
                        id={field.id}
                        accept={field.accept}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                            const file = e.currentTarget.files ? e.currentTarget.files[0] : null;
                            onChange(name, file);
                        }}
                        invalid={!!error}
                    />
                    {value && (
                        <Description className="mt-1 text-xs">
                            <span>
                                <a href={value} target="_blank" rel="noopener noreferrer"
                                   className="text-blue-600 underline">
                                    Current File <br />
                                </a>
                            </span>
                            Select a new file to replace the existing document(s)

                        </Description>
                    )}
                    {error && <ErrorMessage>{error}</ErrorMessage>}
                </Field>
            );
        case 'select':
            return (
                <SelectField
                    key={name}
                    id={field.id}
                    name={name}
                    value={value}
                    onChange={(newValue) => onChange(name, newValue)}
                    options={field.options}
                    placeholder={field.placeholder ?? "Select an option..."}
                    required={field.required}
                    disabled={field.disabled || disabled}
                    readOnly={field.readOnly}
                    error={error}
                    label={field.label}
                    description={field.description}
                    className="mt-8"
                    invalid={!!error}
                    {...getAriaAttributes(field, error)}
                />
            );
        case 'raw_select':
            return (
                <Field key={name} className="mt-8">
                    {field.label && <Label>{field.label}</Label>}
                    {field.description && <Description>{field.description}</Description>}
                    <select
                        id={field.id}
                        required={field.required}
                        value={value ?? ''}
                        onChange={handleChange}
                        className={`mt-2 block w-full border rounded ${error ? 'border-red-600' : 'border-gray-300'}`}
                    >
                        <option value="">Select an option...</option>
                        {field.options?.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                    {error && <ErrorMessage>{error}</ErrorMessage>}
                </Field>
            );
        case 'multiselect':
            return (
                <MultiSelectField
                    key={name}
                    id={field.id}
                    name={name}
                    value={value || []}
                    onChange={(newValue) => onChange(name, newValue)}
                    options={field.options}
                    placeholder={field.placeholder || "Select options..."}
                    required={field.required}
                    disabled={field.disabled || disabled}
                    readOnly={field.readOnly}
                    error={error}
                    label={field.label}
                    description={field.description}
                    className="mt-8"
                    invalid={!!error}
                    showChips={true}
                    maxSelections={field.max}
                    {...getAriaAttributes(field, error)}
                />
            );
        case 'ranked-challenge':
            return (
                <RankedChallengeField
                    key={name}
                    id={field.id}
                    name={name}
                    value={value}
                    onChange={(v) => onChange(name, v)}
                    options={field.options as {value: string; label: string}[]}
                    required={field.required}
                    disabled={field.disabled || disabled}
                    readOnly={field.readOnly}
                    error={error}
                    label={field.label}
                    description={field.description}
                    image={field.image}
                    className="mt-8"
                />
            );
        case 'radio':
            return (
                <Field key={name} className="mt-8">
                    {field.label && <Label>{field.label}</Label>}
                    {field.description && <Description>{field.description}</Description>}
                    <RadioGroup
                        value={value}
                        onChange={(newValue) => onChange(name, newValue)}
                    >
                        <div className="flex gap-6">
                            {field.options?.map(opt => (
                                <RadioField key={opt.value}>
                                    <Radio value={String(opt.value)}/>
                                    <Label className="capitalize">{opt.label}</Label>
                                </RadioField>
                            ))}
                        </div>
                    </RadioGroup>
                    {error && <ErrorMessage>{error}</ErrorMessage>}
                </Field>
            );
        case 'checkbox-group':
            return (
                <CheckboxGroupField
                    key={name}
                    name={name}
                    field={field}
                    value={value}
                    error={error}
                    onChange={onChange}
                />
            );
        case 'csv-upload':
            return (
                <CSVUpload
                    key={name}
                    name={name}
                    label={field.label}
                    description={field.description}
                    accept={field.accept}
                    onChange={onChange}
                    error={error}
                    disabled={field.disabled || disabled}
                />
            );
        case 'keywords':
            return (
                <KeywordsField
                    key={name}
                    name={name}
                    label={field.label}
                    description={field.description}
                    placeholder={field.placeholder}
                    value={value ?? []}
                    onChange={(newValue) => onChange(name, newValue)}
                    error={error}
                    required={field.required}
                    disabled={field.disabled || disabled}
                    maxKeywords={field.maxKeywords}
                    minLength={field.minLength}
                    maxLength={field.maxLength}
                    className={field.className ?? className}
                />
            );
        default:
            return null;
    }
}
