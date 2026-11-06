<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ListEmailTemplatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:list-templates
                          {--event= : Filter by specific event name}
                          {--format=table : Output format (table, json, csv)}
                          {--show-variables : Include variable details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all configured email templates with their event mappings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $eventFilter = $this->option('event');
        $format = $this->option('format');
        $showVariables = $this->option('show-variables');

        // Get all configured templates
        $templates = Config::get('mail-templates.templates', []);

        if (empty($templates)) {
            $this->warn('No templates configured in mail-templates.php');

            return self::FAILURE;
        }

        // Filter by event if specified
        if ($eventFilter) {
            $templates = array_filter(
                $templates,
                fn ($key) => str_contains($key, $eventFilter),
                ARRAY_FILTER_USE_KEY
            );

            if (empty($templates)) {
                $this->warn("No templates found matching event: {$eventFilter}");

                return self::FAILURE;
            }
        }

        // Get environment prefix for current environment
        $environment = Config::get('app.env', 'production');
        $environmentPrefix = Config::get("mail-templates.environment_prefix.{$environment}", '');

        switch ($format) {
            case 'json':
                $this->outputJson($templates, $environmentPrefix);
                break;

            case 'csv':
                $this->outputCsv($templates, $environmentPrefix);
                break;

            default:
                $this->outputTable($templates, $environmentPrefix, $showVariables);
                break;
        }

        return self::SUCCESS;
    }

    /**
     * Output templates in table format.
     */
    protected function outputTable(array $templates, string $environmentPrefix, bool $showVariables): void
    {
        $this->info('Email Template Configuration');
        $this->line('Environment: '.Config::get('app.env'));
        $this->line('Prefix: '.($environmentPrefix ?: '(none)'));
        $this->newLine();

        $tableData = [];

        foreach ($templates as $eventName => $config) {
            $variables = $config['variables'] ?? [];
            $tags = $config['tags'] ?? [];

            $tableData[] = [
                $eventName,
                $environmentPrefix.$config['template_name'],
                $config['subject'] ?? '(from template)',
                count($variables),
                implode(', ', array_slice($tags, 0, 3)).(count($tags) > 3 ? '...' : ''),
            ];

            if ($showVariables && ! empty($variables)) {
                foreach ($variables as $varName => $rules) {
                    $tableData[] = [
                        '',
                        '  └─ '.$varName,
                        is_array($rules) ? implode('|', $rules) : $rules,
                        '',
                        '',
                    ];
                }
            }
        }

        $headers = ['Event Name', 'Mandrill Template', 'Subject', 'Variables', 'Tags'];

        $this->table($headers, $tableData);

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->line('• Total Templates: '.count($templates));
        $this->line('• Total Variables: '.array_sum(array_map(fn ($t) => count($t['variables'] ?? []), $templates)));

        // Group by entity
        $entities = [];
        foreach (array_keys($templates) as $eventName) {
            $entity = explode('.', $eventName)[0];
            $entities[$entity] = ($entities[$entity] ?? 0) + 1;
        }

        $this->newLine();
        $this->info('Templates by Entity:');
        foreach ($entities as $entity => $count) {
            $this->line("• {$entity}: {$count}");
        }
    }

    /**
     * Output templates in JSON format.
     */
    protected function outputJson(array $templates, string $environmentPrefix): void
    {
        $output = [
            'environment' => Config::get('app.env'),
            'prefix' => $environmentPrefix,
            'templates' => [],
        ];

        foreach ($templates as $eventName => $config) {
            $output['templates'][$eventName] = [
                'template_name' => $config['template_name'],
                'full_name' => $environmentPrefix.$config['template_name'],
                'subject' => $config['subject'] ?? null,
                'variables' => $config['variables'] ?? [],
                'tags' => $config['tags'] ?? [],
            ];
        }

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Output templates in CSV format.
     */
    protected function outputCsv(array $templates, string $environmentPrefix): void
    {
        // Header
        $this->line('Event,Template,Full Name,Subject,Variable Count,Tags');

        foreach ($templates as $eventName => $config) {
            $variables = $config['variables'] ?? [];
            $tags = $config['tags'] ?? [];

            $this->line(implode(',', [
                '"'.$eventName.'"',
                '"'.$config['template_name'].'"',
                '"'.$environmentPrefix.$config['template_name'].'"',
                '"'.($config['subject'] ?? '').'"',
                count($variables),
                '"'.implode(';', $tags).'"',
            ]));
        }
    }
}
