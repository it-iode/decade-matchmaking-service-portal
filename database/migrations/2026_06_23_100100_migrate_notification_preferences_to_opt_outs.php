<?php

use App\Domains\Opportunity\Enums\Type;
use App\Domains\Request\Enums\SubTheme;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the legacy opt-in `user_notification_preferences` rows (an
     * inclusion list of the types a user wanted) into the new opt-out model:
     *
     *  - A user who had at least one enabled row keeps their narrowing: we
     *    store every taxonomy value they did NOT enable as an opt-out, so the
     *    set they receive is unchanged.
     *  - A user whose rows were all disabled had used "unsubscribe from all";
     *    we set their master switch off.
     *  - A user with no rows (never opted in) is NOT touched by this migration
     *    and defaults to master on, no opt-outs — meaning they now receive
     *    all opportunity types. This is the intended opt-out-by-default
     *    behaviour (zero-rows cohort receives everything).
     *
     * Re-run safety: only rows still in the default state (null opt-outs,
     * master switch on) are written, so re-running never clobbers live toggles.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_notification_preferences')) {
            return;
        }

        $opportunityValues = array_map(fn ($case) => $case->value, Type::cases());
        $requestValues = array_map(fn ($case) => $case->value, SubTheme::cases());

        $userIds = DB::table('user_notification_preferences')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            // C1: Only backfill users still in default state to make the
            // migration safe to re-run without clobbering live toggles.
            $alreadyMigrated = DB::table('users')
                ->where('id', $userId)
                ->where(function ($q): void {
                    $q->whereNotNull('notification_opt_outs')
                        ->orWhere('email_notifications_enabled', false);
                })
                ->exists();

            if ($alreadyMigrated) {
                continue;
            }

            // C2: Filter by attribute_type to avoid stray rows polluting the diff.
            $prefs = DB::table('user_notification_preferences')
                ->where('user_id', $userId)
                ->whereIn('attribute_type', ['subtheme', 'type'])
                ->get();

            $anyEnabled = $prefs->contains(fn ($p) => (bool) $p->email_notification_enabled);

            $optOuts = [];

            $oppPrefs = $prefs->where('attribute_type', 'type');
            if ($oppPrefs->isNotEmpty()) {
                $enabled = $oppPrefs
                    ->filter(fn ($p) => (bool) $p->email_notification_enabled)
                    ->pluck('attribute_value')
                    ->all();
                $optOuts['opportunity'] = array_values(array_diff($opportunityValues, $enabled));
            }

            $reqPrefs = $prefs->where('attribute_type', 'subtheme');
            if ($reqPrefs->isNotEmpty()) {
                $enabled = $reqPrefs
                    ->filter(fn ($p) => (bool) $p->email_notification_enabled)
                    ->pluck('attribute_value')
                    ->all();
                $optOuts['request'] = array_values(array_diff($requestValues, $enabled));
            }

            DB::table('users')->where('id', $userId)->update([
                'email_notifications_enabled' => $anyEnabled,
                'notification_opt_outs' => empty($optOuts) ? null : json_encode($optOuts),
            ]);
        }
    }

    /**
     * Reset the opt-out state back to defaults.
     */
    public function down(): void
    {
        DB::table('users')->update([
            'email_notifications_enabled' => true,
            'notification_opt_outs' => null,
        ]);
    }
};
