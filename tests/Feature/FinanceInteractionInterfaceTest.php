<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceInteractionInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_filter_pages_expose_live_browser_hooks(): void
    {
        $user = $this->completedUser();

        foreach (
            [
                'transactions.index' => 'transactions',
                'budgets.index' => 'budgets',
                'subscriptions.index' => 'subscriptions',
            ] as $routeName => $browser
        ) {
            $this
                ->actingAs($user)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee(
                    'data-finance-browser="'.$browser.'"',
                    false
                )
                ->assertSee(
                    'data-finance-filter-form',
                    false
                )
                ->assertSee(
                    'data-finance-search',
                    false
                )
                ->assertSee(
                    'data-finance-reset',
                    false
                );
        }
    }

    public function test_account_page_exposes_animated_ordering_hooks(): void
    {
        $user = $this->completedUser();

        Account::factory()->count(2)->sequence(
            ['sort_order' => 1],
            ['sort_order' => 2],
        )->create([
            'user_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('data-account-order-list', false)
            ->assertSee('data-account-order-card', false)
            ->assertSee('data-account-move-form', false)
            ->assertSee('data-direction="up"', false)
            ->assertSee('data-direction="down"', false);
    }

    public function test_account_move_can_return_json_for_async_updates(): void
    {
        $user = $this->completedUser();

        $first = Account::factory()->create([
            'user_id' => $user->id,
            'sort_order' => 1,
        ]);

        $second = Account::factory()->create([
            'user_id' => $user->id,
            'sort_order' => 2,
        ]);

        $this
            ->actingAs($user)
            ->patchJson(
                route('accounts.move', $second),
                ['direction' => 'up']
            )
            ->assertOk()
            ->assertJson([
                'account_id' => $second->id,
                'direction' => 'up',
            ]);

        $this->assertSame(
            [$second->id, $first->id],
            $user->accounts()->pluck('id')->all()
        );
    }

    public function test_frontend_supports_debounce_abort_and_flip_animation(): void
    {
        $financeBrowser = file_get_contents(
            resource_path('js/features/finance-browser.js')
        );

        $accountOrdering = file_get_contents(
            resource_path('js/features/account-ordering.js')
        );

        $forms = file_get_contents(
            resource_path('css/ui/forms.css')
        );

        $this->assertStringContainsString(
            'SEARCH_DEBOUNCE',
            $financeBrowser
        );
        $this->assertStringContainsString(
            'AbortController',
            $financeBrowser
        );
        $this->assertStringContainsString(
            'history.pushState',
            $financeBrowser
        );
        $this->assertStringContainsString(
            'getBoundingClientRect',
            $accountOrdering
        );
        $this->assertStringContainsString(
            'translateY',
            $accountOrdering
        );
        $this->assertStringContainsString(
            'line-height: 1.25',
            $forms
        );
    }

    private function completedUser(): User
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
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

        return $user;
    }
}
