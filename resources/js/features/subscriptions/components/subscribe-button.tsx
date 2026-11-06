import React, { useState, useEffect } from 'react';
import { Button } from '@ui/primitives/button';
import { BellIcon, BellSlashIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';

interface SubscribeButtonProps {
    requestId: number;
    className?: string;
}

export default function SubscribeButton({
    requestId,
    className = ''
}: SubscribeButtonProps) {
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [isCheckingStatus, setIsCheckingStatus] = useState(true);

    // Check subscription status on component mount
    useEffect(() => {
        checkSubscriptionStatus();
    }, [requestId]);

    const checkSubscriptionStatus = async () => {
        try {
            const response = await fetch(route('user.subscriptions.status'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ request_id: requestId }),
            });

            if (response.ok) {
                const data = await response.json();
                setIsSubscribed(data.is_subscribed);
            }
        } catch (error) {
            console.error('Failed to check subscription status:', error);
        } finally {
            setIsCheckingStatus(false);
        }
    };

    const handleSubscriptionToggle = async () => {
        setIsLoading(true);

        try {
            const endpoint = isSubscribed
                ? route('user.subscriptions.unsubscribe')
                : route('user.subscriptions.subscribe');

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ request_id: requestId }),
            });

            const data = await response.json();

            if (data.success) {
                setIsSubscribed(!isSubscribed);

                console.log('Subscription updated successfully:', data.message);
            } else {
                console.error('Failed to update subscription:', data.message);
            }
        } catch (error) {
            console.error('Subscription error:', error);
        } finally {
            setIsLoading(false);
        }
    };

    if (isCheckingStatus) {
        return (
            <Button
                disabled
                className={className}
            >
                <div className="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-gray-600"></div>
            </Button>
        );
    }

    return (
        <Button
            onClick={handleSubscriptionToggle}
            disabled={isLoading}
            className={className}
        >
            {isLoading ? (
                <div className="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-gray-600"></div>
            ) : (
                <>
                    {isSubscribed ? (
                        <BellSlashIcon data-slot="icon" className="h-4 w-4" />
                    ) : (
                        <BellIcon data-slot="icon" className="h-4 w-4" />
                    )}
                    {isSubscribed ? 'Unsubscribe' : 'Subscribe'}
                </>
            )}
        </Button>
    );
}
