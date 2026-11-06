import React, {useEffect, useRef, useState} from 'react';
import {Fieldset, Legend, ErrorMessage, Field} from '@ui/primitives/fieldset';
import {Text} from '@ui/primitives/text';
import {Badge} from '@ui/primitives/badge';
import {GripVertical, ChevronUp, ChevronDown, X} from 'lucide-react';
import SelectField from './SelectField';

export interface RankedChallengeValue {
    primary?: string | null;
    secondary?: string | null;
    tertiary?: string | null;
}

interface RankedChallengeFieldProps {
    id: string;
    name: string;
    value: RankedChallengeValue | null;
    onChange: (v: {primary: string | null; secondary: string | null; tertiary: string | null}) => void;
    options?: {value: string; label: string}[];
    required?: boolean;
    disabled?: boolean;
    readOnly?: boolean;
    error?: string;
    label?: string;
    description?: string;
    image?: string;
    className?: string;
}

const POS = ['PRIMARY', 'SECONDARY', 'TERTIARY'] as const;
const POS_COLORS = ['blue', 'cyan', 'zinc'] as const;
const MAX = 3;

// Derive an ordered list of selected challenge values from the incoming keyed value.
function valueToOrder(value: RankedChallengeValue | null): string[] {
    if (!value) {
        return [];
    }
    return (['primary', 'secondary', 'tertiary'] as const)
        .map((rank) => value[rank])
        .filter((v): v is string => typeof v === 'string' && v.length > 0);
}

export default function RankedChallengeField({
    id,
    name,
    value,
    onChange,
    options = [],
    required,
    disabled,
    readOnly,
    error,
    label,
    description,
    image,
    className = 'mt-8',
}: Readonly<RankedChallengeFieldProps>) {
    const [order, setOrder] = useState<string[]>(() => valueToOrder(value));
    const dragIndex = useRef<number | null>(null);

    const isLocked = disabled || readOnly;

    // Re-hydrate internal order when the external value changes (e.g. edit-mode async load).
    // Only sync when the derived orders differ to avoid clobbering local reordering.
    const externalOrder = valueToOrder(value);
    const externalKey = externalOrder.join('|');
    const orderKey = order.join('|');
    useEffect(() => {
        if (externalKey !== orderKey) {
            setOrder(externalOrder);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [externalKey]);

    const labelFor = (val: string) => options.find((o) => o.value === val)?.label ?? val;

    const emit = (next: string[]) => {
        setOrder(next);
        onChange({
            primary: next[0] ?? null,
            secondary: next[1] ?? null,
            tertiary: next[2] ?? null,
        });
    };

    const availableOptions = options.filter((o) => !order.includes(o.value));

    const handleAdd = (val: string | number | null) => {
        if (val === null || val === undefined || val === '') {
            return;
        }
        const strVal = String(val);
        if (order.includes(strVal) || order.length >= MAX) {
            return;
        }
        emit([...order, strVal]);
    };

    const handleRemove = (index: number) => {
        emit(order.filter((_, i) => i !== index));
    };

    const move = (from: number, to: number) => {
        if (to < 0 || to >= order.length || from === to) {
            return;
        }
        const next = [...order];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        emit(next);
    };

    // Native HTML5 drag handlers
    const handleDragStart = (index: number) => {
        dragIndex.current = index;
    };
    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };
    const handleDrop = (index: number) => {
        if (dragIndex.current === null) {
            return;
        }
        move(dragIndex.current, index);
        dragIndex.current = null;
    };

    return (
        <Fieldset className={className}>
            {label && <Legend>{label}</Legend>}
            {image && (
                <div className="w-full">
                    <img src={image} alt="" className="object-cover" />
                </div>
            )}
            {description && <Text>{description}</Text>}

            {/* Add control */}
            {!isLocked && (
                <SelectField
                    id={`${id}-add`}
                    name={`${name}_add`}
                    value={null}
                    onChange={handleAdd}
                    options={availableOptions}
                    placeholder={
                        order.length >= MAX
                            ? 'Maximum of three Challenges selected'
                            : 'Add a Decade Challenge...'
                    }
                    disabled={order.length >= MAX}
                    className="mt-4"
                    aria-required={required}
                    aria-invalid={!!error}
                />
            )}

            {/* Chip list */}
            <ul className="mt-4 space-y-2" aria-label="Selected Decade Challenges in priority order">
                {order.map((val, index) => (
                    <li
                        key={val}
                        draggable={!isLocked}
                        onDragStart={() => handleDragStart(index)}
                        onDragOver={handleDragOver}
                        onDrop={() => handleDrop(index)}
                        className={`flex items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-800 ${
                            isLocked ? '' : 'cursor-grab active:cursor-grabbing'
                        }`}
                    >
                        {!isLocked && (
                            <GripVertical
                                className="h-4 w-4 shrink-0 text-gray-400"
                                aria-hidden="true"
                            />
                        )}
                        <Badge color={POS_COLORS[index]} className="shrink-0">
                            {POS[index]}
                        </Badge>
                        <span className="flex-1 text-sm text-gray-900 dark:text-gray-100">
                            {labelFor(val)}
                        </span>
                        {!isLocked && (
                            <div className="flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    onClick={() => move(index, index - 1)}
                                    disabled={index === 0}
                                    aria-label={`Move ${labelFor(val)} up`}
                                    className="rounded p-1 text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent dark:hover:bg-gray-700"
                                >
                                    <ChevronUp className="h-4 w-4" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => move(index, index + 1)}
                                    disabled={index === order.length - 1}
                                    aria-label={`Move ${labelFor(val)} down`}
                                    className="rounded p-1 text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent dark:hover:bg-gray-700"
                                >
                                    <ChevronDown className="h-4 w-4" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleRemove(index)}
                                    aria-label={`Remove ${labelFor(val)}`}
                                    className="rounded p-1 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30"
                                >
                                    <X className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>
                        )}
                    </li>
                ))}
            </ul>

            {error && (
                <Field className="mt-2">
                    <ErrorMessage id={`${id}-error`}>{error}</ErrorMessage>
                </Field>
            )}
        </Fieldset>
    );
}
