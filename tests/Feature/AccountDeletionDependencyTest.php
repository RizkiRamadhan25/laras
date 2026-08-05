<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Account;
use App\Models\Activity;
use App\Models\FinanceCategory;
use App\Models\RecommendationInteraction;
use App\Models\SecurityEvent;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountDeletionDependencyTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Current#Pass123';

    public function test_user_with_complete_dependency_graph_can_be_deleted(): void
    {
        Storage::fake('public');

        $user = $this->user('Pengguna Dihapus');
        $otherUser = $this->user('Pengguna Dipertahankan');

        $photoPath = 'profile-photos/'
            .$user->id
            .'/profile.webp';

        Storage::disk('public')->put(
            $photoPath,
            'profile-photo'
        );

        $user->forceFill([
            'profile_photo_path' => $photoPath,
        ])->save();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Pengguna Dihapus',
        ]);

        $otherAccount = Account::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'BCA Pengguna Dipertahankan',
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
                'name' => 'Langganan Pengguna Dihapus',
            ]);

        $transaction = Transaction::factory()
            ->posted()
            ->expense()
            ->create([
                'user_id' => $user->id,
                'description' => 'Tagihan pengujian',
            ]);

        $entry = TransactionEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'finance_category_id' => $category->id,
            'amount' => '-59000.00',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'finance_category_id' => $category->id,
            'name' => 'Langganan Pengujian',
        ]);

        $billing = SubscriptionBilling::factory()
            ->posted()
            ->create([
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
            ]);

        $budget = app(BudgetService::class)->create(
            $user,
            $category,
            [
                'name' => 'Anggaran Pengujian',
                'amount' => '500000.00',
                'period_type' => BudgetPeriodType::Monthly->value,
                'warning_threshold_percent' => '80.00',
                'start_date' => now()
                    ->startOfMonth()
                    ->toDateString(),
            ]
        );

        $period = $budget->periods()->firstOrFail();

        $alertEventId = DB::table(
            'budget_alert_events'
        )->insertGetId([
            'budget_period_id' => $period->id,
            'alert_level' => 'warning',
            'notified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Aktivitas Pengujian',
        ]);

        $interaction = RecommendationInteraction::query()->create([
            'user_id' => $user->id,
            'recommendation_key' => 'test-recommendation',
            'recommendation_kind' => 'finance',
            'interaction_type' => 'opened',
            'title' => 'Rekomendasi Pengujian',
            'snapshot' => null,
            'occurred_at' => now(),
        ]);

        $securityEvent = SecurityEvent::query()->create([
            'user_id' => $user->id,
            'type' => 'password_changed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'occurred_at' => now(),
        ]);

        $passkeyId = DB::table('passkeys')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Passkey Pengujian',
            'credential_id' => 'credential-'.$user->id,
            'credential' => '{}',
            'last_used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Tests\\Notification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => '{}',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => 'session-user-'.$user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'token-user',
            'created_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->delete(
                route('settings.account.destroy'),
                [
                    'delete_current_password' => self::PASSWORD,
                    'confirmation' => 'HAPUS AKUN',
                ]
            )
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status');

        $this->assertGuest();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);

        $this->assertDatabaseMissing('finance_categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);

        $this->assertDatabaseMissing('transaction_entries', [
            'id' => $entry->id,
        ]);

        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
        ]);

        $this->assertDatabaseMissing('subscription_billings', [
            'id' => $billing->id,
        ]);

        $this->assertDatabaseMissing('budgets', [
            'id' => $budget->id,
        ]);

        $this->assertDatabaseMissing('budget_periods', [
            'id' => $period->id,
        ]);

        $this->assertDatabaseMissing('budget_alert_events', [
            'id' => $alertEventId,
        ]);

        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);

        $this->assertDatabaseMissing('recommendation_interactions', [
            'id' => $interaction->id,
        ]);

        $this->assertDatabaseMissing('security_events', [
            'id' => $securityEvent->id,
        ]);

        $this->assertDatabaseMissing('passkeys', [
            'id' => $passkeyId,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
        ]);

        $this->assertDatabaseMissing('sessions', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $otherAccount->id,
            'user_id' => $otherUser->id,
        ]);

        Storage::disk('public')->assertMissing(
            $photoPath
        );
    }

    private function user(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        return $user->refresh();
    }
}
