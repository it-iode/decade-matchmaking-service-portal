import React from 'react';
import {Head, router, usePage} from '@inertiajs/react';
import {FrontendLayout} from '@layouts/index';
import {Avatar} from '@ui/primitives/avatar';
import {Button} from '@ui/primitives/button';
import {FieldRenderer, SignInForm} from '@ui/organisms/forms';
import {LockClosedIcon} from '@heroicons/react/20/solid';
import {Auth} from '@/types';
import {OpportunityFormOptions} from '@features/opportunities/types';
import {useODCTravelSupportForm} from '@features/odc-travel-support';

interface OdcTravelSupportCreateProps {
    formOptions?: OpportunityFormOptions;
}

const FORM_ID = 'odc-travel-support-form';

/**
 * localStorage draft key: a guest's entries survive the sign-in remounts and
 * redirects on this page, and closing the tab (the server keeps nothing until
 * they submit).
 */
const DRAFT_KEY = 'odc-travel-support-draft';

const CARD_CLASS =
    'rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800';

function StepHeading({step, title, id}: Readonly<{ step: number; title: string; id: string }>) {
    return (
        <div className="flex items-center gap-2.5">
            <span
                aria-hidden="true"
                className="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-firefly-600 text-xs font-semibold text-white"
            >
                {step}
            </span>
            <h2 id={id} className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {title}
            </h2>
        </div>
    );
}

export default function OdcTravelSupportCreate({formOptions}: Readonly<OdcTravelSupportCreateProps>) {
    const {auth} = usePage<{ auth: Auth }>().props;
    const user = auth?.user ?? null;
    const isSignedIn = Boolean(user);

    const {form, fields, handleFieldChange, handleSubmit} = useODCTravelSupportForm({formOptions, draftKey: DRAFT_KEY});

    const handleOtpClick = () => {
        router.visit(route('otp.request'));
    };

    // Submitting as a guest routes the user to the sign-in card instead of
    // posting (the endpoint requires authentication); the button stays
    // focusable and explains itself via aria-describedby.
    const focusSignIn = () => {
        const emailInput = document.getElementById('email');
        emailInput?.scrollIntoView({block: 'center', behavior: 'smooth'});
        emailInput?.focus({preventScroll: true});
    };

    const handleFormSubmit = (e: React.FormEvent) => {
        if (!isSignedIn) {
            e.preventDefault();
            focusSignIn();
            return;
        }
        handleSubmit(e);
    };

    const hasErrors = Object.keys(form.errors).length > 0;

    return (
        <FrontendLayout>
            <Head title="Upload a 2027 ODC travel support opportunity"/>

            <div className="mx-auto">
                <div className="max-w-2xl">
                    <p className="text-xs font-medium text-firefly-700 dark:text-firefly-300">
                        2027 Ocean Decade Conference (ODC)
                    </p>
                    <h1 className="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Upload a 2027 ODC travel support opportunity
                    </h1>
                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Share travel support available for the 2027 Ocean Decade Conference. Fill in the
                        details{isSignedIn ? '' : ', sign in'} and submit — all on this one page. No partner
                        registration needed.
                    </p>
                </div>

                <div className="mt-6 flex flex-wrap items-start gap-6">
                    {/* Step 1 — the existing ODC travel support form, unchanged. */}
                    <form
                        id={FORM_ID}
                        onSubmit={handleFormSubmit}
                        noValidate
                        aria-labelledby="odc-details-heading"
                        className={`min-w-0 flex-[999_1_36rem] ${CARD_CLASS}`}
                    >
                        <StepHeading step={1} title="Opportunity details" id="odc-details-heading"/>

                        <div className="mt-4 space-y-5">
                            <div
                                className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800/60">
                                <div className="flex items-center gap-3">
                                    <LockClosedIcon className="size-4 shrink-0 text-gray-400" aria-hidden="true"/>
                                    <span className="text-gray-500 dark:text-gray-400">Type of opportunity</span>
                                    <span className="font-medium text-gray-900 dark:text-gray-100">
                                        2027 ODC Travel Support
                                    </span>
                                </div>
                                {form.errors.type && (
                                    <p className="mt-1.5 text-xs text-red-600 dark:text-red-400">{form.errors.type}</p>
                                )}
                            </div>

                            {fields.map(field => (
                                <FieldRenderer
                                    key={field.id}
                                    name={field.id}
                                    field={field}
                                    value={form.data[field.id as keyof typeof form.data]}
                                    error={form.errors[field.id as keyof typeof form.errors]}
                                    onChange={handleFieldChange}
                                    formData={form.data}
                                />
                            ))}
                        </div>
                    </form>

                    <div className="flex w-full min-w-0 flex-[1_1_20rem] flex-col gap-6 lg:sticky lg:top-6">
                        {/* Step 2 — account: the existing sign-in form for guests. */}
                        {!isSignedIn && !user ? (
                            <>
                                <section aria-labelledby="odc-account-heading" className={CARD_CLASS}>
                                    <StepHeading
                                        step={2}
                                        title={isSignedIn ? 'Your account' : 'Sign in to submit'}
                                        id="odc-account-heading"
                                    />
                                    <SignInForm onOtpClick={handleOtpClick} className="mt-4"/>
                                </section>
                            </>
                        ) : ''}
                        {/* Step 3 — submit. */}
                        <section aria-labelledby="odc-submit-heading" className={CARD_CLASS}>
                            <StepHeading step={3} title="Submit" id="odc-submit-heading"/>

                            <Button
                                type="submit"
                                form={FORM_ID}
                                color="firefly"
                                disabled={form.processing}
                                aria-disabled={!isSignedIn || form.processing}
                                aria-describedby="odc-submit-help"
                                className="mt-4 w-full"
                            >
                                {form.processing ? 'Submitting...' : 'Submit opportunity'}
                            </Button>
                            {hasErrors && (
                                <p role="alert" className="mt-2 text-xs text-red-600 dark:text-red-400">
                                    Please fix the highlighted fields in the form.
                                </p>
                            )}
                            <p id="odc-submit-help" className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {isSignedIn
                                    ? 'No partner registration needed.'
                                    : 'Sign in above to enable submission — your entries are kept on this device while you sign in. No partner registration needed.'}
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </FrontendLayout>
    );
}
