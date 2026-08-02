<?php

namespace Tests\Feature;

use Illuminate\Console\Command;
use Tests\TestCase;

class ReleaseReadinessCommandTest extends TestCase
{
    public function test_production_check_rejects_debug_mode(): void
    {
        $this->configureProductionBaseline();
        config()->set('app.debug', true);

        $this->artisan('laras:release-check', [
            '--production' => true,
            '--skip-database' => true,
            '--skip-filesystem' => true,
        ])
            ->expectsOutputToContain('APP_DEBUG harus false.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_production_check_accepts_safe_runtime_configuration(): void
    {
        $this->configureProductionBaseline();

        $this->artisan('laras:release-check', [
            '--production' => true,
            '--skip-database' => true,
            '--skip-filesystem' => true,
        ])
            ->expectsOutputToContain('Release check lulus')
            ->assertExitCode(Command::SUCCESS);
    }

    private function configureProductionBaseline(): void
    {
        config()->set([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://laras.example.test',
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'session.driver' => 'database',
            'session.encrypt' => true,
            'cache.default' => 'database',
            'queue.default' => 'database',
            'observability.eloquent.strict' => false,
            'observability.queries.response_headers' => false,
            'logging.channels.single.level' => 'warning',
            'mail.default' => 'smtp',
        ]);
    }
}
