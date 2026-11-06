<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Mailchimp Transactional Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Mailchimp Transactional (formerly Mandrill) email
    | service integration with Laravel's mail system.
    |
    */

    'transactional' => [
        /*
        |--------------------------------------------------------------------------
        | API Key
        |--------------------------------------------------------------------------
        |
        | Your Mailchimp Transactional API key. This is required to authenticate
        | with the Mailchimp Transactional API.
        |
        */
        'api_key' => env('MAILCHIMP_TRANSACTIONAL_KEY','b2dafc3f08a744a65555679b2bf4ff26-us15'),

        /*
        |--------------------------------------------------------------------------
        | Default From Address
        |--------------------------------------------------------------------------
        |
        | The default from email address and name for emails sent through
        | Mailchimp Transactional. These can be overridden per message.
        |
        */
        'from' => [
            'email' => env('MAILCHIMP_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
            'name' => env('MAILCHIMP_FROM_NAME', env('MAIL_FROM_NAME')),
        ],

        /*
        |--------------------------------------------------------------------------
        | Tracking Options
        |--------------------------------------------------------------------------
        |
        | Configure tracking options for emails sent through Mailchimp.
        |
        */
        'tracking' => [
            'opens' => env('MAILCHIMP_TRACK_OPENS', true),
            'clicks' => env('MAILCHIMP_TRACK_CLICKS', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Email Options
        |--------------------------------------------------------------------------
        |
        | Additional options for email processing.
        |
        */
        'options' => [
            'auto_text' => env('MAILCHIMP_AUTO_TEXT', true),
            'preserve_recipients' => env('MAILCHIMP_PRESERVE_RECIPIENTS', false),
        ],
    ],
];
