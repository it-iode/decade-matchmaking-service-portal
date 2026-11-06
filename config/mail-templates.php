<?php

declare(strict_types=1);

return [
    'provider' => 'mandrill',

    /*
    |--------------------------------------------------------------------------
    | Email Template Mappings
    |--------------------------------------------------------------------------
    |
    | Maps application events to Mandrill template names with required variables.
    | Each template configuration includes:
    | - template_name: Mandrill template slug
    | - subject: Default subject line (can be overridden in Mandrill)
    | - variables: Required and optional variables with validation rules
    | - tags: Tags for tracking and filtering in Mandrill
    |
    */
    'templates' => [
        // Authentication Events
        'auth.otp' => [
            'template_name' => 'cdf-otp-information',
            'subject' => 'Your Ocean Decade Portal Login Code',
            'variables' => [
                'user_name' => 'required|string',
                'otp_code' => 'required|string',
                'expires_in_minutes' => 'required|integer',
            ],
            'tags' => ['auth', 'otp', 'login'],
        ],

        // User Events
        'user.registered' => [
            'template_name' => 'ocd-welcome',
            'subject' => 'Welcome to Ocean Decade Portal',
            'variables' => [
                'user_name' => 'required|string',
                'verification_url' => 'required|url',
                'portal_url' => 'required|url',
            ],
            'tags' => ['user', 'registration', 'welcome'],
        ],
        'user.roles_changed' => [
            'template_name' => 'cdf-user-role-changed',
            'subject' => 'Update to your Ocean Connector role and access',
            'variables' => [
                'name' => 'required|string',
                'portal_url' => 'required|url',
            ],
            'tags' => ['user', 'registration', 'roles update'],
        ],

        'user.invitation' => [
            'template_name' => 'cdf-invitation-link',
            'subject' => 'You\'ve Been Invited to Ocean Decade Portal',
            'variables' => [
                'Link' => 'required|url'
            ],
            'tags' => ['user', 'invitation'],
        ],

        'user.email_verified' => [
            'template_name' => 'ocd-email-verified',
            'subject' => 'Email Verified Successfully',
            'variables' => [
                'user_name' => 'required|string',
                'portal_url' => 'required|url',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['user', 'verification'],
        ],

        'user.password_reset' => [
            'template_name' => 'ocd-password-reset',
            'subject' => 'Reset Your Password',
            'variables' => [
                'user_name' => 'required|string',
                'reset_url' => 'required|url',
                'expires_at' => 'required|string',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['user', 'security', 'password'],
        ],

        // Request Events
        'request.submitted' => [
            'template_name' => 'ocd-request-submitted',
            'subject' => 'Your Ocean Decade Request Has Been Submitted',
            'variables' => [
                'user_name' => 'required|string',
                'request_title' => 'required|string',
                'request_id' => 'required|integer',
                'submission_date' => 'required|date',
                'request_url' => 'required|url',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['request', 'submission'],
        ],

        'request.approved' => [
            'template_name' => 'ocd-request-approved',
            'subject' => 'Your Request Has Been Approved',
            'variables' => [
                'user_name' => 'required|string',
                'request_title' => 'required|string',
                'request_url' => 'required|url',
                'approved_by' => 'optional|string',
                'approval_message' => 'optional|string',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['request', 'approval', 'status'],
        ],

        'request.matched' => [
            'template_name' => 'ocd-request-matched',
            'subject' => 'We Found a Match for Your Request',
            'variables' => [
                'user_name' => 'required|string',
                'request_title' => 'required|string',
                'partner_name' => 'required|string',
                'partner_organization' => 'required|string',
                'match_url' => 'required|url',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['request', 'match', 'partner'],
        ],
        'request.status.changed.user' => [
            'template_name' => 'cdf-status-change-notification-user',
            'subject' => 'Status change notification-User',
            'variables' => [
                'Request_Title' => 'required|string',
                'Request_Status' => 'required|string',
                'Link_to_Request' => 'required|string',
                'UNSUB'=>'required|string',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['request', 'user', 'status'],
        ],
        'request.status.changed.matched_partner' => [
            'template_name' => 'cdf-status-change-notification-partner',
            'subject' => 'Status change notification-User',
            'variables' => [
                'Request_Title' => 'required|string',
                'Request_Status' => 'required|string',
                'Link_to_Matched_Request' => 'required|string',
                'UNSUB'=>'required|string',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['request', 'user', 'status'],
        ],

        // Offer Events
        'offer.received' => [
            'template_name' => 'ocd-offer-received',
            'subject' => 'New Offer for Your Request',
            'variables' => [
                'recipient_name' => 'required|string',
                'partner_organization' => 'required|string',
                'request_title' => 'required|string',
                'offer_summary' => 'required|string',
                'offer_url' => 'required|url',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['offer', 'notification'],
        ],
        'opportunity.updated' => [
            'template_name' => 'cdf-update-on-your-opportunity',
            'subject' => 'Opportunity Update',
            'variables' => [
                'Opportunity_Title' => 'required|string',
                'Opportunity_Link' => 'required|string',
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE'=>'required|string',
            ],
            'tags' => ['opportunity', 'notification'],
        ],
        // Weekly Opportunity Newsletter
        'opportunity.newsletter.weekly' => [
            'template_name' => 'cdf-opportunitynewsletter',
            'subject' => 'New Ocean Decade Opportunities This Week',
            'variables' => [
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE' => 'string',
                'user_name' => 'string',
                'opportunity_count' => 'integer',
                'view_all_url' => 'string',
            ],
            'tags' => ['opportunity', 'newsletter', 'weekly'],
        ],

        // DEPRECATED: Request newsletter moved to event-driven notifications
        'request.newsletter.weekly' => [
            'template_name' => 'cdf-request-newsletter-weekly',
            'subject' => 'New Ocean Decade Requests This Week',
            'variables' => [
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE' => 'string',
                'user_name' => 'string',
                'request_count' => 'integer',
            ],
            'tags' => ['request', 'newsletter', 'weekly'],
            'deprecated' => true,
            'deprecated_note' => 'Request notifications are now event-driven (instant) rather than weekly batched',
        ],

        // Instant Request Notification (Event-Driven)
        'request.notification.instant' => [
            'template_name' => 'ocd-request-instant-notification',
            'subject' => 'New Capacity Development Request Matches Your Interests',
            'variables' => [
                'user_name' => 'required|string',
                'request_title' => 'required|string',
                'request_description' => 'required|string',
                'request_url' => 'required|url',
                'Request_Challenges' => 'required|string',
                'location' => 'optional|string',
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE' => 'required|string',
            ],
            'tags' => ['request', 'instant', 'notification'],
        ],

        // Instant Request Notification (Event-Driven)
        'request.express-interest.partner' => [
            'template_name' => 'cdf-expression-of-interest-partner',
            'subject' => 'New Capacity Development Request Matches Your Interests',
            'variables' => [
                'Request_Title' => 'required|string',
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE' => 'required|string',
            ],
            'tags' => ['request', 'express-interest', 'partner'],
        ],

        // Request Created Confirmation
        'request.created' => [
            'template_name' => 'cdf-request-submission',
            'subject' => 'Your Capacity Development Request Has Been Submitted',
            'variables' => [
                'user_name' => 'required|string',
                'Request_Title' => 'required|string',
                'Request_Link' => 'required|url',
                'UNSUB' => 'required|string',
            ],
            'tags' => ['request', 'confirmation'],
        ],

        // Offer Events
        'offer.created' => [
            'template_name' => 'ocd-offer-created',
            'subject' => 'New Offer Submitted for Your Request',
            'variables' => [
                'user_name' => 'required|string',
                'partner_name' => 'required|string',
                'partner_organization' => 'required|string',
                'request_title' => 'required|string',
                'offer_url' => 'required|url',
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE' => 'required|string',
            ],
            'tags' => ['offer', 'notification'],
        ],

        'offer.rejected' => [
            'template_name' => 'ocd-offer-rejected',
            'subject' => 'Update on Your Offer',
            'variables' => [
                'partner_name' => 'required|string',
                'request_title' => 'required|string',
                'rejection_reason' => 'optional|string',
                'UNSUB' => 'required|string',
                'UPDATE_PROFILE' => 'required|string',
            ],
            'tags' => ['offer', 'rejection'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Template Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes added to template names based on environment.
    | This allows testing templates without affecting production.
    |
    */
    'environment_prefix' => [
        'production' => '',
        'staging' => '',
        'development' => '',
        'local' => '',
        'testing' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mandrill Settings
    |--------------------------------------------------------------------------
    |
    | The API key is configured from the database via EmailServiceProvider.
    | It does not use ENV fallback - if no API key is found in database,
    | an exception will be thrown.
    |
    */
    'mandrill' => [
        'from_address' => env('MANDRILL_FROM_ADDRESS', 'noreply@oceandecade.org'),
        'from_name' => env('MANDRILL_FROM_NAME', 'Ocean Decade Portal'),
        'reply_to' => env('MANDRILL_REPLY_TO', 'support@oceandecade.org'),
        'webhook_key' => env('MANDRILL_WEBHOOK_KEY'), // For webhook signature verification
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Queue Settings
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('EMAIL_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('EMAIL_QUEUE_NAME', 'default'),
        'retry_after' => 90, // seconds
        'max_attempts' => 3,
        'backoff' => [60, 300, 900], // 1min, 5min, 15min
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'email', // Will use default channel if not exists
        'log_level' => env('EMAIL_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Settings
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => env('EMAIL_RATE_LIMIT_ENABLED', true),
        'global_per_minute' => env('EMAIL_RATE_LIMIT_GLOBAL', 100),
        'per_user_per_minute' => env('EMAIL_RATE_LIMIT_PER_USER', 5),
        'per_event_per_minute' => env('EMAIL_RATE_LIMIT_PER_EVENT', 20),
        'per_user_per_event_per_hour' => env('EMAIL_RATE_LIMIT_USER_EVENT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('EMAIL_TEMPLATE_CACHE_ENABLED', true),
        'ttl' => env('EMAIL_TEMPLATE_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'email_template:',
        'store' => env('EMAIL_TEMPLATE_CACHE_STORE', null), // Use default cache store
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Events
    |--------------------------------------------------------------------------
    |
    | Events that should trigger admin notifications when they fail
    |
    */
    'critical_events' => [
        'user.registered',
        'user.email_verified',
        'user.password_reset',
        'request.approved',
        'request.rejected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    |
    | Email addresses to notify when critical emails fail
    |
    */
    'admin_notifications' => explode(',', env('EMAIL_ADMIN_NOTIFICATIONS', '')),

    /*
    |--------------------------------------------------------------------------
    | Failure Threshold
    |--------------------------------------------------------------------------
    |
    | Number of failures before triggering critical alert
    |
    */
    'failure_threshold' => env('EMAIL_FAILURE_THRESHOLD', 10),
];
