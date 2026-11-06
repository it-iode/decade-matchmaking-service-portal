<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Detect if current route is an admin route
     */
    protected function isAdminRoute(): bool
    {
        return str_contains(request()->route()->getPrefix(),'admin');
    }

    protected function getViewPrefix(): string
    {
        return $this->isAdminRoute() ? 'admin/' : '';
    }

    protected function buildBanner(string $title, string $description): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'image' => '/assets/img/sidebar.png',
        ];
    }
}
