import React, { useState, useMemo } from 'react';
import { Combobox, ComboboxInput, ComboboxButton, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { ChevronsUpDown } from 'lucide-react';

interface MultiSelectFieldProps {
    id: string;
    name: string;
    value: string[] | number[];
    onChange: (value: string[] | number[]) => void;
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
    showChips?: boolean;
    maxSelections?: number;
    'aria-invalid'?: boolean;
    'aria-describedby'?: string;
    'aria-required'?: boolean;
}

export default function MultiSelectField({
    id,
    name,
    value = [],
    onChange,
    options = [],
    placeholder = "Select options...",
    required,
    disabled,
    readOnly,
    error,
    label,
    description,
    className = "mt-8",
    invalid,
    showChips = true,
    maxSelections,
    ...ariaProps
}: Readonly<MultiSelectFieldProps>) {
    const [query, setQuery] = useState('');

    // Convert values to string for consistency
    const selectedValues = useMemo(() =>
        Array.isArray(value) ? value.map(v => String(v)) : [],
        [value]
    );

    const filteredOptions = query == '' ? options : options.filter(opt =>
        opt.label.toString().toLowerCase().includes(query.toLowerCase())
    );

    const handleMultiselectChange = (values: string[]) => {
        // Check max selections limit
        if (maxSelections && values.length > maxSelections) {
            return;
        }

        onChange(values);
        setQuery(''); // reset query on select
    };

    const handleRemoveChip = (chipValue: string) => {
        onChange(selectedValues.filter(v => v !== chipValue));
    };

    return (
        <div className={className}>
            {label && (
                <label htmlFor={id} className="block font-medium">
                    {label}
                </label>
            )}
            {description && (
                <p className="mt-1 text-sm text-gray-500">
                    {description}
                </p>
            )}
            <Combobox
                value={selectedValues}
                immediate
                onChange={handleMultiselectChange}
                multiple
                disabled={disabled || readOnly}
                {...ariaProps}
            >
                <div className="relative">
                    <div className="mt-3 relative w-full cursor-default overflow-hidden rounded-md border border-gray-300 bg-white text-left shadow-sm focus-within:border-firefly-500 focus-within:ring-1 focus-within:ring-firefly-500">
                        <ComboboxInput
                            className="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 focus:ring-0"
                            displayValue={() => query}
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
                                        <span className={`block truncate ${selected ? 'font-medium' : 'font-normal'}`}>
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

            {/* Display selected chips */}
            {showChips && selectedValues.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-2">
                    {selectedValues.map((valueStr: string) => {
                        const option = options.find(opt => String(opt.value) === valueStr);
                        return (
                            <span
                                key={valueStr}
                                className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-firefly-100 text-firefly-800"
                            >
                                {option ? option.label : valueStr}
                                {!disabled && !readOnly && (
                                    <button
                                        type="button"
                                        onClick={() => handleRemoveChip(valueStr)}
                                        className="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full text-firefly-400 hover:bg-firefly-200 hover:text-firefly-500"
                                    >
                                        <span className="sr-only">Remove</span>
                                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fillRule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                )}
                            </span>
                        );
                    })}
                </div>
            )}

            {/* Max selections reached message */}
            {maxSelections && selectedValues.length >= maxSelections && (
                <p className="mt-2 text-sm text-amber-600">
                    Maximum {maxSelections} selection{maxSelections !== 1 ? 's' : ''} reached.
                </p>
            )}

            {error && <p className="text-red-600 text-sm mt-1" id={`${id}-error`}>{error}</p>}
        </div>
    );
}
