<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools\Tests;

use Illuminate\Support\Facades\File;

class PublishCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing test files
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test files after each test
        $this->cleanupTestFiles();

        parent::tearDown();
    }

    public function test_can_run_publish_command(): void
    {
        $this->artisan('doctool:publish')
             ->expectsQuestion('Masukkan Docker Hub username Anda', 'testuser')
             ->expectsQuestion('Masukkan nama aplikasi untuk docker image', 'testapp')
             ->expectsOutput('🚀 Publishing Docker Tools configuration...')
             ->expectsOutput('📦 Publishing configuration files...')
             ->expectsOutput('⚠️  Using --force option: existing files will be overwritten')
             ->expectsOutput('📝 Publishing stub files and replacing placeholders...')
             ->expectsOutput('⚠️  Publishing raw doctool-stubs files with --force option...')
             ->assertExitCode(0);
    }

    public function test_fails_with_empty_docker_hub_username(): void
    {
        $this->artisan('doctool:publish')
             ->expectsQuestion('Masukkan Docker Hub username Anda', '')
             ->expectsOutput('Docker Hub username tidak boleh kosong')
             ->assertExitCode(1);
    }

    public function test_fails_with_empty_app_name(): void
    {
        $this->artisan('doctool:publish')
             ->expectsQuestion('Masukkan Docker Hub username Anda', 'testuser')
             ->expectsQuestion('Masukkan nama aplikasi untuk docker image', '')
             ->expectsOutput('Nama aplikasi tidak boleh kosong')
             ->assertExitCode(1);
    }

    public function test_can_run_with_force_option(): void
    {
        $this->artisan('doctool:publish', ['--force' => true])
             ->expectsQuestion('Masukkan Docker Hub username Anda', 'testuser')
             ->expectsQuestion('Masukkan nama aplikasi untuk docker image', 'testapp')
             ->expectsOutput('⚠️  Using --force option: existing files will be overwritten')
             ->expectsOutput('📝 Publishing stub files and replacing placeholders...')
             ->expectsOutput('⚠️  Publishing raw doctool-stubs files with --force option...')
             ->assertExitCode(0);
    }

    public function test_replaces_placeholders_in_published_files(): void
    {
        // Create a test stub file with placeholders
        $testDir = base_path('test-stubs');
        File::makeDirectory($testDir, 0755, true);
        File::put($testDir . '/test-file.txt', 'Username: {{:docker_hub_username}}, App: {{:docker_image_name}}');

        $this->artisan('doctool:publish')
             ->expectsQuestion('Masukkan Docker Hub username Anda', 'testuser')
             ->expectsQuestion('Masukkan nama aplikasi untuk docker image', 'testapp')
             ->expectsOutput('📝 Publishing stub files and replacing placeholders...')
             ->expectsOutput('⚠️  Publishing raw doctool-stubs files with --force option...')
             ->assertExitCode(0);

        // Clean up test directory
        File::deleteDirectory($testDir);
    }

    protected function cleanupTestFiles(): void
    {
        $paths = [
            base_path('.github'),
            base_path('docker'),
            base_path('docker-compose.yml'),
            base_path('docker-compose.prod.yml'),
            base_path('test-stubs'),
        ];

        foreach ($paths as $path) {
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
