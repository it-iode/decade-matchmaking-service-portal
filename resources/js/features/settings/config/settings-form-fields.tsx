import {UIStep} from '@features/offers/config';


export const settingsFormFields: UIStep[] = [
    {
        label: 'General Settings',
        fields: {
            site_name: {
                id: 'site_name',
                type: 'text',
                required: false,
                label: 'Site Name',
                placeholder: 'Enter the site name',
            },
            site_description: {
                id: 'site_description',
                type: 'textarea',
                required: false,
                label: 'Site Description',
                placeholder: 'Enter a brief description of the site',
            },
            homepage_youtube_video: {
                id: 'homepage_youtube_video',
                type: 'textarea',
                required: false,
                label: 'Homepage YouTube Video',
                placeholder: 'Enter the YouTube video URL for the homepage',
            },
            show_odc_travel_support_section: {
                id: 'show_odc_travel_support_section',
                type: 'radio',
                required: false,
                label: '2027 ODC Travel Support Section',
                description: 'Show or hide the 2027 ODC Travel Support section on the landing and home pages.',
                options: [
                    { value: '1', label: 'Show' },
                    { value: '0', label: 'Hide' },
                ],
            }
        }
    },
    {
        label: 'Metrics',
        fields: {
            successful_matches_metric: {
                id: 'successful_matches_metric',
                type: 'number',
                required: false,
                label: 'Successful Matches Metric',
                placeholder: 'Enter the number of successful matches',
            },
            fully_closed_matches_metric: {
                id: 'fully_closed_matches_metric',
                type: 'number',
                required: false,
                label: 'Fully Closed Matches Metric',
                placeholder: 'Enter the number of fully closed matches',
            },
            request_in_implementation_metric: {
                id: 'request_in_implementation_metric',
                type: 'number',
                required: false,
                label: 'Request in Implementation Metric',
                placeholder: 'Enter the number of requests in implementation',
            },
            committed_funding_metric: {
                id: 'committed_funding_metric',
                type: 'number',
                required: false,
                label: 'Committed Funding Metric',
                placeholder: 'Enter the amount of committed funding',
            },
            open_partner_opportunities_metric: {
                id: 'open_partner_opportunities_metric',
                type: 'number',
                required: false,
                label: 'Open Partner Opportunities Metric',
                placeholder: 'Enter the number of open partner opportunities',
            },
        }
    },
    {
        label: 'Guides',
        fields: {
            portal_guide: {
                id: 'portal_guide',
                type: 'file',
                required: false,
                label: 'Portal Guide',
                description: 'Upload a PDF guide for the portal (PDF)',
                accept: 'application/pdf',
            },
            user_guide: {
                id: 'user_guide',
                type: 'file',
                required: false,
                label: 'User Guide',
                description: 'Upload a PDF User guide (PDF)',
                accept: 'application/pdf',
            },
            partner_guide: {
                id: 'partner_guide',
                type: 'file',
                required: true,
                label: 'Partner Guide',
                description: 'Upload a PDF User guide (PDF)',
                accept: 'application/pdf',
            }
        }
    },
    {
        label: 'Data Management',
        fields: {
            organizations_csv: {
                id: 'organizations_csv',
                type: 'file',
                required: false,
                label: 'Organizations CSV Import',
                description: 'Upload a CSV file containing organization data. The file should have 3 columns: organization name, description, and website link.',
                accept: '.csv,text/csv,application/csv',
            },
            ioc_platforms_csv: {
                id: 'ioc_platforms_csv',
                type: 'file',
                required: false,
                label: 'IOC Platforms CSV Import',
                description: 'Upload a CSV file containing IOC platform data. The file should have 4 columns: name, description, link, and contact.',
                accept: '.csv,text/csv,application/csv',
            }
        }
    },
    {
        label: 'Mandrill Settings',
        fields: {
            mandrill_api_key: {
                id: 'mandrill_api_key',
                type: 'text',
                required: false,
                label: 'Mandrill API Key',
                description: 'Enter your Mandrill API key for email services.',
            },
        }
    },
    {
        label: 'OAuth Settings',
        fields: {
            linkedin_client_id: {
                id: 'linkedin_client_id',
                type: 'text',
                required: false,
                label: 'LinkedIn Client ID',
                description: 'Client ID of the LinkedIn OAuth application used for social sign-in.',
                autoComplete: 'off',
            },
            linkedin_client_secret: {
                id: 'linkedin_client_secret',
                type: 'password',
                required: false,
                label: 'LinkedIn Client Secret',
                description: 'Client secret of the LinkedIn OAuth application.',
                autoComplete: 'new-password',
            },
            google_client_id: {
                id: 'google_client_id',
                type: 'text',
                required: false,
                label: 'Google Client ID',
                description: 'Client ID of the Google OAuth application used for social sign-in.',
                autoComplete: 'off',
            },
            google_client_secret: {
                id: 'google_client_secret',
                type: 'password',
                required: false,
                label: 'Google Client Secret',
                description: 'Client secret of the Google OAuth application.',
                autoComplete: 'new-password',
            },
        }
    }
]
