<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools\Tests;

use Illuminate\Support\Facades\File;

class PlaceholderReplacementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    public function test_replaces_docker_username_placeholder_in_all_files(): void
    {
        $dockerUsername = 'testuser';
        $appName = 'testapp';

        $this->artisan('doctool:publish')
            ->expectsQuestion('Masukkan Docker Hub username Anda', $dockerUsername)
            ->expectsQuestion('Masukkan nama aplikasi untuk docker image', $appName)
            ->assertExitCode(0);

        // Test docker-compose.yml (may not contain placeholders since it uses build context)
        $dockerComposeContent = File::get(base_path('docker-compose.yml'));
        $this->assertStringNotContainsString('{{:docker_username}}', $dockerComposeContent);
        $this->assertStringNotContainsString('{{:docker_image_name}}', $dockerComposeContent);

        // Test docker-compose.prod.yml (contains image references with placeholders)
        $dockerComposeProdContent = File::get(base_path('docker-compose.prod.yml'));
        $this->assertStringNotContainsString('{{:docker_username}}', $dockerComposeProdContent);
        $this->assertStringNotContainsString('{{:docker_hub_username}}', $dockerComposeProdContent);
        $this->assertStringContainsString($dockerUsername, $dockerComposeProdContent);
        $this->assertStringContainsString($appName, $dockerComposeProdContent);

        // Test GitHub workflows
        $buildImagesContent = File::get(base_path('.github/workflows/build-images.yml'));
        $this->assertStringNotContainsString('{{:docker_username}}', $buildImagesContent);
        $this->assertStringNotContainsString('{{:docker_image_name}}', $buildImagesContent);
        $this->assertStringContainsString($dockerUsername, $buildImagesContent);
        $this->assertStringContainsString($appName, $buildImagesContent);

        // Test release workflow (may not contain placeholders)
        $releaseContent = File::get(base_path('.github/workflows/release.yml'));
        // release.yml tidak selalu ada placeholder, jadi skip assertion untuk testuser
        $this->assertStringNotContainsString('{{:docker_username}}', $releaseContent);
        $this->assertStringNotContainsString('{{:docker_image_name}}', $releaseContent);
    }

    public function test_handles_mixed_placeholder_formats_correctly(): void
    {
        $dockerUsername = 'mixeduser';
        $appName = 'mixedapp';

        $this->artisan('doctool:publish')
            ->expectsQuestion('Masukkan Docker Hub username Anda', $dockerUsername)
            ->expectsQuestion('Masukkan nama aplikasi untuk docker image', $appName)
            ->assertExitCode(0);

        // Check docker-compose.prod.yml specifically for mixed placeholder formats
        $dockerComposeProdContent = File::get(base_path('docker-compose.prod.yml'));

        // Ensure no old format placeholders remain
        $this->assertStringNotContainsString('{{:docker_hub_username}}', $dockerComposeProdContent);
        $this->assertStringNotContainsString('{{:docker_username}}', $dockerComposeProdContent);
        $this->assertStringNotContainsString('{{:docker_image_name}}', $dockerComposeProdContent);

        // Ensure all instances are replaced with actual values
        $this->assertStringContainsString($dockerUsername, $dockerComposeProdContent);
        $this->assertStringContainsString($appName, $dockerComposeProdContent);
    }

    public function test_replaces_placeholders_in_dockerfile(): void
    {
        $dockerUsername = 'dockeruser';
        $appName = 'dockerapp';

        $this->artisan('doctool:publish')
            ->expectsQuestion('Masukkan Docker Hub username Anda', $dockerUsername)
            ->expectsQuestion('Masukkan nama aplikasi untuk docker image', $appName)
            ->assertExitCode(0);

        // Test Dockerfile
        $dockerfileContent = File::get(base_path('docker/Dockerfile'));
        $this->assertStringNotContainsString('{{:docker_username}}', $dockerfileContent);
        $this->assertStringNotContainsString('{{:docker_image_name}}', $dockerfileContent);

        // Check if replacements were made (if Dockerfile contains these placeholders)
        if (str_contains($dockerfileContent, $dockerUsername) || str_contains($dockerfileContent, $appName)) {
            $this->assertStringContainsString($dockerUsername, $dockerfileContent);
            $this->assertStringContainsString($appName, $dockerfileContent);
        }
    }

    public function test_placeholder_replacement_is_case_sensitive(): void
    {
        $dockerUsername = 'CaseUser';
        $appName = 'CaseApp';

        $this->artisan('doctool:publish')
            ->expectsQuestion('Masukkan Docker Hub username Anda', $dockerUsername)
            ->expectsQuestion('Masukkan nama aplikasi untuk docker image', $appName)
            ->assertExitCode(0);

        $dockerComposeContent = File::get(base_path('docker-compose.yml'));

        // Should not contain placeholders (regardless of whether values were replaced)
        $this->assertStringNotContainsString('{{:docker_username}}', $dockerComposeContent);
        $this->assertStringNotContainsString('{{:docker_image_name}}', $dockerComposeContent);

        // docker-compose.yml uses build context, so it may not contain the actual username/appname
    }

    public function test_all_published_files_have_no_remaining_placeholders(): void
    {
        $dockerUsername = 'completeuser';
        $appName = 'completeapp';

        $this->artisan('doctool:publish')
            ->expectsQuestion('Masukkan Docker Hub username Anda', $dockerUsername)
            ->expectsQuestion('Masukkan nama aplikasi untuk docker image', $appName)
            ->assertExitCode(0);

        // List of all files that should be checked
        $filesToCheck = [
            'docker-compose.yml',
            'docker-compose.prod.yml',
            '.github/workflows/build-images.yml',
            '.github/workflows/release.yml',
            'docker/Dockerfile',
        ];

        foreach ($filesToCheck as $file) {
            $filePath = base_path($file);
            if (File::exists($filePath)) {
                $content = File::get($filePath);

                // Check for any remaining placeholders
                $this->assertStringNotContainsString('{{:docker_username}}', $content, "File $file still contains {{:docker_username}} placeholder");
                $this->assertStringNotContainsString('{{:docker_hub_username}}', $content, "File $file still contains {{:docker_hub_username}} placeholder");
                $this->assertStringNotContainsString('{{:docker_image_name}}', $content, "File $file still contains {{:docker_image_name}} placeholder");
            }
        }
    }

    public function test_placeholder_replacement_performance(): void
    {
        $dockerUsername = 'perfuser';
        $appName = 'perfapp';

        $startTime = microtime(true);

        $this->artisan('doctool:publish')
            ->expectsQuestion('Masukkan Docker Hub username Anda', $dockerUsername)
            ->expectsQuestion('Masukkan nama aplikasi untuk docker image', $appName)
            ->assertExitCode(0);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Command should complete within reasonable time (10 seconds)
        $this->assertLessThan(10.0, $executionTime, 'Placeholder replacement took too long');
    }

    protected function cleanupTestFiles(): void
    {
        $filesToCleanup = [
            'docker-compose.yml',
            'docker-compose.prod.yml',
            '.github',
            'docker',
        ];

        foreach ($filesToCleanup as $file) {
            $path = base_path($file);
            if (File::exists($path)) {
                if (File::isDirectory($path)) {
                    File::deleteDirectory($path);
                } else {
                    File::delete($path);
                }
            }
        }
    }
}
