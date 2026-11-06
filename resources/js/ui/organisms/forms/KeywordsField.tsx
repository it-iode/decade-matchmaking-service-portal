import React, { useState, useCallback, useRef } from 'react';
import { Field, Label, Description, ErrorMessage } from '@ui/primitives/fieldset';
import { Input } from '@ui/primitives/input';
import { Text } from '@ui/primitives/text';
import KeywordChip from './KeywordChip';

interface KeywordsFieldProps {
  name: string;
  label?: string;
  description?: string;
  placeholder?: string;
  value?: string[];
  onChange: (value: string[]) => void;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  maxKeywords?: number;
  minLength?: number;
  maxLength?: number;
  className?: string;
}

export default function KeywordsField({
  name,
  label,
  description,
  placeholder = "Type keywords separated by commas or press Enter",
  value = [],
  onChange,
  error,
  required = false,
  disabled = false,
  maxKeywords = 10,
  minLength = 2,
  maxLength = 50,
  className = "mt-8"
}: Readonly<KeywordsFieldProps>) {
  const [inputValue, setInputValue] = useState('');
  const [internalError, setInternalError] = useState<string>('');
  const inputRef = useRef<HTMLInputElement>(null);

  // Parse comma-separated string to array
  const keywords = value ;

  // Display error (external error takes priority over internal error)
  const displayError = error || internalError;

  // Validate keyword
  const validateKeyword = useCallback((keyword: string): string | null => {
    const trimmed = keyword.trim();

    if (!trimmed) {
      return 'Keyword cannot be empty';
    }

    if (trimmed.length < minLength) {
      return `Keyword must be at least ${minLength} characters long`;
    }

    if (trimmed.length > maxLength) {
      return `Keyword cannot exceed ${maxLength} characters`;
    }

    if (keywords.includes(trimmed)) {
      return 'This keyword already exists';
    }

    if (keywords.length >= maxKeywords) {
      return `Maximum ${maxKeywords} keywords allowed`;
    }

    return null;
  }, [keywords, minLength, maxLength, maxKeywords]);

  // Add keyword
  const addKeyword = useCallback((keyword: string) => {
    const trimmed = keyword.trim();

    if (!trimmed) {
      return;
    }

    const validationError = validateKeyword(trimmed);
    if (validationError) {
      setInternalError(validationError);
      return;
    }

    const newKeywords = [...keywords, trimmed];
    onChange(newKeywords);
    setInputValue('');
    setInternalError('');

    // Focus back to input for better UX
    setTimeout(() => {
      inputRef.current?.focus();
    }, 0);
  }, [keywords, onChange, validateKeyword]);

  // Remove keyword
  const removeKeyword = useCallback((index: number) => {
    const newKeywords = keywords.filter((_, i) => i !== index);
    onChange(newKeywords);
    setInternalError('');
  }, [keywords, onChange]);

  // Handle input changes
  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setInputValue(newValue);
    console.log('Input changed:', newValue);
    // Clear internal error when user starts typing
    if (internalError) {
      setInternalError('');
    }

    // Handle comma separation in real-time
    if (newValue.includes(',')) {
      const parts = newValue.split(',');
      const keywordToAdd = parts[0].trim();
      const remaining = parts.slice(1).join(',');

      if (keywordToAdd) {
        addKeyword(keywordToAdd);
      }

      setInputValue(remaining);
    }
  }, [addKeyword, internalError]);

  // Handle key events
  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    switch (e.key) {
      case 'Enter':
        e.preventDefault();
        if (inputValue.trim()) {
          addKeyword(inputValue);
        }
        break;

      case 'Tab':
        if (inputValue.trim()) {
          e.preventDefault();
          addKeyword(inputValue);
        }
        break;

      case 'Backspace':
        if (!inputValue && keywords.length > 0) {
          e.preventDefault();
          removeKeyword(keywords.length - 1);
        }
        break;

      case 'Escape':
        setInputValue('');
        setInternalError('');
        break;
    }
  }, [inputValue, addKeyword, keywords, removeKeyword]);

  // Handle input blur
  const handleBlur = useCallback(() => {
    if (inputValue.trim()) {
      addKeyword(inputValue);
    }
  }, [inputValue, addKeyword]);

  // Handle paste
  const handlePaste = useCallback((e: React.ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault();
    const pastedText = e.clipboardData.getData('text');
    const pastedKeywords = pastedText.split(/[,\n\t]+/).filter(k => k.trim());

    pastedKeywords.forEach(keyword => {
      addKeyword(keyword);
    });
  }, [addKeyword]);

  // Calculate remaining count
  const remainingCount = maxKeywords - keywords.length;

  // Accessibility attributes
  const ariaAttributes = {
    'aria-invalid': !!displayError,
    'aria-describedby': displayError ? `${name}-error` : undefined,
    'aria-required': required,
  };

  return (
    <Field className={className}>
      {label && (
        <Label className="capitalize">
          {label}
          {required && <span className="text-red-500 ml-1" aria-label="required">*</span>}
        </Label>
      )}

      {description && (
        <Description className="capitalize">
          {description}
        </Description>
      )}

      <div className="space-y-3">
        {/* Keywords Display */}
        {keywords.length > 0 && (
          <ul
            className="flex flex-wrap gap-2 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700"
            aria-label="Current keywords"
          >
            {keywords.map((keyword, index) => (
              <li key={`${keyword}-${index}`}>
                <KeywordChip
                  keyword={keyword}
                  onRemove={() => removeKeyword(index)}
                  disabled={disabled}
                />
              </li>
            ))}
          </ul>
        )}

        {/* Input Field */}
        <div className="relative">
          <Input
            ref={inputRef}
            type="text"
            name={name}
            value={inputValue}
            onChange={handleInputChange}
            onKeyDown={handleKeyDown}
            onBlur={handleBlur}
            onPaste={handlePaste}
            placeholder={keywords.length >= maxKeywords ? `Maximum ${maxKeywords} keywords reached` : placeholder}
            disabled={disabled || keywords.length >= maxKeywords}
            invalid={!!displayError}
            className="w-full"
            {...ariaAttributes}
          />
        </div>

        {/* Helper Text & Count */}
        <div className="flex justify-between items-center text-sm">
          <Text className="text-gray-600 dark:text-gray-400">
            Press Enter, Tab, or comma to add keywords
          </Text>
          <Text
            className={`font-medium ${remainingCount <= 2 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400'}`}
          >
            {remainingCount} remaining
          </Text>
        </div>
      </div>

      {/* Error Message */}
      {displayError && (
        <ErrorMessage id={`${name}-error`}>
          {displayError}
        </ErrorMessage>
      )}
    </Field>
  );
}
