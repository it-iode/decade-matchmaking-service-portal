<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Listeners;

use App\Domains\Opportunity\Events\OpportunityClicked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordOpportunityClick implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    private const BOT_UA_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|mediapartners|headlesschrome|puppeteer|playwright|wget|curl|python-requests|go-http-client/i';

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(OpportunityClicked $event): void
    {
        if ($this->isBot($event->userAgent)) {
            return;
        }

        $pepper = (string) config('services.opportunity_click.ip_pepper', '');
        $ipHash = hash('sha256', $event->ip.$pepper);

        DB::table('opportunity_clicks')->insert([
            'opportunity_id' => $event->opportunity->id,
            'user_id' => $event->user?->id,
            'ip_hash' => $ipHash,
            'user_agent' => Str::limit($event->userAgent, 509, ''),
            'referer' => $event->referer !== null ? Str::limit($event->referer, 509, '') : null,
            'created_at' => now(),
        ]);
    }

    private function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        return preg_match(self::BOT_UA_PATTERN, $userAgent) === 1;
    }
}
