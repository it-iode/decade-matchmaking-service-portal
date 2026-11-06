import React, { useCallback } from 'react';
import {TagsInput} from '@ui/molecules';
import {UIField} from '@/types';

interface TagsInputFieldProps {
    id: string;
    value: string[];
    onChange: (value: string[]) => void;
    field: UIField;
    required?: boolean;
    disabled?: boolean;
}

export default function TagsInputField({
    id,
    value,
    onChange,
    field,
    required,
    disabled
}: Readonly<TagsInputFieldProps>) {
    const handleTagsChange = useCallback((tags: string[]) => {
        onChange(tags);
    }, [onChange]);

    return (
        <TagsInput
            initialTags={value || []}
            onTagsChange={handleTagsChange}
            maxTags={field.maxLength || 3}
            placeholder={field.placeholder || "Type and press enter"}
        />
    );
}
