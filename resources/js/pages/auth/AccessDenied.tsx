import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Button } from '@ui/primitives/button';
import { Badge } from '@ui/primitives/badge';
import { Text } from '@ui/primitives/text';
import { Heading } from '@ui/primitives/heading';
import { 
    ShieldExclamationIcon, 
    EnvelopeIcon, 
    DocumentDuplicateIcon,
    CheckIcon,
    ArrowLeftIcon,
    HomeIcon
} from '@heroicons/react/24/outline';
import { UserWithRoles } from '@/types';

interface AccessDeniedProps {
    requiredRoles: string[];
    contactEmail: string;
    attemptedRoute?: string;
    auth: {
        user: UserWithRoles;
    };
}

export default function AccessDenied({ 
    requiredRoles, 
    contactEmail = 'cdf@unesco.org', 
    attemptedRoute,
    auth 
}: AccessDeniedProps) {
    const [emailCopied, setEmailCopied] = useState(false);
    const user = auth.user;
    const userRoles = user.roles || [];

    // Generate mailto link with pre-filled content
    const generateMailtoLink = () => {
        const subject = encodeURIComponent(`Role Access Request - ${user.name}`);
        
        const bodyLines = [
            `Dear Ocean Decade Portal Administrator,`,
            ``,
            `I am requesting access to the following role(s): ${requiredRoles.join(', ')}`,
            ``,
            `User Information:`,
            `- Name: ${user.name}`,
            `- Email: ${user.email}`,
            `- User ID: ${user.id}`,
            userRoles.length > 0 ? `- Current Roles: ${userRoles.map(r => r.name).join(', ')}` : `- Current Roles: None`,
            attemptedRoute ? `- Attempted to access: ${attemptedRoute}` : '',
            ``,
            `Reason for request:`,
            `[Please provide your reason here]`,
            ``,
            `Thank you for your consideration.`,
            ``,
            `Best regards,`,
            `${user.name}`
        ].filter(Boolean).join('%0A');

        return `mailto:${contactEmail}?subject=${subject}&body=${bodyLines}`;
    };

    // Handle email copy
    const handleCopyEmail = async () => {
        try {
            await navigator.clipboard.writeText(contactEmail);
            setEmailCopied(true);
            setTimeout(() => setEmailCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy email:', err);
        }
    };

    // Handle navigation
    const handleGoBack = () => {
        window.history.back();
    };

    const handleGoToDashboard = () => {
        router.visit('/dashboard');
    };

    return (
        <FrontendLayout>
            <Head title="Access Denied" />
            
            <div className="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                {/* Icon and Title Section */}
                <div className="text-center mb-8">
                    <div className="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                        <ShieldExclamationIcon 
                            className="h-12 w-12 text-red-600 dark:text-red-400" 
                            aria-hidden="true"
                        />
                    </div>
                    
                    <Heading level={1} className="text-3xl font-bold text-zinc-900 dark:text-white mb-2">
                        Access Denied
                    </Heading>
                    
                    <Text className="text-3xl text-firefly-600 dark:text-zinc-400">
                        You don't have the required permissions to access this resource.
                    </Text>
                </div>


                {/* Navigation Actions */}
                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <Button
                        type="button"
                        color="firefly"
                        onClick={handleGoBack}
                        className="inline-flex items-center gap-2"
                    >
                        <ArrowLeftIcon data-slot="icon" />
                        Go Back
                    </Button>
                </div>
            </div>
        </FrontendLayout>
    );
}