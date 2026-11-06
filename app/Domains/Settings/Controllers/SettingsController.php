<?php

namespace App\Domains\Settings\Controllers;

use App\Domains\Settings\Requests\SettingsPostRequest;
use App\Domains\Settings\Services\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,

    ) {}

    public function index()
    {
        $settings = $this->settingsService->getAllSettings();

        return Inertia::render('admin/Portal/Settings', [
            'title' => 'Portal Settings',
            'settings' => $settings, // Pass current settings to form
        ]);
    }

    public function update(SettingsPostRequest $request): RedirectResponse
    {
        try {
            // Get validated data from FormRequest
            $validatedData = $request->validated();

            // Additional validation against defined constants
            $validatedData = $this->settingsService->validateSettingsData($validatedData);

            // Handle file uploads for all file-type settings
            foreach ($validatedData as $path => $value) {
                if ($request->hasFile($path)) {
                    $filePath = $this->settingsService->handleFileUpload($path, $request->file($path));
                    $validatedData[$path] = $filePath;
                }
            }

            // Update settings
            $this->settingsService->updateSettings($validatedData);

            return to_route('admin.settings.index')
                ->with('success', 'Settings updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update settings: '.$e->getMessage())
                ->withInput();
        }
    }
}
