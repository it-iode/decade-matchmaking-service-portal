import React from 'react';
import {Head} from '@inertiajs/react';
import { settingsFormFields } from '@features/settings/config';
import { FieldRenderer } from '@ui/organisms/forms';
import {Settings} from "@/types";
import {SidebarLayout} from '@layouts/index'
import {PageHeader} from "@ui/molecules/page-header";
import {Button} from "@ui/primitives/button";
import {Text} from '@ui/primitives/text';
import {useSettingsForm} from "@/hooks/useSettingsForm";

interface SettingsFormProps {
    settings: Settings;
}

export default function SettingsForm({settings}: Readonly<SettingsFormProps>) {
    const {
        form,
        changedFields,
        handleSubmit,
        handleFieldChange,
    } = useSettingsForm({settings, isEditing: true});

    const {data, processing, errors} = form;

    return (
        <SidebarLayout>
            <Head title="Portal Settings"/>
            <PageHeader title="Portal Settings" />
            <div>
                <form onSubmit={handleSubmit}>
                    {settingsFormFields.map((step) => (
                        <div key={step.label}>
                            <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100 mt-8 mb-2">{step.label}</h2>
                            {Object.entries(step.fields).map(([key, field]) => {
                                // Enhance field with existing file information for file inputs
                                const enhancedField = { ...field } as typeof field;

                                return (
                                    <FieldRenderer
                                        key={key}
                                        name={key}
                                        field={enhancedField}
                                        value={(data as any)[key]}
                                        error={(errors as any)[key]}
                                        onChange={handleFieldChange}
                                        formData={data}
                                    />
                                );
                            })}
                        </div>
                    ))}
                    <div className="flex justify-between items-center mt-6">
                        {changedFields.size > 0 && (
                            <Text className="text-sm text-amber-600 dark:text-amber-400">
                                {changedFields.size} field{changedFields.size > 1 ? 's' : ''} modified
                            </Text>
                        )}
                        <div className="flex-1"></div>
                        <Button
                            type="submit"
                            disabled={processing || changedFields.size === 0}
                            className="bg-firefly-600 hover:bg-firefly-700 text-white disabled:opacity-50"
                        >
                            {(() => {
                                if (processing) return 'Saving...';
                                if (changedFields.size === 0) return 'No Changes';
                                return `Save ${changedFields.size} Change${changedFields.size > 1 ? 's' : ''}`;
                            })()}
                        </Button>
                    </div>
                </form>
            </div>
        </SidebarLayout>
    )
}
