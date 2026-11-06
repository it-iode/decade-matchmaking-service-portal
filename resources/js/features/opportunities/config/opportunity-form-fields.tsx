import {UIStep} from '@/types';

/** Opportunity type value for Ocean Decade Conference (ODC) travel support. Defined here to avoid a circular import with the ODC feature config. */
export const ODC_TRAVEL_SUPPORT_TYPE = 'odc-travel-support';

/** Fields that are not asked for ODC travel support opportunities. */
const isNotODCTravelSupport = (data: { type?: string }) => data.type !== ODC_TRAVEL_SUPPORT_TYPE;

export const opportunityFormFields: UIStep[] = [
    {
        label: 'Basic Info',
        fields: {
            co_organizers: {
                id: 'co_organizers',
                type: 'keywords',
                required: true,
                label: 'Institution or programme offering this opportunity\n',
                description: 'If there are multiple co-organizers, you may list up to 10. If there are more than 10, please use the summary box.',
                maxKeywords: 10
            },
            title: {
                id: 'title',
                type: 'text',
                required: true,
                label: 'Opportunity Title',
                placeholder: 'Enter the title',
            },
            type: {
                id: 'type',
                type: 'select',
                required: true,
                label: 'Type of Opportunity',
                // options should be provided by the parent component from backend
            },
            thematic_areas: {
                id: 'thematic_areas',
                type: 'multiselect',
                required: true,
                label: 'Thematic Areas',
                show: isNotODCTravelSupport,
                description: 'If your area is not listed, select “Other” and use the Three Keywords question to describe it, or to further specify the selected areas.\n' +
                    'Multiple choice (select all that apply):',
                // options should be provided by the parent component from backend
            },
            thematic_areas_other: {
                id: 'thematic_areas_other',
                type: 'text',
                required: false,
                label: 'Please specify the areas',
                show: data => isNotODCTravelSupport(data) && data.thematic_areas?.includes('other')
                // options should be provided by the parent component from backend
            },
            closing_date: {
                id: 'closing_date',
                type: 'date',
                required: true,
                label: 'Application/Registration Closing Date (MM/DD/YY)',
            },
            coverage_activity: {
                id: 'coverage_activity',
                type: 'select',
                required: true,
                label: 'Coverage of CD Activity',
                show: isNotODCTravelSupport,
            },
            implementation_location: {
                id: 'implementation_location',
                type: 'multiselect',
                required: true,
                label: 'Implementation Location',
                show: isNotODCTravelSupport,
                // options will be set dynamically based on coverage_activity
            },
            target_audience: {
                id: 'target_audience',
                type: 'multiselect',
                required: true,
                label: 'Target Audience',
                // options will be provided by the parent component from FormOptions
            },
            target_audience_other: {
                id: 'target_audience_other',
                type: 'text',
                required: false,
                label: 'Please specify the target audience',
                show: data => data.target_audience?.includes('other')
            },
            target_languages: {
                id: 'target_languages',
                type: 'multiselect',
                required: true,
                label: 'Language of participation',
                show: isNotODCTravelSupport,
                // options will be provided by the parent component from FormOptions
            },
            target_languages_other: {
                id: 'target_languages_other',
                type: 'text',
                required: false,
                label: 'Please specify the Language of participation',
                show: data => isNotODCTravelSupport(data) && data.target_languages?.includes('other')
            },
            summary: {
                id: 'summary',
                type: 'textarea',
                required: true,
                label: 'Summary of the Opportunity',
                maxLength: 500,
                placeholder: 'Summary of the Opportunity (please include any prerequisites so that interested applicants are aware).'
            },
            url: {
                id: 'url',
                type: 'text',
                required: false,
                label: 'Application site URL',
            },
            key_words: {
                id: 'key_words',
                type: 'keywords',
                required: false,
                label: 'Three key words',
                description: 'Add comma (,) to separate key words or press enter',
                maxKeywords: 3
            },
        },
    }
];
