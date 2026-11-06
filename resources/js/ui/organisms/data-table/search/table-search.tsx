import React, { useState, useEffect } from 'react';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/16/solid';
import { Button } from '@ui/primitives/button';
import { router } from '@inertiajs/react';
import { FieldRenderer } from '@ui/organisms/forms';
import { UIField } from '@/types';

interface SearchField {
    key: string;
    label: string;
    placeholder: string;
}

interface TableSearchProps {
    searchFields: UIField[];
    routeName: string;
    currentSearch?: Record<string, string>;
    preserveSort?: boolean;
    className?: string;
}

export function TableSearch({
    searchFields,
    routeName,
    currentSearch = {},
    preserveSort = true,
    className = ""
}: Readonly<TableSearchProps>) {

    const [searchValues, setSearchValues] = useState<Record<string, string>>(currentSearch);
    const [isSearching, setIsSearching] = useState(false);

    // Update local state when currentSearch changes
    useEffect(() => {
        setSearchValues(currentSearch);
    }, [currentSearch]);

    const handleSearchChange = (field: string, value: any) => {
        setSearchValues(prev => ({
            ...prev,
            [field]: value
        }));
    };


    const handleSearch = () => {
        setIsSearching(true);

        // Filter out empty search values
        const searchParams = Object.fromEntries(
            Object.entries(searchValues).filter(([_, value]) => value.toString().trim() !== '')
        );

        // Preserve current sort parameters if needed
        const currentParams = new URLSearchParams(window.location.search);
        const preservedParams: Record<string, string> = {};

        if (preserveSort) {
            const sort = currentParams.get('sort');
            const order = currentParams.get('order');
            if (sort) preservedParams.sort = sort;
            if (order) preservedParams.order = order;
        }

        router.get(route(routeName), {
            ...preservedParams,
            ...searchParams
        }, {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => setIsSearching(false)
        });
    };

    const handleClearSearch = () => {
        setSearchValues({});
        setIsSearching(true);

        // Preserve sort parameters if needed
        const currentParams = new URLSearchParams(window.location.search);
        const preservedParams: Record<string, string> = {};

        if (preserveSort) {
            const sort = currentParams.get('sort');
            const order = currentParams.get('order');
            if (sort) preservedParams.sort = sort;
            if (order) preservedParams.order = order;
        }

        router.get(route(routeName), preservedParams, {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => setIsSearching(false)
        });
    };

    const hasActiveSearch = Object.values(currentSearch).some(value => value && value.trim() !== '');

    return (
        <div className={`bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6 ${className}`}>
            <div className="flex flex-col gap-4">
                <div className="flex items-center gap-2">
                    <MagnifyingGlassIcon className="size-5 text-gray-400" />
                    <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100">Search</h3>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {searchFields.map((field) => (
                        <div key={field.id}>
                            <FieldRenderer
                                name={field.id}
                                field={field}
                                value={searchValues[field.id] || ''}
                                onChange={handleSearchChange}
                                formData={searchValues}
                            />
                        </div>
                    ))}
                </div>

                <div className="flex items-center justify-between pt-2">
                    <div className="flex items-center gap-2">
                        <Button
                            onClick={handleSearch}
                            disabled={isSearching}
                            className="flex items-center gap-2"
                            color="firefly"
                        >
                            <MagnifyingGlassIcon className="size-4" />
                            {isSearching ? 'Searching...' : 'Search'}
                        </Button>

                        {hasActiveSearch && (
                            <Button
                                onClick={handleClearSearch}
                                disabled={isSearching}
                                color="zinc"
                                className="flex items-center gap-2"
                            >
                                <XMarkIcon className="size-4" />
                                Clear
                            </Button>
                        )}
                    </div>

                    {hasActiveSearch && (
                        <span className="text-sm text-gray-500 dark:text-gray-400">
                            Active search applied
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}
