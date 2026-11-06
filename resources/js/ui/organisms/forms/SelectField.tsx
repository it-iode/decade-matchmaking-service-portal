import React, { useState } from 'react';
import { Field, Label, Description, ErrorMessage } from '@ui/primitives/fieldset';
import { Combobox, ComboboxInput, ComboboxButton, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { ChevronsUpDown } from 'lucide-react';

interface SelectFieldProps {
    id: string;
    name: string;
    value: string | number | null;
    onChange: (value: string | number | null) => void;
    options?: { value: string | number; label: string }[];
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    readOnly?: boolean;
    error?: string;
    label?: string;
    description?: string;
    className?: string;
    invalid?: boolean;
    'aria-invalid'?: boolean;
    'aria-describedby'?: string;
    'aria-required'?: boolean;
}

export default function SelectField({
    id,
    name,
    value,
    onChange,
    options = [],
    placeholder = "Select an option...",
    required,
    disabled,
    readOnly,
    error,
    label,
    description,
    className = "mt-8",
    invalid,
    ...ariaProps
}: Readonly<SelectFieldProps>) {
    const [query, setQuery] = useState('');

    const filteredOptions = query == '' ? options : options.filter(opt =>
        opt.label.toString().toLowerCase().includes(query.toLowerCase())
    );

    const handleComboboxChange = (newValue: string | null) => {
        // Convert back to original type if it was a number
        const originalOption = options.find(opt => String(opt.value) === newValue);
        const convertedValue = originalOption ? originalOption.value : newValue;
        onChange(convertedValue);
        setQuery(''); // reset query on select
    };

    return (
        <Field className={className}>
            {label && (
                <Label htmlFor={id} className="block font-medium">
                    {label}
                </Label>
            )}
            {description && (
                <Description className="mt-1 text-sm text-gray-500">
                    {description}
                </Description>
            )}
            <Combobox
                immediate
                value={value !== null ? String(value) : null}
                onChange={handleComboboxChange}
                disabled={disabled || readOnly}
                {...ariaProps}
            >
                <div className="relative">
                    <div className="mt-3 relative w-full cursor-default overflow-hidden rounded-md border border-gray-300 bg-white text-left shadow-sm focus-within:border-firefly-500 focus-within:ring-1 focus-within:ring-firefly-500">
                        <ComboboxInput
                            className="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 focus:ring-0"
                            displayValue={(value: string | number | null) => {
                                const option = options.find(opt => opt.value == value);
                                return option ? option.label : String(value ?? '');
                            }}
                            onChange={event => setQuery(event.target.value)}
                            placeholder={placeholder}
                            disabled={disabled || readOnly}
                        />
                        <ComboboxButton className="absolute inset-y-0 right-0 flex items-center pr-2">
                            <ChevronsUpDown
                                className="h-5 w-5 text-gray-400"
                                aria-hidden="true"
                            />
                        </ComboboxButton>
                    </div>
                    <ComboboxOptions className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                        {filteredOptions.map((option) => (
                            <ComboboxOption
                                key={option.value}
                                className={({ focus }) =>
                                    `relative cursor-default select-none py-2 pl-10 pr-4 ${
                                        focus ? 'bg-firefly-600 text-white' : 'text-gray-900'
                                    }`
                                }
                                value={String(option.value)}
                            >
                                {({ selected, focus }) => (
                                    <>
                                        <span className={`block capitalize truncate ${selected ? 'font-medium' : 'font-normal'}`}>
                                            {option.label}
                                        </span>
                                        {selected ? (
                                            <span className={`absolute inset-y-0 left-0 flex items-center pl-3 ${
                                                focus ? 'text-white' : 'text-firefly-600'
                                            }`}>
                                                <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </span>
                                        ) : null}
                                    </>
                                )}
                            </ComboboxOption>
                        ))}
                    </ComboboxOptions>
                </div>
            </Combobox>
            {error && <ErrorMessage id={`${id}-error`}>{error}</ErrorMessage>}
        </Field>
    );
}
