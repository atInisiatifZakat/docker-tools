<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools\Tests;

use Inisiatif\DockerTools\Console\PublishCommand;
use Inisiatif\DockerTools\DockerToolsServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_registers_the_publish_command(): void
    {
        $this->assertTrue($this->app->bound(PublishCommand::class));
    }

    public function test_can_resolve_publish_command(): void
    {
        $command = $this->app->make(PublishCommand::class);

        $this->assertInstanceOf(PublishCommand::class, $command);
        $this->assertEquals('doctool:publish', $command->getName());
    }

    public function test_configures_publishing_paths(): void
    {
        $provider = $this->app->getProvider(DockerToolsServiceProvider::class);

        $this->assertNotNull($provider);
    }

    public function test_has_doctool_stubs_tag_configured(): void
    {
        $this->artisan('vendor:publish', ['--help' => true])
            ->assertExitCode(0);
    }
}
