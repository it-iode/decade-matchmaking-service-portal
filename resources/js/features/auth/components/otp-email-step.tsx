import React, { FormEvent } from 'react';
import { Button } from '@ui/primitives/button';
import { Input } from '@ui/primitives/input';
import { Field, Label, Description } from '@ui/primitives/fieldset';
import { Text } from '@ui/primitives/text';

interface OtpEmailStepProps {
    email: string;
    onEmailChange: (email: string) => void;
    onSubmit: () => void;
    onCancel: () => void;
    isProcessing: boolean;
    error?: string;
}

export function OtpEmailStep({
    email,
    onEmailChange,
    onSubmit,
    onCancel,
    isProcessing,
    error,
}: Readonly<OtpEmailStepProps>) {
    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit();
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Field>
                <Label htmlFor="otp-email">Email Address</Label>
                <Input
                    id="otp-email"
                    type="email"
                    value={email}
                    onChange={(e) => onEmailChange(e.target.value)}
                    placeholder="you@example.com"
                    required
                    autoFocus
                    autoComplete="email"
                    className={error ? 'border-red-500' : ''}
                />
                <Description className="text-center" aria-hidden="true">
                    If you have never logged into Ocean Connector, OTP will not work
                </Description>
                <Description className="text-center" aria-hidden="true">
                    We&apos;ll send a 6-digit code to this email.
                </Description>
            </Field>

            {error && (
                <div
                    role="alert"
                    aria-live="polite"
                    className="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg transition-opacity duration-200"
                >
                    <Text className="text-sm text-red-600 dark:text-red-400">
                        {error}
                    </Text>
                </div>
            )}

            <div className="flex justify-between gap-3">
                <Button
                    type="button"
                    outline
                    onClick={onCancel}
                    disabled={isProcessing}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={isProcessing || !email}
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
                            Sending...
                        </>
                    ) : (
                        'Send Code'
                    )}
                </Button>
            </div>
        </form>
    );
}
