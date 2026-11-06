<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rewrites persisted polymorphic class names (App\Models\...) to the short aliases
 * declared in AppServiceProvider::morphMap(), so models can be moved between
 * namespaces without breaking existing rows.
 *
 * The alias map is deliberately inlined (a frozen snapshot of the classes that were
 * persisted at the time this migration was written) rather than read from the
 * provider, so the migration keeps rewriting the same rows on every environment.
 *
 * A down() is provided on purpose: this is a data rewrite, not a schema change.
 */
return new class extends Migration
{
    /**
     * Class names that existed in polymorphic columns when the morph map was introduced.
     *
     * @var array<string, string> FQCN => alias
     */
    private array $aliases = [
        'App\Models\User' => 'user',
        'App\Models\Request' => 'request',
        'App\Models\Request\Offer' => 'offer',
        'App\Models\Opportunity' => 'opportunity',
        'App\Models\Document' => 'document',
    ];

    /**
     * Table => polymorphic type column.
     *
     * @return array<string, string>
     */
    private function polymorphicColumns(): array
    {
        $permission = config('permission.table_names', []);

        return [
            'documents' => 'parent_type',
            'one_time_passwords' => 'authenticatable_type',
            $permission['model_has_roles'] ?? 'model_has_roles' => 'model_type',
            $permission['model_has_permissions'] ?? 'model_has_permissions' => 'model_type',
        ];
    }

    public function up(): void
    {
        $this->rewrite($this->aliases);
    }

    public function down(): void
    {
        $this->rewrite(array_flip($this->aliases));
    }

    /**
     * @param  array<string, string>  $map  from => to
     */
    private function rewrite(array $map): void
    {
        foreach ($this->polymorphicColumns() as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            foreach ($map as $from => $to) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
