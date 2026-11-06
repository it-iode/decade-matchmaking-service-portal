<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Contracts\ActionProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base resource that standardizes entity-field shaping and (optionally) merges
 * a rich `actions` array resolved from the entity's action provider.
 *
 * Subclasses return ONLY their entity fields from fields(); if they declare
 * an action provider, its EntityAction[] output is appended under `actions`.
 */
abstract class BaseResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Return ONLY the entity fields for this resource (no actions).
     *
     * @return array<string, mixed>
     */
    abstract protected function fields(Request $request): array;

    /**
     * The action provider for this entity, or null to emit no actions.
     *
     * @return class-string<ActionProviderInterface>|null
     */
    protected function actionProvider(): ?string
    {
        return null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->fields($request);

        $providerClass = $this->actionProvider();

        if ($providerClass !== null) {
            $data['actions'] = app($providerClass)->getActions(
                $this->resource,
                $request->user(),
                $this->resolveContext($request)
            );
        }

        return $data;
    }

    /**
     * Resolve the UI context passed to the action provider.
     *
     * Defaults to a coarse admin/user distinction based on the route; resources
     * that need a richer context vocabulary should override this.
     */
    protected function resolveContext(Request $request): string
    {
        return $request->routeIs('admin.*') ? 'admin' : 'user';
    }
}
