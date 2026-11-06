<?php

return [
    /*
     * One-time passwords should be consumed within this number of minutes.
     * Set to 10 minutes to match the previous custom implementation.
     */
    'default_expires_in_minutes' => 10,

    /*
     * When this setting is active, we'll delete all previous one-time passwords for
     * a user when generating a new one
     */
    'only_one_active_one_time_password_per_user' => true,

    /*
     * When this option is active, we'll try to ensure that the one-time password can only
     * be consumed on the platform where it was requested on.
     * Disabled to match previous implementation behavior.
     */
    'enforce_same_origin' => false,

    /*
     * This class is responsible to enforce that the one-time password can only be consumed on
     * the platform it was requested on.
     *
     * Since we disable origin enforcement, we use the DoNotEnforceOrigin class.
     */
    'origin_enforcer' => Spatie\OneTimePasswords\Support\OriginInspector\DoNotEnforceOrigin::class,

    /*
     * This class generates a random password
     */
    'password_generator' => Spatie\OneTimePasswords\Support\PasswordGenerators\NumericOneTimePasswordGenerator::class,

    /*
     * By default, the password generator will create a password with
     * this number of digits. 6 digits for better security.
     */
    'password_length' => 6,

    /*
     * The Livewire component will redirect successfully authenticated users
     * to this URL.
     */
    'redirect_successful_authentication_to' => '/home',

    /*
     * These values are used to rate limit the number of attempts
     * that may be made to consume a one-time password.
     */
    'rate_limit_attempts' => [
        'max_attempts_per_user' => 5,
        'time_window_in_seconds' => 60,
    ],

    /*
     * The model uses to store one-time passwords
     */
    'model' => Spatie\OneTimePasswords\Models\OneTimePassword::class,

    /*
     * The notification used to send a one-time password to a user.
     * Using our custom notification that integrates with Mandrill.
     */
    'notification' => App\Domains\Auth\Notifications\OneTimePasswordNotification::class,

    /*
     * These class are responsible for performing core tasks regarding one-time passwords.
     * You can customize them by creating a class that extends the default, and
     * by specifying your custom class name here.
     */
    'actions' => [
        'create_one_time_password' => Spatie\OneTimePasswords\Actions\CreateOneTimePasswordAction::class,
        'consume_one_time_password' => Spatie\OneTimePasswords\Actions\ConsumeOneTimePasswordAction::class,
    ],
];
