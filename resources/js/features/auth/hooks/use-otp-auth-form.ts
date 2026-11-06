import axios, { AxiosError } from 'axios';
import { useState, useCallback, useEffect } from 'react';

type OtpStep = 'email' | 'verify';
type StepDirection = 'forward' | 'backward';

interface OtpFormData {
    email: string;
    code: string;
}

interface OtpFormErrors {
    email?: string;
    code?: string;
}

interface OtpApiResponse {
    success: boolean;
    message?: string;
    maskedEmail?: string;
    redirect?: string;
    error?: string;
    error_code?: string;
    retry_after?: number;
}

interface OtpApiErrorResponse {
    message?: string;
    error?: string;
    error_code?: string;
    retry_after?: number;
    errors?: {
        email?: string[];
        code?: string[];
    };
}

export function useOtpAuthForm() {
    const [data, setData] = useState<OtpFormData>({
        email: '',
        code: '',
    });
    const [errors, setErrors] = useState<OtpFormErrors>({});
    const [processing, setProcessing] = useState(false);

    const [step, setStep] = useState<OtpStep>('email');
    const [stepDirection, setStepDirection] = useState<StepDirection>('forward');
    const [maskedEmail, setMaskedEmail] = useState('');
    const [resendCooldown, setResendCooldown] = useState(0);

    // Countdown timer for resend cooldown
    useEffect(() => {
        if (resendCooldown > 0) {
            const timer = setTimeout(() => setResendCooldown(resendCooldown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [resendCooldown]);

    const setFormData = useCallback((key: keyof OtpFormData, value: string) => {
        setData(prev => ({ ...prev, [key]: value }));
    }, []);

    const clearErrors = useCallback(() => {
        setErrors({});
    }, []);

    const maskEmail = useCallback((email: string): string => {
        const [local, domain] = email.split('@');
        const maskedLocal = local.length > 2
            ? local[0] + '***' + local[local.length - 1]
            : '***';
        return `${maskedLocal}@${domain}`;
    }, []);

    const handleSendOtp = useCallback(async () => {
        setProcessing(true);
        setErrors({});

        try {
            const response = await axios.post<OtpApiResponse>(route('otp.send'), {
                email: data.email,
            });

            if (response.data.success) {
                // Get masked email from response or generate fallback
                if (response.data.maskedEmail) {
                    setMaskedEmail(response.data.maskedEmail);
                } else {
                    setMaskedEmail(maskEmail(data.email));
                }
                setStepDirection('forward');
                setStep('verify');
                setResendCooldown(60);
            }
        } catch (err) {
            const axiosError = err as AxiosError<OtpApiErrorResponse>;
            const responseData = axiosError.response?.data;

            if (axiosError.response?.status === 429) {
                if (responseData?.retry_after) {
                    setResendCooldown(responseData.retry_after);
                }
                setErrors({ email: responseData?.message || 'Too many requests. Please try again later.' });
            } else if (axiosError.response?.status === 403) {
                setErrors({ email: responseData?.message || 'Access denied.' });
            } else if (axiosError.response?.status === 422) {
                const validationErrors = responseData?.errors;
                setErrors({
                    email: validationErrors?.email?.[0] || 'Please enter a valid email address.',
                });
            } else {
                setErrors({ email: 'An error occurred. Please try again.' });
            }
        } finally {
            setProcessing(false);
        }
    }, [data.email, maskEmail]);

    const handleVerifyOtp = useCallback(async (onSuccess: (redirect?: string) => void, codeOverride?: string) => {
        setProcessing(true);
        setErrors({});

        try {
            const codeToSubmit = codeOverride ?? data.code;
            const response = await axios.post<OtpApiResponse>(route('otp.verify.submit'), {
                code: codeToSubmit,
            });

            if (response.data.success) {
                onSuccess(response.data.redirect);
            }
        } catch (err) {
            const axiosError = err as AxiosError<OtpApiErrorResponse>;
            const responseData = axiosError.response?.data;

            // Handle specific error codes from Spatie OTP
            switch (responseData?.error_code) {
                case 'expired':
                    setErrors({ code: responseData.message || 'Code has expired. Please request a new one.' });
                    break;
                case 'rate_limited':
                    setErrors({ code: responseData.message || 'Too many attempts. Please try again later.' });
                    if (responseData?.retry_after) {
                        setResendCooldown(responseData.retry_after);
                    }
                    break;
                case 'not_found':
                case 'no_email':
                    // Session expired, need to start over
                    setErrors({ code: responseData.message || 'Session expired. Please start over.' });
                    setStepDirection('backward');
                    setStep('email');
                    break;
                case 'origin_mismatch':
                    // Security check failed
                    setErrors({ code: responseData.message || 'Security verification failed. Please start over.' });
                    setStepDirection('backward');
                    setStep('email');
                    break;
                case 'invalid_code':
                default:
                    setErrors({ code: responseData?.message || 'Invalid code. Please try again.' });
                    break;
            }

            // Clear code on error
            setData(prev => ({ ...prev, code: '' }));
        } finally {
            setProcessing(false);
        }
    }, [data.code]);

    const handleResend = useCallback(async () => {
        setProcessing(true);
        setErrors({});

        try {
            await axios.post<OtpApiResponse>(route('otp.resend'));
            setResendCooldown(60);
            setData(prev => ({ ...prev, code: '' }));
        } catch (err) {
            const axiosError = err as AxiosError<OtpApiErrorResponse>;
            const responseData = axiosError.response?.data;

            if (axiosError.response?.status === 429) {
                const retryAfter = responseData?.retry_after;
                if (retryAfter) {
                    setResendCooldown(retryAfter);
                }
                setErrors({ code: responseData?.message || 'Please wait before requesting a new code.' });
            } else if (axiosError.response?.status === 400) {
                // No email in session
                setErrors({ code: responseData?.message || 'Session expired. Please start over.' });
                setStepDirection('backward');
                setStep('email');
            } else {
                setErrors({ code: 'Failed to resend OTP. Please try again.' });
            }
        } finally {
            setProcessing(false);
        }
    }, []);

    const handleBack = useCallback(() => {
        setStepDirection('backward');
        setStep('email');
        setData(prev => ({ ...prev, code: '' }));
        setErrors({});
    }, []);

    const reset = useCallback(() => {
        setStep('email');
        setStepDirection('forward');
        setMaskedEmail('');
        setResendCooldown(0);
        setData({ email: '', code: '' });
        setErrors({});
        setProcessing(false);
    }, []);

    // Create a form-like object for backward compatibility
    const form = {
        data,
        errors,
        processing,
        setData: setFormData,
        clearErrors,
    };

    return {
        form,
        step,
        stepDirection,
        maskedEmail,
        resendCooldown,
        setResendCooldown,
        handleSendOtp,
        handleVerifyOtp,
        handleResend,
        handleBack,
        reset,
    };
}