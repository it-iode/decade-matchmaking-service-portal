<?php

namespace App\Domains\Opportunity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseOpportunitiesController extends Controller
{
    /**
     * Get the context based on route name
     */
    protected function getRouteContext(): string
    {
        $routeName = request()->route()->getName() ?? '';

        if (str_starts_with($routeName, 'admin.')) {
            return 'admin';
        }

        if (str_contains($routeName, 'me.')) {
            return 'user_own';
        }

        return 'public';
    }

    /**
     * Build search filters from request based on field configurations.
     *
     * @param  Request  $request  The HTTP request containing search parameters
     * @param  array  $fields  Array of field configurations in format:
     *                         [['name' => 'field_name', 'label' => 'Field Label', 'type' => 'text'], ...]
     * @return array Associative array of field_name => value pairs for non-empty values
     */
    protected function buildSearchFilters(Request $request, array $fields): array
    {
        $filters = [];

        foreach ($fields as $field) {
            // Validate field configuration structure
            if (! is_array($field) || ! isset($field['name'])) {
                continue; // Skip malformed field configurations
            }

            $fieldName = $field['name'];
            $value = $request->get($fieldName);

            // Only include non-empty values in filters
            if ($value !== null && $value !== '') {
                $filters[$fieldName] = $value;
            }
        }

        return $filters;
    }

    protected function buildSortFilters(Request $request, string $defaultField = 'created_at', string $defaultOrder = 'desc', int $perPage = 10): array
    {
        return [
            'field' => $request->get('sort', $defaultField),
            'order' => $request->get('order', $defaultOrder),
            'per_page' => (int) $request->get('per_page', $perPage),
        ];
    }
}
