import { useState, useCallback, useRef } from 'react';
import axios from 'axios';

interface AsyncSearchOption {
    value: number;
    label: string;
}

interface UseAsyncSearchOptions {
    routeName: string;
    responseKey?: string;
    transformItem?: (item: Record<string, unknown>) => AsyncSearchOption;
    minQueryLength?: number;
    debounceMs?: number;
}

interface UseAsyncSearchReturn {
    options: AsyncSearchOption[];
    isLoading: boolean;
    search: (query: string) => void;
    clear: () => void;
}

export function useAsyncSearch({
    routeName,
    responseKey = 'data',
    transformItem,
    minQueryLength = 2,
    debounceMs = 300,
}: UseAsyncSearchOptions): UseAsyncSearchReturn {
    const [options, setOptions] = useState<AsyncSearchOption[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const debounceTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const abortController = useRef<AbortController | null>(null);

    const search = useCallback(
        (query: string) => {
            // Clear previous debounce
            if (debounceTimer.current) {
                clearTimeout(debounceTimer.current);
            }

            // Cancel in-flight request
            if (abortController.current) {
                abortController.current.abort();
            }

            if (query.length < minQueryLength) {
                setOptions([]);
                setIsLoading(false);
                return;
            }

            setIsLoading(true);

            debounceTimer.current = setTimeout(async () => {
                const controller = new AbortController();
                abortController.current = controller;

                try {
                    const response = await axios.get(route(routeName, { query }), {
                        signal: controller.signal,
                    });

                    const items = response.data[responseKey] ?? [];

                    const transformed = transformItem
                        ? items.map(transformItem)
                        : items;

                    setOptions(transformed);
                } catch (error) {
                    if (!axios.isCancel(error)) {
                        setOptions([]);
                    }
                } finally {
                    if (!controller.signal.aborted) {
                        setIsLoading(false);
                    }
                }
            }, debounceMs);
        },
        [routeName, responseKey, transformItem, minQueryLength, debounceMs]
    );

    const clear = useCallback(() => {
        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }
        if (abortController.current) {
            abortController.current.abort();
        }
        setOptions([]);
        setIsLoading(false);
    }, []);

    return { options, isLoading, search, clear };
}
