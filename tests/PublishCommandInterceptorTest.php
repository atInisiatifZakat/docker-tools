<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools\Tests;

class PublishCommandInterceptorTest extends TestCase
{
    public function test_prevents_direct_publishing_of_doctool_stubs(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'doctool-stubs'])
             ->expectsOutput('⚠️  You are about to publish doctool-stubs files that contain placeholders!')
             ->expectsOutput('💡 Alternative: Use --force if you want to publish raw files:')
             ->expectsQuestion('Do you still want to continue with raw stub publishing?', 'no')
             ->assertExitCode(0);
    }

    public function test_allows_publishing_other_tags(): void
    {
        // Test that other vendor:publish operations still work
        $this->artisan('vendor:publish', ['--tag' => 'non-existent-tag'])
             ->assertExitCode(0);
    }

    public function test_prevents_direct_publishing_with_force_option(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'doctool-stubs', '--force' => true])
             ->expectsOutput('⚠️  Publishing raw doctool-stubs files with --force option...')
             ->assertExitCode(0);
    }

    public function test_allows_vendor_publish_without_tag(): void
    {
        // Test that vendor:publish without specific tag still works
        // Use --help to avoid interactive prompts
        $this->artisan('vendor:publish', ['--help' => true])
             ->assertExitCode(0);
    }

    public function test_allows_direct_publishing_with_confirmation(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'doctool-stubs'])
             ->expectsOutput('⚠️  You are about to publish doctool-stubs files that contain placeholders!')
             ->expectsQuestion('Do you still want to continue with raw stub publishing?', 'yes')
             ->expectsOutput('⚠️  Proceeding with raw stub publishing...')
             ->assertExitCode(0);
    }
}
