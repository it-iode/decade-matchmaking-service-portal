<?php

namespace App\Shared\Http;

trait HasPageActions
{
    /**
     * Build page actions array with permission filtering
     */
    protected function buildActions(array $actions): array
    {
        return collect($actions)
            ->values()
            ->toArray();
    }

    /**
     * Quick helper to create common action patterns
     */
    protected function createAction(
        string $label,
        string $route,
        string $variant = 'secondary',
        ?string $icon = null,
        string $method = 'GET',
        ?array $data = []
    ): array {
        return [
            'label' => $label,
            'href' => $route,
            'variant' => $variant,
            'icon' => $icon,
            'method' => $method,
            'data' => $data,
        ];
    }

    /**
     * Create a primary action (common for "Create" buttons)
     */
    protected function createPrimaryAction(
        string $label,
        string $route,
        ?string $icon = '',
        string $method = 'GET'
    ): array {
        return $this->createAction($label, $route, 'primary', $icon, $method);
    }

    /**
     * Create a secondary action (common for "View" or "Export" buttons)
     */
    protected function createSecondaryAction(
        string $label,
        string $route,
        ?string $icon = null,
        string $method = 'GET'
    ): array {
        return $this->createAction($label, $route, 'secondary', $icon, $method);
    }

    /**
     * Create a danger action (common for "Delete" buttons)
     */
    protected function createDangerAction(
        string $label,
        string $route,
        ?string $icon = 'TrashIcon',
        string $method = 'GET'
    ): array {
        return $this->createAction($label, $route, 'danger', $icon, $method);
    }

    /**
     * Create a POST action (common for form submissions)
     */
    protected function createPostAction(
        string $label,
        string $route,
        ?string $icon = null,
        string $variant = 'primary'
    ): array {
        return $this->createAction($label, $route, $variant, $icon, 'POST');
    }

    /**
     * Create a DELETE action (common for resource deletion)
     */
    protected function createDeleteAction(string $label, string $route, ?string $icon = 'TrashIcon'): array
    {
        return $this->createAction($label, $route, 'danger', $icon, 'DELETE');
    }

    /**
     * Create a PUT action (common for updates)
     */
    protected function createPutAction(
        string $label,
        string $route,
        ?string $icon = null,
        string $variant = 'secondary'
    ): array {
        return $this->createAction($label, $route, $variant, $icon, 'PUT');
    }

    /**
     * Create a PATCH action (common for partial updates)
     */
    protected function createPatchAction(
        string $label,
        string $route,
        ?string $icon = null,
        string $variant = 'secondary'
    ): array {
        return $this->createAction($label, $route, $variant, $icon, 'PATCH');
    }

    /**
     * Create a PATCH action (common for partial updates)
     */
    protected function createLink(
        string $label,
        string $route,
        ?string $icon = null,
        string $variant = 'secondary'
    ): array {
        return $this->createAction($label, $route, $variant, $icon, 'LINK');
    }
}
