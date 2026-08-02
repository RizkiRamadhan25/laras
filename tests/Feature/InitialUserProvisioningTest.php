<?php

namespace Tests\Feature;

use App\Models\FinanceCategory;
use App\Models\User;
use App\Services\InitialUserProvisioningService;
use Database\Seeders\LarasUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class InitialUserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGINAL_PASSWORD =
        'Original#Pass123';

    private const REPLACEMENT_PASSWORD =
        'Replacement#Pass456';

    public function test_service_creates_initial_user_with_hashed_password(): void
    {
        $result = app(
            InitialUserProvisioningService::class
        )->provision(
            'Pengguna Awal',
            'owner@example.test',
            self::ORIGINAL_PASSWORD
        );

        $this->assertTrue(
            $result['created']
        );

        $user = $result['user']
            ->fresh();

        $this->assertSame(
            'Pengguna Awal',
            $user->name
        );

        $this->assertSame(
            'owner@example.test',
            $user->email
        );

        $this->assertTrue(
            Hash::check(
                self::ORIGINAL_PASSWORD,
                $user->password
            )
        );

        $this->assertTrue(
            $user->is_active
        );

        $this->assertNotNull(
            $user->email_verified_at
        );
    }

    public function test_repeated_provisioning_does_not_reset_existing_user(): void
    {
        $service = app(
            InitialUserProvisioningService::class
        );

        $created = $service->provision(
            'Nama Pertama',
            'owner@example.test',
            self::ORIGINAL_PASSWORD
        );

        $existing = $service->provision(
            'Nama Pengganti',
            'OWNER@example.test',
            self::REPLACEMENT_PASSWORD
        );

        $this->assertFalse(
            $existing['created']
        );

        $user = $created['user']
            ->fresh();

        $this->assertSame(
            'Nama Pertama',
            $user->name
        );

        $this->assertTrue(
            Hash::check(
                self::ORIGINAL_PASSWORD,
                $user->password
            )
        );

        $this->assertFalse(
            Hash::check(
                self::REPLACEMENT_PASSWORD,
                $user->password
            )
        );
    }

    public function test_seeder_is_idempotent_and_does_not_reset_password(): void
    {
        config()->set(
            'laras.user.name',
            'Pemilik Laras'
        );

        config()->set(
            'laras.user.email',
            'owner@example.test'
        );

        config()->set(
            'laras.user.password',
            self::ORIGINAL_PASSWORD
        );

        $this->seed(
            LarasUserSeeder::class
        );

        config()->set(
            'laras.user.name',
            'Nama Baru'
        );

        config()->set(
            'laras.user.password',
            self::REPLACEMENT_PASSWORD
        );

        $this->seed(
            LarasUserSeeder::class
        );

        $user = User::query()
            ->where(
                'email',
                'owner@example.test'
            )
            ->firstOrFail();

        $this->assertSame(
            1,
            User::query()->count()
        );

        $this->assertSame(
            'Pemilik Laras',
            $user->name
        );

        $this->assertTrue(
            Hash::check(
                self::ORIGINAL_PASSWORD,
                $user->password
            )
        );
    }

    public function test_provision_command_creates_categories_and_is_safe_to_rerun(): void
    {
        config()->set(
            'laras.user.name',
            'Pemilik Laras'
        );

        config()->set(
            'laras.user.email',
            'owner@example.test'
        );

        config()->set(
            'laras.user.password',
            self::ORIGINAL_PASSWORD
        );

        $this->artisan(
            'laras:provision-user'
        )->assertSuccessful();

        $user = User::query()
            ->where(
                'email',
                'owner@example.test'
            )
            ->firstOrFail();

        $this->assertGreaterThan(
            0,
            FinanceCategory::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->count()
        );

        config()->set(
            'laras.user.password',
            self::REPLACEMENT_PASSWORD
        );

        $this->artisan(
            'laras:provision-user'
        )->assertSuccessful();

        $this->assertSame(
            1,
            User::query()->count()
        );

        $this->assertTrue(
            Hash::check(
                self::ORIGINAL_PASSWORD,
                $user->fresh()->password
            )
        );
    }

    public function test_new_user_requires_a_strong_password(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            InitialUserProvisioningService::class
        )->provision(
            'Pengguna Awal',
            'owner@example.test',
            'password'
        );
    }
}
