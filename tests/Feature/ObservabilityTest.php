<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_response_exposes_query_metrics_in_testing(): void
    {
        config()->set(
            'observability.queries.response_headers',
            true
        );

        $response = $this->get(
            route('login')
        );

        $response
            ->assertOk()
            ->assertHeader(
                'X-DB-Query-Count'
            )
            ->assertHeader(
                'X-DB-Query-Time-Ms'
            )
            ->assertHeader(
                'X-DB-Slowest-Query-Ms'
            );

        $this->assertIsNumeric(
            $response->headers->get(
                'X-DB-Query-Count'
            )
        );

        $this->assertStringContainsString(
            'db;dur=',
            (string) $response->headers->get(
                'Server-Timing'
            )
        );
    }

    public function test_strict_mode_detects_lazy_loading_when_enabled(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Account::factory()
            ->for($firstUser)
            ->create();

        Model::shouldBeStrict(true);

        try {
            /*
             * Laravel menerapkan pencegahan lazy loading
             * pada model yang dihidrasi sebagai koleksi.
             *
             * Karena itu, ambil lebih dari satu model agar
             * test merepresentasikan kondisi N+1 sebenarnya.
             */
            $users = User::query()
                ->whereKey([
                    $firstUser->id,
                    $secondUser->id,
                ])
                ->orderBy('id')
                ->get();

            $this->assertCount(
                2,
                $users
            );

            $targetUser = $users->firstWhere(
                'id',
                $firstUser->id
            );

            $this->assertNotNull(
                $targetUser
            );

            $targetUser->accounts->count();

            $this->fail(
                'LazyLoadingViolationException tidak dilempar.'
            );
        } catch (
            LazyLoadingViolationException
        ) {
            $this->assertTrue(true);
        } finally {
            Model::shouldBeStrict(false);
        }
    }
}
