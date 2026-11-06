<?php

namespace App\Domains\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = ['id'];

    /**
     * Setting path constants (used as the 'path' column in the settings table)
     */
    public const SITE_NAME = 'site_name';

    public const SITE_DESCRIPTION = 'site_description';

    public const HOMEPAGE_YOUTUBE_VIDEO = 'homepage_youtube_video';

    public const SHOW_ODC_TRAVEL_SUPPORT_SECTION = 'show_odc_travel_support_section';

    public const PORTAL_GUIDE = 'portal_guide';

    public const USER_GUIDE = 'user_guide';

    public const PARTNER_GUIDE = 'partner_guide';

    public const SUCCESSFUL_MATCHES_METRIC = 'successful_matches_metric';

    public const FULLY_CLOSED_MATCHES_METRIC = 'fully_closed_matches_metric';

    public const REQUEST_IN_IMPLEMENTATION_METRIC = 'request_in_implementation_metric';

    public const COMMITTED_FUNDING_METRIC = 'committed_funding_metric';

    public const OPEN_PARTNER_OPPORTUNITIES_METRIC = 'open_partner_opportunities_metric';

    public const ORGANIZATIONS_CSV = 'organizations_csv';

    public const IOC_PLATFORMS_CSV = 'ioc_platforms_csv';

    public const MANDRILL_API_KEY = 'mandrill_api_key';

    public const LINKEDIN_CLIENT_ID = 'linkedin_client_id';

    public const LINKEDIN_CLIENT_SECRET = 'linkedin_client_secret';

    public const GOOGLE_CLIENT_ID = 'google_client_id';

    public const GOOGLE_CLIENT_SECRET = 'google_client_secret';

    /**
     * Settings that are file uploads
     */
    public const FILE_UPLOAD_SETTINGS = [
        self::PORTAL_GUIDE,
        self::USER_GUIDE,
        self::PARTNER_GUIDE,
        self::ORGANIZATIONS_CSV,
        self::IOC_PLATFORMS_CSV,
    ];

    /**
     * All valid setting paths
     */
    public const VALID_PATHS = [
        self::SITE_NAME,
        self::SITE_DESCRIPTION,
        self::HOMEPAGE_YOUTUBE_VIDEO,
        self::SHOW_ODC_TRAVEL_SUPPORT_SECTION,
        self::PORTAL_GUIDE,
        self::USER_GUIDE,
        self::PARTNER_GUIDE,
        self::ORGANIZATIONS_CSV,
        self::IOC_PLATFORMS_CSV,
        self::SUCCESSFUL_MATCHES_METRIC,
        self::FULLY_CLOSED_MATCHES_METRIC,
        self::REQUEST_IN_IMPLEMENTATION_METRIC,
        self::COMMITTED_FUNDING_METRIC,
        self::OPEN_PARTNER_OPPORTUNITIES_METRIC,
        self::MANDRILL_API_KEY,
        self::LINKEDIN_CLIENT_ID,
        self::LINKEDIN_CLIENT_SECRET,
        self::GOOGLE_CLIENT_ID,
        self::GOOGLE_CLIENT_SECRET,
    ];

    public $timestamps = true;

    /**
     * Check if a setting path is a file upload
     */
    public static function isFileUpload(string $path): bool
    {
        return in_array($path, self::FILE_UPLOAD_SETTINGS);
    }

    /**
     * Get storage directory for file uploads based on setting path
     */
    public static function getStorageDirectory(string $path): string
    {
        return match ($path) {
            self::PORTAL_GUIDE, self::USER_GUIDE, self::PARTNER_GUIDE, self::ORGANIZATIONS_CSV, self::IOC_PLATFORMS_CSV => 'settings/guides',
            default => 'settings'
        };
    }
}
