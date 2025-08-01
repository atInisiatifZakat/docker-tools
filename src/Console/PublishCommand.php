<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'doctool:publish', description: 'Publish the Docker Tools configuration')]
class PublishCommand extends Command
{
    protected $signature = 'doctool:publish
                {--force : Overwrite any existing files}';

    protected $description = 'Publish the Docker Tools configuration';

    public function handle(): int
    {
        $this->info('🚀 Publishing Docker Tools configuration...');

        try {
            // Validate and get user input
            $dockerHubUsername = $this->validateInput(
                $this->ask('Masukkan Docker Hub username Anda'),
                'Docker Hub username tidak boleh kosong'
            );

            $appName = $this->validateInput(
                $this->ask('Masukkan nama aplikasi untuk docker image'),
                'Nama aplikasi tidak boleh kosong'
            );
        } catch (\InvalidArgumentException $e) {
            return self::FAILURE;
        }

        // Publish the stubs
        $this->info('📦 Publishing configuration files...');

        $publishOptions = ['--tag' => 'doctool-stubs', '--force' => true];

        // Show force option warning
        $this->warn('⚠️  Using --force option: existing files will be overwritten');

        $this->line('📝 Publishing stub files and replacing placeholders...');

        $exitCode = $this->call('vendor:publish', $publishOptions);

        if ($exitCode !== 0) {
            $this->error('❌ Failed to publish configuration files');

            return self::FAILURE;
        }

        // Replace placeholders in different locations
        $locations = [
            [
                'path' => $this->getLaravel()->basePath('.github'),
                'description' => 'GitHub workflows',
            ],
            [
                'path' => $this->getLaravel()->basePath('docker'),
                'description' => 'Docker configuration',
            ],
        ];

        foreach ($locations as $location) {
            $this->processDirectory($location['path'], $dockerHubUsername, $appName, $location['description']);
        }

        // Handle docker-compose files separately
        $this->processDockerComposeFiles($dockerHubUsername, $appName);

        $this->newLine();
        $this->info('✅ Docker Tools configuration published successfully!');
        $this->line("   Docker Hub Username: <comment>{$dockerHubUsername}</comment>");
        $this->line("   Application Name: <comment>{$appName}</comment>");

        return self::SUCCESS;
    }

    /**
     * Validate user input
     */
    private function validateInput(?string $input, string $errorMessage): string
    {
        if (empty(trim($input))) {
            $this->error($errorMessage);

            throw new \InvalidArgumentException($errorMessage);
        }

        return trim($input);
    }

    /**
     * Process all files in a single directory (non-recursive)
     */
    private function processDirectory(string $path, string $dockerHubUsername, string $appName, string $description): void
    {
        if (!is_dir($path)) {
            $this->warn("⚠️  Directory not found: {$path}");

            return;
        }

        $this->line("🔄 Processing {$description}...");

        try {
            $files = glob($path . '/*');
            $filesProcessed = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($this->processFile($file, $dockerHubUsername, $appName)) {
                        $filesProcessed++;
                    }
                }
            }

            if ($filesProcessed > 0) {
                $this->line("   ✓ Processed {$filesProcessed} file(s)");
            } else {
                $this->line("   ℹ️  No files to process");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error processing directory {$path}: " . $e->getMessage());
        }
    }

    /**
     * Process docker-compose files
     */
    private function processDockerComposeFiles(string $dockerHubUsername, string $appName): void
    {
        $this->line("🔄 Processing docker-compose files...");

        $files = glob($this->getLaravel()->basePath() . '/docker-compose*');
        $filesProcessed = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($this->processFile($file, $dockerHubUsername, $appName)) {
                    $filesProcessed++;
                }
            }
        }

        if ($filesProcessed > 0) {
            $this->line("   ✓ Processed {$filesProcessed} docker-compose file(s)");
        } else {
            $this->line("   ℹ️  No docker-compose files found");
        }
    }

    /**
     * Process a single file
     */
    private function processFile(string $filePath, string $dockerHubUsername, string $appName): bool
    {
        try {
            $content = file_get_contents($filePath);

            if ($content === false) {
                $this->warn("⚠️  Could not read file: {$filePath}");

                return false;
            }

            $originalContent = $content;
            $content = str_replace('{{:docker_username}}', $dockerHubUsername, $content);
            $content = str_replace('{{:docker_image_name}}', $appName, $content);

            // Only write if content changed
            if ($content !== $originalContent) {
                if (file_put_contents($filePath, $content) === false) {
                    $this->error("❌ Could not write to file: {$filePath}");

                    return false;
                }

                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->error("❌ Error processing file {$filePath}: " . $e->getMessage());

            return false;
        }
    }
}
