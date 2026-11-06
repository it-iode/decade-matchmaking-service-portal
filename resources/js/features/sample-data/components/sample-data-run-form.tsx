import React, { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@ui/primitives/button';
import { Description, ErrorMessage, Field, Label } from '@ui/primitives/fieldset';
import { Input } from '@ui/primitives/input';
import { Subheading } from '@ui/primitives/heading';
import { Text, Strong, Code } from '@ui/primitives/text';
import { SAMPLE_DATA_CONFIRMATION_PHRASE } from '../types';

interface SampleDataRunFormProps {
    environment: string;
    protectedEmails: string[];
}

/**
 * Destructive action form: the administrator must type the confirmation
 * phrase before the purge + generation request is sent.
 */
export function SampleDataRunForm({ environment, protectedEmails }: Readonly<SampleDataRunFormProps>) {
    const { data, setData, post, processing, errors, reset } = useForm({ confirmation: '' });

    const isConfirmed = data.confirmation === SAMPLE_DATA_CONFIRMATION_PHRASE;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!isConfirmed || processing) {
            return;
        }
        post(route('admin.sample-data.run'), {
            // Keep the scroll position on a validation error; scroll to the top
            // on success so the summary panel (and any flash message) is visible.
            preserveScroll: (page) => Object.keys(page.props.errors ?? {}).length > 0,
            // Always clear the phrase: a destructive action should be re-confirmed.
            onFinish: () => reset('confirmation'),
        });
    };

    return (
        <section
            aria-labelledby="sample-data-run-heading"
            className="space-y-4 rounded-lg border border-red-200 bg-red-50/60 p-4 sm:p-6 dark:border-red-900 dark:bg-red-950/30"
        >
            <Subheading id="sample-data-run-heading" level={2}>
                Erase and generate sample data
            </Subheading>
            <Text>
                Environment: <Strong>{environment}</Strong>. Protected accounts:{' '}
                {protectedEmails.map((email, index) => (
                    <React.Fragment key={email}>
                        {index > 0 && ', '}
                        <Code>{email}</Code>
                    </React.Fragment>
                ))}
                .
            </Text>
            <Text>
                This cannot be undone. Queued jobs that reference erased records will fail when processed; clear the
                queue first if it is not empty.
            </Text>

            <form onSubmit={handleSubmit} className="space-y-4">
                <Field>
                    <Label htmlFor="sample-data-confirmation">
                        Type <Code>{SAMPLE_DATA_CONFIRMATION_PHRASE}</Code> to confirm
                    </Label>
                    <Input
                        id="sample-data-confirmation"
                        name="confirmation"
                        type="text"
                        autoComplete="off"
                        spellCheck={false}
                        value={data.confirmation}
                        onChange={(event) => setData('confirmation', event.target.value)}
                        invalid={Boolean(errors.confirmation)}
                        disabled={processing}
                        className="max-w-xs"
                    />
                    {errors.confirmation ? (
                        <ErrorMessage>{errors.confirmation}</ErrorMessage>
                    ) : (
                        <Description>The phrase is case-sensitive.</Description>
                    )}
                </Field>

                <div className="flex items-center gap-3">
                    <Button type="submit" color="red" disabled={!isConfirmed || processing}>
                        {processing ? 'Generating…' : 'Erase and generate sample data'}
                    </Button>
                    <Text role="status" aria-live="polite">
                        {processing ? 'This usually takes a few seconds. Do not close the page.' : ''}
                    </Text>
                </div>
            </form>
        </section>
    );
}
