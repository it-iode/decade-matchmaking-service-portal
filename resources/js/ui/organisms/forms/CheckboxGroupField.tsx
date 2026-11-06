import React, { useState } from 'react';
import clsx from 'clsx';
import { ChevronRightIcon } from '@heroicons/react/20/solid';
import { UIField } from '@/types';
import { Field, Label, ErrorMessage, Fieldset, Legend } from '@ui/primitives/fieldset';
import { Text } from '@ui/primitives/text';
import { CheckboxGroup, CheckboxField, Checkbox } from '@ui/primitives/checkbox';

interface CheckboxGroupFieldProps {
    name: string;
    field: UIField;
    value: any;
    error?: string;
    onChange: (name: string, value: any) => void;
}

/**
 * Renders a `checkbox-group` field. When one or more options carry a `detail`
 * (description and/or a long list of items), each option gains an inline,
 * collapsible "Details" disclosure and the group is laid out as full-width
 * stacked rows. Options without any detail keep the original multi-column grid,
 * so existing checkbox groups are unchanged.
 */
export default function CheckboxGroupField({ name, field, value, error, onChange }: Readonly<CheckboxGroupFieldProps>) {
    const [expanded, setExpanded] = useState<Set<string>>(new Set());

    const toggleChecked = (optionValue: string) => {
        const arr = Array.isArray(value) ? [...value] : [];
        onChange(name, arr.includes(optionValue) ? arr.filter((i: string) => i !== optionValue) : [...arr, optionValue]);
    };

    const toggleExpanded = (optionValue: string) => {
        setExpanded(prev => {
            const next = new Set(prev);
            if (next.has(optionValue)) {
                next.delete(optionValue);
            } else {
                next.add(optionValue);
            }
            return next;
        });
    };

    const options = field.options ?? [];
    const hasDetails = options.some(opt => opt.detail);

    return (
        <Fieldset className="mt-8">
            {field.label && <Legend className="capitalize">{field.label}</Legend>}
            {field.image && (
                <div className="w-full">
                    <img src={field.image} alt="Logo" className="object-cover" />
                </div>
            )}
            {field.description && <Text>{field.description}</Text>}

            {hasDetails ? (
                <ul className="mt-4 space-y-2">
                    {options.map(opt => {
                        const optionValue = String(opt.value);
                        const isChecked = Array.isArray(value) ? value.includes(opt.value) : false;
                        const isExpanded = expanded.has(optionValue);
                        const panelId = `${name}-${optionValue}-detail`;
                        return (
                            <li
                                key={opt.value}
                                className={clsx(
                                    'overflow-hidden rounded-lg border',
                                    isChecked
                                        ? 'border-firefly-200 bg-firefly-50 dark:border-firefly-800 dark:bg-firefly-950/30'
                                        : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-transparent'
                                )}
                            >
                                <div className="flex items-start justify-between gap-3 p-3">
                                    <CheckboxField className="min-w-0 grow">
                                        <Checkbox checked={isChecked} onChange={() => toggleChecked(optionValue)} />
                                        <Label>{opt.label}</Label>
                                    </CheckboxField>
                                    {opt.detail && (
                                        <button
                                            type="button"
                                            onClick={() => toggleExpanded(optionValue)}
                                            aria-expanded={isExpanded}
                                            aria-controls={panelId}
                                            className="inline-flex shrink-0 items-center gap-1 rounded-md px-1.5 py-1 text-xs font-semibold text-firefly-700 hover:bg-firefly-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-firefly-500 dark:text-firefly-300 dark:hover:bg-white/5"
                                        >
                                            {isExpanded ? 'Hide details' : 'Details'}
                                            <ChevronRightIcon
                                                data-slot="icon"
                                                aria-hidden="true"
                                                className={clsx('size-3.5 transition-transform', isExpanded && 'rotate-90')}
                                            />
                                        </button>
                                    )}
                                </div>
                                {opt.detail && (
                                    <div id={panelId} hidden={!isExpanded} className="px-3 pb-3 pl-[2.1rem]">
                                        <div className="rounded-lg border border-firefly-200 bg-firefly-50 p-3 dark:border-firefly-800 dark:bg-firefly-950/40">
                                            <p className="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">
                                                {opt.detail.description}
                                            </p>
                                            {opt.detail.htmlContent && (
                                                <div
                                                    className="mt-3 max-h-44 overflow-y-auto rounded-md border border-gray-200 bg-white p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 [&_a]:text-firefly-700 [&_a]:underline dark:[&_a]:text-firefly-300 [&_p]:mb-2 [&_strong]:font-semibold [&_ul]:grid [&_ul]:list-none [&_ul]:grid-cols-2 [&_ul]:gap-x-4 [&_ul]:gap-y-1 sm:[&_ul]:grid-cols-3"
                                                    /* Trusted, developer-authored HTML from a static config — never user input. */
                                                    dangerouslySetInnerHTML={{ __html: opt.detail.htmlContent }}
                                                />
                                            )}
                                        </div>
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>
            ) : (
                <CheckboxGroup className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    {options.map(opt => (
                        <CheckboxField key={opt.value}>
                            <Checkbox
                                checked={Array.isArray(value) ? value.includes(opt.value) : false}
                                onChange={() => toggleChecked(String(opt.value))}
                            />
                            <Label>{opt.label}</Label>
                        </CheckboxField>
                    ))}
                </CheckboxGroup>
            )}

            {error && (
                <Field>
                    <ErrorMessage>{error}</ErrorMessage>
                </Field>
            )}
        </Fieldset>
    );
}