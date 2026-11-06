import React, { useState, useRef } from 'react';
import { Button } from '@ui/primitives/button';
import { Field, Label, Description, ErrorMessage } from '@ui/primitives/fieldset';
import { Text } from '@ui/primitives/text';
import { CheckCircleIcon, XCircleIcon, DocumentIcon, ArrowUpTrayIcon } from '@heroicons/react/16/solid';

interface CSVUploadProps {
    name: string;
    label?: string;
    description?: string;
    accept?: string;
    onChange: (name: string, value: File | null) => void;
    error?: string;
    disabled?: boolean;
}

interface UploadStatus {
    type: 'idle' | 'uploading' | 'success' | 'error';
    message?: string;
    progress?: number;
}

export default function CSVUpload({
    name,
    label,
    description,
    accept = '.csv,text/csv,application/csv',
    onChange,
    error,
    disabled = false
}: Readonly<CSVUploadProps>) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [status, setStatus] = useState<UploadStatus>({ type: 'idle' });
    const [dragActive, setDragActive] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const validateFile = (file: File): string | null => {
        // Check file type
        const validTypes = ['text/csv', 'application/csv', 'text/plain'];
        const validExtensions = ['.csv'];
        const hasValidType = validTypes.includes(file.type);
        const hasValidExtension = validExtensions.some(ext => file.name.toLowerCase().endsWith(ext));
        
        if (!hasValidType && !hasValidExtension) {
            return 'Please select a valid CSV file.';
        }

        // Check file size (max 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            return 'File size must be less than 5MB.';
        }

        return null;
    };

    const handleFileSelect = (file: File) => {
        const validationError = validateFile(file);
        
        if (validationError) {
            setStatus({ type: 'error', message: validationError });
            setSelectedFile(null);
            onChange(name, null);
            return;
        }

        setSelectedFile(file);
        setStatus({ type: 'idle' });
        onChange(name, file);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            handleFileSelect(file);
        }
    };

    const handleDragEnter = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        const file = e.dataTransfer.files?.[0];
        if (file) {
            handleFileSelect(file);
        }
    };

    const handleRemoveFile = () => {
        setSelectedFile(null);
        setStatus({ type: 'idle' });
        onChange(name, null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <Field className="mt-8">
            {label && <Label>{label}</Label>}
            {description && <Description>{description}</Description>}
            
            <div className="mt-2">
                {/* File drop zone */}
                <div
                    className={`
                        relative border-2 border-dashed rounded-lg p-6 transition-colors
                        ${dragActive 
                            ? 'border-firefly-500 bg-firefly-50 dark:bg-firefly-950/20' 
                            : selectedFile 
                                ? 'border-green-300 bg-green-50 dark:bg-green-950/20'
                                : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                        }
                        ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                    `}
                    onDragEnter={handleDragEnter}
                    onDragLeave={handleDragLeave}
                    onDragOver={handleDragOver}
                    onDrop={handleDrop}
                    onClick={() => !disabled && fileInputRef.current?.click()}
                >
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept={accept}
                        onChange={handleInputChange}
                        className="hidden"
                        disabled={disabled}
                    />

                    <div className="text-center">
                        {selectedFile ? (
                            <>
                                <DocumentIcon className="mx-auto h-12 w-12 text-green-500" data-slot="icon" />
                                <Text className="mt-2 text-sm font-medium text-green-700 dark:text-green-400">
                                    {selectedFile.name}
                                </Text>
                                <Text className="text-xs text-gray-500 dark:text-gray-400">
                                    {formatFileSize(selectedFile.size)}
                                </Text>
                                <Button
                                    type="button"
                                    outline
                                    className="mt-2"
                                    onClick={(e: React.MouseEvent) => {
                                        e.stopPropagation();
                                        handleRemoveFile();
                                    }}
                                    disabled={disabled}
                                >
                                    Remove
                                </Button>
                            </>
                        ) : (
                            <>
                                <ArrowUpTrayIcon className="mx-auto h-12 w-12 text-gray-400" data-slot="icon" />
                                <Text className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Drop your CSV file here, or click to browse
                                </Text>
                                <Text className="text-xs text-gray-500 dark:text-gray-400">
                                    Supports: CSV files up to 5MB
                                </Text>
                            </>
                        )}
                    </div>
                </div>

                {/* Status messages */}
                {status.type === 'success' && status.message && (
                    <div className="mt-3 flex items-center gap-2 p-3 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-md">
                        <CheckCircleIcon className="h-5 w-5 text-green-500" data-slot="icon" />
                        <Text className="text-sm text-green-700 dark:text-green-400">{status.message}</Text>
                    </div>
                )}

                {status.type === 'error' && status.message && (
                    <div className="mt-3 flex items-center gap-2 p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-md">
                        <XCircleIcon className="h-5 w-5 text-red-500" data-slot="icon" />
                        <Text className="text-sm text-red-700 dark:text-red-400">{status.message}</Text>
                    </div>
                )}

                {/* Upload progress */}
                {status.type === 'uploading' && status.progress !== undefined && (
                    <div className="mt-3">
                        <div className="flex items-center justify-between text-sm">
                            <Text className="text-gray-600 dark:text-gray-400">Uploading...</Text>
                            <Text className="text-gray-600 dark:text-gray-400">{status.progress}%</Text>
                        </div>
                        <div className="mt-1 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                className="bg-firefly-600 h-2 rounded-full transition-all duration-300"
                                style={{ width: `${status.progress}%` }}
                            />
                        </div>
                    </div>
                )}

                {/* CSV Format Help */}
                <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-md">
                    <Text className="text-sm text-blue-800 dark:text-blue-400 font-medium mb-2">
                        CSV Format Requirements:
                    </Text>
                    <Text className="text-xs text-blue-700 dark:text-blue-300">
                        • Column 1: Organization Name (required)<br />
                        • Column 2: Description (optional)<br />
                        • Column 3: Website Link (optional)<br />
                        • First row should contain column headers<br />
                        • Use UTF-8 encoding for special characters
                    </Text>
                </div>
            </div>

            {error && <ErrorMessage>{error}</ErrorMessage>}
        </Field>
    );
}