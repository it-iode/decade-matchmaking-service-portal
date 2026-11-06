<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class LogTextCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:text {text} {--file=custom.log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log a text message to a specified log file';

    /**
     * Default log file name.
     */
    private const DEFAULT_LOG_FILE = 'custom.log';

    /**
     * Maximum allowed text length.
     */
    private const MAX_TEXT_LENGTH = 10000;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $text = $this->argument('text');
            $filePath = $this->option('file') ?? self::DEFAULT_LOG_FILE;

            $this->validateText($text);
            $this->validateFilePath($filePath);

            $fullPath = $this->resolveLogPath($filePath);
            $directory = dirname($fullPath);

            $this->ensureDirectoryExists($directory);
            $this->writeToLog($fullPath, $text);

            $this->info("Successfully logged to {$filePath}");

            return Command::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        } catch (RuntimeException $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Validate the text input.
     *
     * @param string $text
     * @throws InvalidArgumentException
     */
    private function validateText(string $text): void
    {
        if (trim($text) === '') {
            throw new InvalidArgumentException('Text cannot be empty');
        }

        if (strlen($text) > self::MAX_TEXT_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Text exceeds maximum length of %d characters', self::MAX_TEXT_LENGTH)
            );
        }
    }

    /**
     * Validate the file path.
     *
     * @param string $filePath
     * @throws InvalidArgumentException
     */
    private function validateFilePath(string $filePath): void
    {
        // Block path traversal attempts
        if (str_contains($filePath, '..')) {
            throw new InvalidArgumentException('Invalid file path');
        }

        // Block absolute paths
        if (str_starts_with($filePath, '/')) {
            throw new InvalidArgumentException('Invalid file path');
        }

        // Validate filename characters (alphanumeric, hyphens, underscores, dots, forward slashes)
        if (!preg_match('/^[a-zA-Z0-9\-_.\/]+$/', $filePath)) {
            throw new InvalidArgumentException('Invalid characters in filename');
        }
    }

    /**
     * Resolve the full log file path.
     *
     * @param string $filePath
     * @return string
     */
    private function resolveLogPath(string $filePath): string
    {
        return storage_path('logs') . DIRECTORY_SEPARATOR . $filePath;
    }

    /**
     * Ensure the directory exists.
     *
     * @param string $directory
     * @throws RuntimeException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Cannot create log directory');
            }
        }
    }

    /**
     * Write the text to the log file using Laravel's Log facade.
     *
     * @param string $path
     * @param string $text
     * @throws RuntimeException
     */
    private function writeToLog(string $path, string $text): void
    {
        try {
            // Create a custom log channel for the specified file path
            $logger = Log::build([
                'driver' => 'single',
                'path' => $path,
                'level' => 'info',
                'replace_placeholders' => true,
            ]);

            // Write the log entry with Laravel's logging system
            $logger->info($text);
        } catch (Throwable $e) {
            throw new RuntimeException('Cannot write to log file: ' . $e->getMessage(), 0, $e);
        }
    }
}
