import React from 'react';

interface OtpEntryButtonProps {
    onClick: () => void;
    disabled?: boolean;
}

function EmailIcon() {
    return (
        <svg className="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    );
}

export function OtpEntryButton({ onClick, disabled = false }: Readonly<OtpEntryButtonProps>) {
    const buttonClass = `w-full flex items-center justify-center bg-white border border-gray-300 rounded-lg shadow-md px-6 py-2 text-sm font-medium text-gray-800 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`;

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className={buttonClass}
            aria-label="Sign in with Email OTP"
        >
            <EmailIcon />
            <span>Sign in with Email OTP</span>
        </button>
    );
}
