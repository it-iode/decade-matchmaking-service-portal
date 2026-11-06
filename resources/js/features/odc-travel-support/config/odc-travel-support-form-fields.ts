import { UIField } from '@/types';
import { opportunityFormFields, ODC_TRAVEL_SUPPORT_TYPE } from '@features/opportunities/config/opportunity-form-fields';

/** Opportunity type value used for Ocean Decade Conference (ODC) travel support. */
export { ODC_TRAVEL_SUPPORT_TYPE };

/**
 * Fields from the standard opportunity form that do not apply to ODC travel support.
 * They are hidden on the ODC travel support form and rendered as "N/A" in the back office.
 *
 * TODO(#218): align this list with the field list in the Google Doc linked from the issue once it is in the repo.
 */
export const ODC_TRAVEL_SUPPORT_EXCLUDED_FIELDS: readonly string[] = [
    'type', // locked to ODC_TRAVEL_SUPPORT_TYPE, not editable
    'thematic_areas',
    'thematic_areas_other',
    'coverage_activity',
    'implementation_location',
    'target_languages',
    'target_languages_other',
];

const FIELD_OVERRIDES: Record<string, Partial<UIField>> = {
    title: { label: 'Opportunity title' },
    closing_date: { label: 'Application closing date' },
    summary: {
        label: 'Summary of the 2027 ODC travel support',
        placeholder: 'What the support covers, who is eligible and how to apply.',
    },
    target_audience: { type: 'checkbox-group' },
};

/**
 * The trimmed field set for the ODC travel support upload form, in display order.
 * Derived from the standard opportunity form so labels and validation stay in sync.
 */
export const odcTravelSupportFormFields: UIField[] = Object.entries(opportunityFormFields[0].fields)
    .filter(([key]) => !ODC_TRAVEL_SUPPORT_EXCLUDED_FIELDS.includes(key))
    .map(([key, field]) => ({ ...field, ...FIELD_OVERRIDES[key] }));
