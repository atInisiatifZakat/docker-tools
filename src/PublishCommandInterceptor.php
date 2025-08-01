<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\VendorPublishCommand;

class PublishCommandInterceptor extends VendorPublishCommand
{
    public function handle(): int
    {
        $tag = $this->option('tag');

        if ($tag === 'doctool-stubs') {
            $forceOption = $this->option('force');

            if ($forceOption) {
                // User used --force, assume they understand they're publishing raw files
                $this->warn('⚠️  Publishing raw doctool-stubs files with --force option...');
                $this->line('   Make sure to manually replace placeholders:');
                $this->line('   • {{:docker_hub_username}}');
                $this->line('   • {{:docker_image_name}}');
            } else {
                // No --force option - show warning and ask for confirmation
                $this->warn('⚠️  You are about to publish doctool-stubs files that contain placeholders!');
                $this->line('');
                $this->error('❌ Publishing raw stub files will result in invalid configurations with placeholders like:');
                $this->line('   • {{:docker_hub_username}}');
                $this->line('   • {{:docker_image_name}}');
                $this->line('');
                $this->info('💡 Recommended: Use the doctool:publish command instead:');
                $this->line('   <comment>php artisan doctool:publish</comment>');
                $this->line('');
                $this->line('This command will properly replace placeholders with your actual values.');
                $this->line('');
                $this->info('💡 Alternative: Use --force if you want to publish raw files:');
                $this->line('   <comment>php artisan vendor:publish --tag=doctool-stubs --force</comment>');
                $this->line('');

                if (!$this->confirm('Do you still want to continue with raw stub publishing?', false)) {
                    $this->info('✅ Publishing aborted. Use <comment>php artisan doctool:publish</comment> for proper setup.');

                    return Command::SUCCESS;
                }

                $this->warn('⚠️  Proceeding with raw stub publishing...');
            }
        }

        parent::handle();

        return Command::SUCCESS;
    }
}
