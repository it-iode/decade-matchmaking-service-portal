import React, { useRef, useEffect, FormEvent, ChangeEvent } from 'react';
import { Button } from '@ui/primitives/button';
import { Input } from '@ui/primitives/input';
import { Text } from '@ui/primitives/text';
import { EnvelopeIcon } from '@heroicons/react/20/solid';

const CODE_LENGTH = 6;

interface OtpVerifyStepProps {
    maskedEmail: string;
    code: string;
    onCodeChange: (code: string) => void;
    onSubmit: (code?: string) => void;
    onResend: () => void;
    onBack: () => void;
    isProcessing: boolean;
    error?: string;
    resendCooldown: number;
}

export function OtpVerifyStep({
    maskedEmail,
    code,
    onCodeChange,
    onSubmit,
    onResend,
    onBack,
    isProcessing,
    error,
    resendCooldown,
}: Readonly<OtpVerifyStepProps>) {
    const inputRef = useRef<HTMLInputElement>(null);

    // Focus input on mount
    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    // Refocus when code is cleared (e.g., after error)
    useEffect(() => {
        if (code === '') {
            inputRef.current?.focus();
        }
    }, [code]);

    const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
        // Only allow digits, max CODE_LENGTH characters
        const value = e.target.value.replace(/\D/g, '').slice(0, CODE_LENGTH);
        onCodeChange(value);

        // Auto-submit when all digits entered
        if (value.length === CODE_LENGTH) {
            onSubmit(value);
        }
    };

    const handleFormSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (code.length === CODE_LENGTH) {
            onSubmit(code);
        }
    };

    return (
        <form onSubmit={handleFormSubmit} className="space-y-6">
            {/* Email indicator */}
            <div className="flex items-center justify-center gap-2">
                <EnvelopeIcon className="h-5 w-5 text-gray-400" />
                <Text className="text-gray-600 dark:text-gray-400">
                    {maskedEmail}
                </Text>
            </div>

            {/* Single OTP Input */}
            <div className="flex justify-center">
                <Input
                    ref={inputRef}
                    type="text"
                    inputMode="numeric"
                    pattern="\d*"
                    maxLength={CODE_LENGTH}
                    value={code}
                    onChange={handleChange}
                    placeholder="000000"
                    className={`w-56 sm:w-64 text-center text-2xl sm:text-3xl font-mono tracking-[0.4em] placeholder:tracking-[0.4em] ${
                        error ? 'border-red-500' : ''
                    }`}
                    disabled={isProcessing}
                    aria-label={`Enter ${CODE_LENGTH}-digit verification code`}
                    aria-describedby={error ? 'otp-error' : undefined}
                />
            </div>

            {/* Error Message */}
            {error && (
                <div
                    id="otp-error"
                    role="alert"
                    aria-live="polite"
                    className="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-center transition-opacity duration-200"
                >
                    <Text className="text-sm text-red-600 dark:text-red-400">
                        {error}
                    </Text>
                </div>
            )}

            {/* Verify Button */}
            <div className="flex justify-center">
                <Button
                    type="submit"
                    disabled={isProcessing || code.length !== CODE_LENGTH}
                    color="firefly"
                >
                    {isProcessing ? (
                        <>
                            <svg
                                className="animate-spin -ml-1 mr-2 h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
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
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                            Verifying...
                        </>
                    ) : (
                        'Verify Code'
                    )}
                </Button>
            </div>

            {/* Footer Actions */}
            <div className="text-center space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <Text className="text-sm text-gray-600 dark:text-gray-400">
                        Didn&apos;t receive the code?{' '}
                    </Text>
                    {resendCooldown > 0 ? (
                        <Text className="text-sm text-gray-500">
                            Resend in {resendCooldown}s
                        </Text>
                    ) : (
                        <button
                            type="button"
                            onClick={onResend}
                            disabled={isProcessing}
                            className="text-sm text-firefly-600 hover:text-firefly-500 disabled:opacity-50"
                        >
                            Resend code
                        </button>
                    )}
                </div>

                <div>
                    <button
                        type="button"
                        onClick={onBack}
                        disabled={isProcessing}
                        className="text-sm text-gray-600 hover:text-gray-500 disabled:opacity-50"
                    >
                        Use a different email
                    </button>
                </div>
            </div>
        </form>
    );
}
