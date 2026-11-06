/**
 * FileUploadDialog Component
 *
 * Generic file upload dialog that can be used for any entity's file upload actions.
 * Supports drag-and-drop, file validation, and progress tracking.
 * Works with both dialog handler (dialog_props) and file_upload handler approaches.
 */

import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogBody, DialogDescription, DialogTitle, DialogActions } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { DocumentArrowUpIcon, DocumentIcon, XCircleIcon } from '@heroicons/react/16/solid';
import type { EntityAction, FileUploadMetadata } from '@/types/actions';
import { ActionHandlerGuards } from '@/types/actions';

interface FileUploadDialogProps {
    isOpen: boolean;
    onClose: () => void;
    /** Optional EntityAction for file_upload handler approach */
    action?: EntityAction;
    /** Optional direct fileUploadMeta for dialog handler approach */
    fileUploadMeta?: FileUploadMetadata;
    onSuccess?: () => void;
}

export function FileUploadDialog({ isOpen, onClose, action, fileUploadMeta: propFileUploadMeta, onSuccess }: FileUploadDialogProps) {
    // Support both approaches: direct props or from EntityAction
    const fileUploadMeta = propFileUploadMeta ||
        (action && ActionHandlerGuards.isFileUploadHandler(action.metadata)
            ? action.metadata.file_upload
            : undefined);
    const [dragActive, setDragActive] = useState(false);
    const [preview, setPreview] = useState<{ name: string; size: number } | null>(null);
    const [validationError, setValidationError] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset, progress } = useForm<{
        document: File | null;
    }>({
        document: null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!fileUploadMeta?.endpoint || !data.document) return;

        post(fileUploadMeta.endpoint, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPreview(null);
                setValidationError(null);
                onClose();
                onSuccess?.();
            },
            onError: (errors) => {
                console.error('Upload failed:', errors);
            },
        });
    };

    const validateFile = (file: File): string | null => {
        const meta = fileUploadMeta?.validationRules;

        if (meta?.mimeTypes && !meta.mimeTypes.includes(file.type)) {
            return `File type not allowed. Accepted types: ${fileUploadMeta?.accept}`;
        }

        if (meta?.maxSizeBytes && file.size > meta.maxSizeBytes) {
            return `File size exceeds maximum of ${fileUploadMeta?.maxSize}MB`;
        }

        return null;
    };

    const handleFileSelect = (file: File) => {
        const error = validateFile(file);

        if (error) {
            setValidationError(error);
            setData('document', null);
            setPreview(null);
            return;
        }

        setValidationError(null);
        setData('document', file);
        setPreview({ name: file.name, size: file.size });
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        setDragActive(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setDragActive(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setDragActive(false);

        const file = e.dataTransfer.files?.[0];
        if (file) handleFileSelect(file);
    };

    const formatFileSize = (bytes: number): string => {
        const sizes = ['Bytes', 'KB', 'MB'];
        if (bytes === 0) return '0 Bytes';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + ' ' + sizes[i];
    };

    const handleRemove = (e: React.MouseEvent) => {
        e.stopPropagation();
        setData('document', null);
        setPreview(null);
        setValidationError(null);
    };

    return (
        <Dialog open={isOpen} onClose={onClose} size="lg">
            <DialogTitle>{fileUploadMeta?.title || 'Upload File'}</DialogTitle>
            {fileUploadMeta?.description && (
                <DialogDescription>{fileUploadMeta.description}</DialogDescription>
            )}

            <DialogBody>
                <form onSubmit={handleSubmit} id="file-upload-form">
                    {/* Drop Zone */}
                    <div
                        className={`
                            mt-2 border-2 border-dashed rounded-lg p-8 text-center transition-colors
                            ${
                                dragActive
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20'
                                    : preview
                                      ? 'border-green-500 bg-green-50 dark:bg-green-950/20'
                                      : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'
                            }
                            ${processing ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                        `}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                        onClick={() => !processing && document.getElementById('file-input')?.click()}
                    >
                        <input
                            id="file-input"
                            type="file"
                            className="hidden"
                            accept={fileUploadMeta?.accept}
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) handleFileSelect(file);
                            }}
                            disabled={processing}
                        />

                        {preview ? (
                            <div className="space-y-2">
                                <DocumentIcon
                                    className="mx-auto h-12 w-12 text-green-500"
                                    data-slot="icon"
                                />
                                <p className="font-medium text-green-700 dark:text-green-400">
                                    {preview.name}
                                </p>
                                <p className="text-sm text-gray-500">{formatFileSize(preview.size)}</p>
                                <Button type="button" plain onClick={handleRemove} disabled={processing}>
                                    <XCircleIcon data-slot="icon" />
                                    Remove
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                <DocumentArrowUpIcon
                                    className="mx-auto h-12 w-12 text-gray-400"
                                    data-slot="icon"
                                />
                                <p className="font-medium text-gray-700 dark:text-gray-300">
                                    Drop file here or click to browse
                                </p>
                                {fileUploadMeta?.accept && (
                                    <p className="text-sm text-gray-500">
                                        Accepted formats: {fileUploadMeta.accept}
                                    </p>
                                )}
                                {fileUploadMeta?.maxSize && (
                                    <p className="text-sm text-gray-500">
                                        Maximum size: {fileUploadMeta.maxSize}MB
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Validation Error */}
                    {validationError && (
                        <p className="mt-2 text-sm text-red-600 dark:text-red-400">{validationError}</p>
                    )}

                    {/* Server Error */}
                    {errors.document && (
                        <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.document}</p>
                    )}

                    {/* Progress Bar */}
                    {processing && progress && (
                        <div className="mt-4">
                            <div className="flex justify-between text-sm mb-1">
                                <span>Uploading...</span>
                                <span>{Math.round(progress.percentage || 0)}%</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    className="bg-blue-600 h-2 rounded-full transition-all"
                                    style={{ width: `${progress.percentage || 0}%` }}
                                />
                            </div>
                        </div>
                    )}
                </form>
            </DialogBody>

            <DialogActions>
                <Button type="button" plain onClick={onClose} disabled={processing}>
                    Cancel
                </Button>
                <Button
                    type="submit"
                    form="file-upload-form"
                    color="blue"
                    disabled={processing || !data.document}
                >
                    <DocumentArrowUpIcon data-slot="icon" />
                    {processing ? 'Uploading...' : 'Upload'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
