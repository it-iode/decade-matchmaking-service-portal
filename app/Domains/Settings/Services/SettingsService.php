<?php

declare(strict_types=1);

namespace App\Domains\Settings\Services;

use App\Domains\ReferenceData\Services\Import\IOCPlatformImportService;
use App\Domains\ReferenceData\Services\Import\OrganizationImportService;
use App\Domains\Settings\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    public function __construct(
        private readonly OrganizationImportService $organizationImportService,
        private readonly IOCPlatformImportService $iocPlatformImportService
    ) {}

    /**
     * Update or create settings
     *
     * @throws \Exception
     */
    public function updateSettings(array $settingsData): array
    {
        $updatedSettings = [];
        $csvImportResult = null;

        foreach ($settingsData as $path => $value) {
            // Skip null values for file uploads that weren't changed
            if ($value === null && Setting::isFileUpload($path)) {
                continue;
            }
            $setting = Setting::where('path', $path)->first();

            DB::transaction(function () use (&$setting, $path, $value) {
                if ($setting) {
                    $setting->update(['value' => $value]);
                } else {
                    $setting = Setting::create([
                        'path' => $path,
                        'value' => $value,
                    ]);
                }
            });

            $updatedSettings[$path] = $setting;
            // Handle CSV import if organizations_csv is being updated
            if ($path === Setting::ORGANIZATIONS_CSV && $value) {
                try {
                    $csvImportResult = $this->organizationImportService->importFromCsv($value);
                } catch (\Exception $e) {
                    // Re-throw the exception to rollback the transaction
                    throw new \Exception('Organizations CSV import failed: '.$e->getMessage(), 0, $e);
                }
            }

            // Handle CSV import if ioc_platforms_csv is being updated
            if ($path === Setting::IOC_PLATFORMS_CSV && $value) {
                try {
                    $csvImportResult = $this->iocPlatformImportService->importFromCsv($value);
                } catch (\Exception $e) {
                    // Re-throw the exception to rollback the transaction
                    throw new \Exception('IOC Platforms CSV import failed: '.$e->getMessage(), 0, $e);
                }
            }
        }

        return [
            'settings' => $updatedSettings,
            'csv_import_result' => $csvImportResult,
        ];
    }

    /**
     * Get all settings as key-value pairs
     */
    public function getAllSettings(): array
    {
        $settings = Setting::all();
        $result = [];

        foreach ($settings as $setting) {
            $path = $setting->path;
            $value = $setting->value;

            // If this is a file upload setting, return the public URL
            if (Setting::isFileUpload($path) && $value) {
                $result[$path] = asset('storage/'.$value);
            } else {
                $result[$path] = $value;
            }
        }

        return $result;
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(string $path, $default = null)
    {
        $setting = Setting::where('path', $path)->first();

        if (! $setting) {
            return $default;
        }

        // If this is a file upload setting, return the public URL
        if (Setting::isFileUpload($path) && $setting->value) {
            return Storage::url($setting->value);
        }

        return $setting->value;
    }

    /**
     * Validate settings data against defined constants
     */
    public function validateSettingsData(array $data): array
    {
        $validatedData = [];

        foreach ($data as $path => $value) {
            if (in_array($path, Setting::VALID_PATHS)) {
                $validatedData[$path] = $value;
            }
        }

        return $validatedData;
    }

    /**
     * Handle file upload for settings
     */
    public function handleFileUpload(string $path, UploadedFile $file): string
    {
        // Delete old file if it exists
        $this->deleteOldFile($path);

        // Store the new file
        $storageDirectory = Setting::getStorageDirectory($path);
        $fileName = time().'_'.$file->getClientOriginalName();

        return $file->storeAs($storageDirectory, $fileName, 'public');
    }

    /**
     * Delete old file when updating file settings
     */
    private function deleteOldFile(string $path): void
    {
        $existingSetting = Setting::where('path', $path)->first();

        if ($existingSetting && $existingSetting->value) {
            $oldFilePath = $existingSetting->value;

            if (Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }
        }
    }
}
