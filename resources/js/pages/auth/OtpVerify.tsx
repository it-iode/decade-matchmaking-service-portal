import { useState, useRef, useEffect, FormEvent, ChangeEvent } from 'react';
import { Head, router } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Button } from '@ui/primitives/button';
import { Input } from '@ui/primitives/input';
import { Text } from '@ui/primitives/text';
import { Link } from '@ui/primitives/link';
import { Heading } from '@ui/primitives/heading';
import { ChevronLeftIcon, EnvelopeIcon } from '@heroicons/react/20/solid';
import axios from 'axios';

const CODE_LENGTH = 6;

interface OtpVerifyProps {
    email: string;
    maskedEmail: string;
}

export default function OtpVerify({ email, maskedEmail }: Readonly<OtpVerifyProps>) {
    const [code, setCode] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isResending, setIsResending] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [resendCooldown, setResendCooldown] = useState(0);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    useEffect(() => {
        if (resendCooldown > 0) {
            const timer = setTimeout(() => setResendCooldown(resendCooldown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [resendCooldown]);

    // Refocus when code is cleared (e.g., after error)
    useEffect(() => {
        if (code === '') {
            inputRef.current?.focus();
        }
    }, [code]);

    const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
        // Only allow digits, max CODE_LENGTH characters
        const value = e.target.value.replace(/\D/g, '').slice(0, CODE_LENGTH);
        setCode(value);

        // Auto-submit when all digits entered
        if (value.length === CODE_LENGTH) {
            handleSubmit(value);
        }
    };

    const handleSubmit = async (otpCode?: string) => {
        const codeToSubmit = otpCode || code;

        if (codeToSubmit.length !== CODE_LENGTH) {
            setError(`Please enter all ${CODE_LENGTH} digits`);
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const response = await axios.post(route('otp.verify.submit'), {
                code: codeToSubmit,
            });

            if (response.data.success && response.data.redirect) {
                setShowSuccess(true);
                setTimeout(() => router.visit(response.data.redirect), 800);
            }
        } catch (err: any) {
            const data = err.response?.data;

            // Handle specific error codes from Spatie OTP
            switch (data?.error_code) {
                case 'expired':
                    setError(data.message || 'Code has expired. Please request a new one.');
                    break;
                case 'rate_limited':
                    setError(data.message || 'Too many attempts. Please try again later.');
                    if (data?.retry_after) {
                        setResendCooldown(data.retry_after);
                    }
                    break;
                case 'not_found':
                case 'no_email':
                    router.visit(route('otp.request'));
                    return;
                case 'origin_mismatch':
                    setError(data.message || 'Security verification failed. Please start over.');
                    setTimeout(() => router.visit(route('otp.request')), 2000);
                    break;
                case 'invalid_code':
                default:
                    setError(data?.message || 'Invalid code. Please try again.');
                    break;
            }

            setCode('');
        } finally {
            setIsLoading(false);
        }
    };

    const handleResend = async () => {
        setIsResending(true);
        setError(null);

        try {
            await axios.post(route('otp.resend'));
            setResendCooldown(60);
        } catch (err: any) {
            if (err.response?.status === 429) {
                const retryAfter = err.response.data.retry_after;
                setResendCooldown(retryAfter);
                setError(err.response.data.message);
            } else {
                setError('Failed to resend OTP. Please try again.');
            }
        } finally {
            setIsResending(false);
        }
    };

    const handleFormSubmit = (e: FormEvent) => {
        e.preventDefault();
        handleSubmit();
    };

    return (
        <FrontendLayout>
            <Head title="Verify OTP" />

            <div className="max-w-lg mx-auto mt-8 p-6">
                {/* Back Navigation */}
                <Link
                    href={route('otp.request')}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6"
                >
                    <ChevronLeftIcon className="h-4 w-4 mr-1" />
                    Back
                </Link>

                <div className="text-center mb-8">
                    <Heading level={1}>Enter Verification Code</Heading>
                    <div className="flex items-center justify-center gap-2 mt-2">
                        <EnvelopeIcon className="h-5 w-5 text-gray-400" />
                        <Text className="text-gray-600 dark:text-gray-400">
                            We sent a {CODE_LENGTH}-digit code to {maskedEmail}
                        </Text>
                    </div>
                </div>

                <form onSubmit={handleFormSubmit} className="space-y-6">
                    {/* Success State */}
                    {showSuccess && (
                        <div
                            role="status"
                            className="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-center transition-opacity duration-200"
                        >
                            <Text className="text-sm text-green-600 dark:text-green-400">
                                Verified! Redirecting...
                            </Text>
                        </div>
                    )}

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
                            className={`w-56 sm:w-64 h-14 sm:h-16 text-center text-2xl sm:text-3xl font-mono tracking-[0.4em] placeholder:tracking-[0.4em] ${
                                error ? 'border-red-500' : ''
                            }`}
                            disabled={isLoading || showSuccess}
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

                    {/* Submit Button */}
                    <div className="flex justify-center">
                        <Button
                            type="submit"
                            disabled={isLoading || code.length !== CODE_LENGTH || showSuccess}
                            color="firefly"
                        >
                            {isLoading ? (
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

                    {/* Footer Links */}
                    <div className="text-center space-y-3 mt-8">
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
                                    onClick={handleResend}
                                    disabled={isResending}
                                    className="text-sm text-firefly-600 hover:text-firefly-500 disabled:opacity-50"
                                >
                                    {isResending ? 'Sending...' : 'Resend code'}
                                </button>
                            )}
                        </div>

                        <div>
                            <Link
                                href={route('otp.request')}
                                className="text-sm text-gray-600 hover:text-gray-500"
                            >
                                Use a different email
                            </Link>
                        </div>

                        <div className="pt-4">
                            <Link
                                href={route('sign.in')}
                                className="text-sm text-firefly-600 hover:text-firefly-500"
                            >
                                Sign in with password instead
                            </Link>
                        </div>
                    </div>
                </form>
            </div>
        </FrontendLayout>
    );
}
