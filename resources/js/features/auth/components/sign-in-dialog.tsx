import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogBody, DialogDescription, DialogTitle } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { SignInForm } from '@ui/organisms/forms';
import { useOtpAuthForm } from '../hooks/use-otp-auth-form';
import { OtpEmailStep } from './otp-email-step';
import { OtpVerifyStep } from './otp-verify-step';
import { XMarkIcon } from '@heroicons/react/20/solid';

type DialogView = 'main' | 'otp';

export default function SignInDialog() {
    const [isOpen, setIsOpen] = useState(false);
    const [view, setView] = useState<DialogView>('main');
    const otpForm = useOtpAuthForm();

    const getDialogContent = () => {
        if (view === 'main') {
            return {
                title: 'Sign In',
                description: 'Enter your OceanExpert credentials to access the portal.',
            };
        }

        if (otpForm.step === 'email') {
            return {
                title: 'Sign In with Email OTP',
                description: 'Enter your email address to receive a one-time password.',
            };
        }

        return {
            title: 'Verify Your Email',
            description: `We sent a 6-digit code to your email. Please enter it below.`,
        };
    };

    const dialogContent = getDialogContent();

    const handleClose = () => {
        setIsOpen(false);
        setView('main');
        otpForm.reset();
    };

    const handleOtpSuccess = (redirect?: string) => {
        handleClose();
        if (redirect) {
            router.visit(redirect);
        }
    };

    const handleOtpClick = () => {
        setView('otp');
    };

    const handleCancelOtp = () => {
        setView('main');
        otpForm.reset();
    };

    return (
        <div>
            <button
                onClick={() => setIsOpen(true)}
                className="px-4 py-2 bg-white text-black rounded hover:bg-firefly-700"
            >
                Sign In
            </button>
            <Dialog size="xl" open={isOpen} onClose={handleClose} closeOnBackdropClick={false}>
                {/* Header with close button */}
                <div className="flex items-start justify-between">
                    <div>
                        <DialogTitle>{dialogContent.title}</DialogTitle>
                        <DialogDescription>{dialogContent.description}</DialogDescription>
                    </div>
                    <Button
                        plain
                        onClick={handleClose}
                        className="-m-1.5 rounded-md p-1.5 text-zinc-400 hover:text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-firefly-500"
                        aria-label="Close dialog"
                    >
                        <XMarkIcon data-slot="icon" className="size-5" aria-hidden="true" />
                    </Button>
                </div>

                <DialogBody>
                    <div className="relative overflow-hidden">
                        {view === 'main' && (
                            <SignInForm
                                showOAuthOptions={true}
                                showOtpOption={true}
                                onOtpClick={handleOtpClick}
                                isOtpProcessing={otpForm.form.processing}
                            />
                        )}

                        {view === 'otp' && otpForm.step === 'email' && (
                            <OtpEmailStep
                                email={otpForm.form.data.email}
                                onEmailChange={(email) => otpForm.form.setData('email', email)}
                                onSubmit={otpForm.handleSendOtp}
                                onCancel={handleCancelOtp}
                                isProcessing={otpForm.form.processing}
                                error={otpForm.form.errors.email}
                            />
                        )}

                        {view === 'otp' && otpForm.step === 'verify' && (
                            <OtpVerifyStep
                                maskedEmail={otpForm.maskedEmail}
                                code={otpForm.form.data.code}
                                onCodeChange={(code) => otpForm.form.setData('code', code)}
                                onSubmit={(code) => otpForm.handleVerifyOtp(handleOtpSuccess, code)}
                                onResend={otpForm.handleResend}
                                onBack={otpForm.handleBack}
                                isProcessing={otpForm.form.processing}
                                error={otpForm.form.errors.code}
                                resendCooldown={otpForm.resendCooldown}
                            />
                        )}
                    </div>
                </DialogBody>
            </Dialog>
        </div>
    );
}
