import React, { useState, FormEvent } from 'react';
import { Head, router } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Button } from '@ui/primitives/button';
import { Input } from '@ui/primitives/input';
import { Field, Label, Description } from '@ui/primitives/fieldset';
import { Text } from '@ui/primitives/text';
import { Link } from '@ui/primitives/link';
import { Heading } from '@ui/primitives/heading';
import { ChevronLeftIcon } from '@heroicons/react/20/solid';
import axios from 'axios';

interface OtpRequestProps {
    status?: string;
}

export default function OtpRequest({ status }: Readonly<OtpRequestProps>) {
    const [email, setEmail] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [retryAfter, setRetryAfter] = useState<number | null>(null);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setError(null);

        try {
            const response = await axios.post(route('otp.send'), { email });

            if (response.data.success) {
                router.visit(route('otp.verify'));
            }
        } catch (err: any) {
            if (err.response?.status === 429) {
                setRetryAfter(err.response.data.retry_after);
                setError(err.response.data.message);
            } else if (err.response?.status === 403) {
                setError(err.response.data.message);
            } else if (err.response?.status === 422) {
                const errors = err.response.data.errors;
                setError(errors?.email?.[0] || 'Please enter a valid email address.');
            } else {
                setError('An error occurred. Please try again.');
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <FrontendLayout>
            <Head title="Sign in with Email" />

            <div className="max-w-lg mx-auto mt-8 p-6">
                {/* Back Navigation */}
                <Link
                    href={route('sign.in')}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6"
                >
                    <ChevronLeftIcon className="h-4 w-4 mr-1" />
                    Back to Sign In
                </Link>

                <div className="text-center mb-8">
                    <Heading level={1}>Sign in with Email</Heading>
                    <Text className="mt-2 text-gray-600 dark:text-gray-400">
                        Enter your email address to receive a one-time password
                    </Text>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Field>
                        <Label htmlFor="email">Email Address</Label>
                        <Input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
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
                            {retryAfter && (
                                <Text className="text-sm text-red-600 dark:text-red-400 mt-1">
                                    Please try again in {Math.ceil(retryAfter / 60)} minute(s).
                                </Text>
                            )}
                        </div>
                    )}

                    <div className="flex justify-end">
                        <Button
                            type="submit"
                            disabled={isLoading || !email}
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
                                    Sending...
                                </>
                            ) : (
                                'Send Code'
                            )}
                        </Button>
                    </div>

                    <div className="relative mt-10">
                        <div aria-hidden="true" className="absolute inset-0 flex items-center">
                            <div className="w-full border-t border-gray-200" />
                        </div>
                        <div className="relative flex justify-center text-sm/6 font-medium">
                            <span className="bg-white px-6 text-gray-900">Or</span>
                        </div>
                    </div>

                    <div className="text-center">
                        <Link
                            href={route('sign.in')}
                            className="text-sm text-firefly-600 hover:text-firefly-500"
                        >
                            Sign in with password instead
                        </Link>
                    </div>
                </form>
            </div>
        </FrontendLayout>
    );
}
