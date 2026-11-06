import React, { useState, useEffect } from 'react';
import { Chips, ChipsChangeEvent } from 'primereact/chips';

interface TagInputProps {
	/** Optional initial tags */
	initialTags?: string[];
	onTagsChange?: (tags: string[]) => void;
	maxTags?: number;
	placeholder?: string;
}

export default function TagsInputss({
	initialTags = [],
	onTagsChange,
	maxTags = 3,
	placeholder = "Type and press enter"
}: TagInputProps) {
	const [tags, setTags] = useState<string[]>(initialTags);
	const [error, setError] = useState<string>('');

	useEffect(() => {
		onTagsChange?.(tags);
	}, [tags, onTagsChange]);

	const handleChange = (e: ChipsChangeEvent) => {
		const newTags = e.value || [];

		if (newTags.length > maxTags) {
			setError(`You can add up to ${maxTags} tags only.`);
			return;
		}

		setTags(newTags);
		setError('');
	};

	return (
		<div className="w-full">
			<Chips
				value={tags}
				onChange={handleChange}
				placeholder={placeholder}
				max={maxTags}
				className="w-full"
			/>
			{error && (
				<p className="mt-2 text-sm text-red-600">{error}</p>
			)}
		</div>
	);
}
