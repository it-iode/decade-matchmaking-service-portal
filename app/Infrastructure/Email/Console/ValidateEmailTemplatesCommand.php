<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Console;

use App\Infrastructure\Email\Services\EmailTemplateService;
use App\Infrastructure\Email\Services\MandrillClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ValidateEmailTemplatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:validate-templates
                          {--env=production : Environment to validate against (production, staging, development, local)}
                          {--show-missing : Show details about missing templates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate that all configured email templates exist in Mandrill';

    /**
     * Execute the console command.
     */
    public function handle(EmailTemplateService $emailService, MandrillClient $mandrillClient): int
    {
        $environment = $this->option('env');
        $showMissing = $this->option('show-missing');

        $this->info("Validating email templates for environment: {$environment}");
        $this->newLine();

        // Get all configured templates
        $templates = Config::get('mail-templates.templates', []);
        $environmentPrefix = Config::get("mail-templates.environment_prefix.{$environment}", '');

        if (empty($templates)) {
            $this->warn('No templates configured in mail-templates.php');

            return self::FAILURE;
        }

        // Get available templates from Mandrill
        try {
            $mandrillTemplates = $mandrillClient->listTemplates();
            $mandrillTemplateNames = array_column($mandrillTemplates, 'name');
        } catch (\Exception $e) {
            $this->error('Failed to fetch templates from Mandrill: '.$e->getMessage());

            return self::FAILURE;
        }

        $errors = [];
        $warnings = [];
        $valid = [];

        $progressBar = $this->output->createProgressBar(count($templates));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($templates as $eventName => $config) {
            $progressBar->setMessage("Checking: {$eventName}");
            $progressBar->advance();

            $templateName = $environmentPrefix.$config['template_name'];

            if (! in_array($templateName, $mandrillTemplateNames, true)) {
                $errors[$eventName] = [
                    'expected' => $templateName,
                    'config' => $config['template_name'],
                    'prefix' => $environmentPrefix,
                ];
            } else {
                $valid[$eventName] = $templateName;

                // Check if template has required merge vars
                $template = array_filter($mandrillTemplates, fn ($t) => $t['name'] === $templateName);
                if (! empty($template)) {
                    $template = reset($template);
                    $requiredVars = array_keys($config['variables'] ?? []);
                    $missingVars = [];

                    // Note: Mandrill API doesn't return merge vars directly, so this is informational
                    if (! empty($requiredVars)) {
                        $warnings[$eventName] = 'Template configured with '.count($requiredVars).' variables';
                    }
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        if (! empty($valid)) {
            $this->info('✓ Valid templates: '.count($valid));
            if ($this->output->isVerbose()) {
                foreach ($valid as $eventName => $templateName) {
                    $this->line("  • {$eventName} → {$templateName}");
                }
            }
        }

        if (! empty($warnings)) {
            $this->newLine();
            $this->warn('⚠ Warnings: '.count($warnings));
            foreach ($warnings as $eventName => $warning) {
                $this->line("  • {$eventName}: {$warning}");
            }
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->error('✗ Missing templates: '.count($errors));

            if ($showMissing) {
                $this->newLine();
                $this->table(
                    ['Event', 'Expected Template', 'Config Name', 'Prefix'],
                    array_map(fn ($eventName, $error) => [
                        $eventName,
                        $error['expected'],
                        $error['config'],
                        $error['prefix'] ?: '(none)',
                    ], array_keys($errors), $errors)
                );

                $this->newLine();
                $this->info('To fix missing templates:');
                $this->line('1. Log into Mandrill/Mailchimp Transactional');
                $this->line('2. Create templates with the exact names shown above');
                $this->line('3. Add the required merge variables for each template');
                $this->line('4. Test the template rendering');
            } else {
                foreach ($errors as $eventName => $error) {
                    $this->line("  • {$eventName} → {$error['expected']} (missing)");
                }
                $this->newLine();
                $this->line('Run with --show-missing to see detailed information');
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All templates validated successfully!');

        // Show summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Valid Templates', count($valid)],
                ['Warnings', count($warnings)],
                ['Errors', count($errors)],
                ['Total Configured', count($templates)],
                ['Mandrill Templates', count($mandrillTemplates)],
            ]
        );

        return self::SUCCESS;
    }
}
