import React from 'react';
import { Badge } from '@ui/primitives/badge';
import { XMarkIcon } from '@heroicons/react/16/solid';

interface KeywordChipProps {
  keyword: string;
  onRemove: () => void;
  disabled?: boolean;
  className?: string;
}

export default function KeywordChip({ 
  keyword, 
  onRemove, 
  disabled = false, 
  className 
}: KeywordChipProps) {
  return (
    <Badge 
      color="blue" 
      className={`group inline-flex items-center gap-1 pr-1 ${className}`}
    >
      <span className="truncate max-w-32">{keyword}</span>
      <button
        type="button"
        onClick={onRemove}
        disabled={disabled}
        className="flex-shrink-0 p-0.5 rounded-sm hover:bg-blue-600/20 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        aria-label={`Remove keyword: ${keyword}`}
      >
        <XMarkIcon 
          data-slot="icon" 
          className="size-3 text-blue-700 dark:text-blue-400" 
        />
      </button>
    </Badge>
  );
}