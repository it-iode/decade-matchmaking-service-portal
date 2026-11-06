import React, { useState } from 'react';
import { Button } from '@ui/primitives/button';
import clsx from 'clsx';

interface DownloadButtonProps {
    url: string;
    fileName?: string;
    children: React.ReactNode;
    className?: string;
    outline?: boolean;
    plain?: boolean;
    color?: 'dark/zinc' | 'light' | 'dark/white' | 'dark' | 'white' | 'zinc' |
            'indigo' | 'cyan' | 'red' | 'orange' | 'amber' | 'yellow' | 'lime' |
            'green' | 'emerald' | 'teal' | 'sky' | 'blue' | 'firefly' | 'violet' |
            'purple' | 'fuchsia' | 'pink' | 'rose';
    disabled?: boolean;
    onDownloadStart?: () => void;
    onDownloadComplete?: () => void;
    onDownloadError?: (error: Error) => void;
}

export function DownloadButton({
    url,
    fileName,
    children,
    className,
    outline,
    plain,
    color,
    disabled,
    onDownloadStart,
    onDownloadComplete,
    onDownloadError
}: DownloadButtonProps) {
    const [isDownloading, setIsDownloading] = useState(false);

    const handleDownload = async (e: React.MouseEvent) => {
        e.preventDefault();

        if (disabled || isDownloading) return;

        setIsDownloading(true);
        onDownloadStart?.();

        try {
            // Simple window.location for server-handled downloads
            window.location.href = url;

            // Give some time for download to start, then reset state
            setTimeout(() => {
                setIsDownloading(false);
                onDownloadComplete?.();
            }, 1500);

        } catch (error) {
            console.error('Download failed:', error);
            setIsDownloading(false);
            onDownloadError?.(error as Error);
        }
    };

    return (
        <Button
            onClick={handleDownload}
            color={color}
            disabled={disabled || isDownloading}
            className={clsx(className, isDownloading && 'opacity-75 cursor-wait')}
            type="button"
        >
            {isDownloading ? (
                <>
                    <svg
                        className="animate-spin -ml-1 mr-2 h-4 w-4"
                        data-slot="icon"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                        />
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    Downloading...
                </>
            ) : (
                children
            )}
        </Button>
    );
}
