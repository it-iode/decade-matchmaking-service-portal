import { Head, router } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { SignInForm } from '@ui/organisms/forms';

export default function SignIn() {
    const handleOtpClick = () => {
        router.visit(route('otp.request'));
    };

    return (
        <FrontendLayout>
            <Head title="Welcome" />
            <div className="max-w-lg mx-auto mt-8 p-6">
                <SignInForm onOtpClick={handleOtpClick} />
            </div>
        </FrontendLayout>
    );
}
