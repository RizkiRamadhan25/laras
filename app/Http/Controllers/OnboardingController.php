<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\StoreOnboardingAccountsRequest;
use App\Http\Requests\StoreOnboardingPreferencesRequest;
use App\Models\Account;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->preference()->exists()) {
            return redirect()->route('onboarding.accounts');
        }

        return $this->preferencesView($request);
    }

    public function editPreferences(Request $request): View
    {
        return $this->preferencesView($request);
    }

    public function storePreferences(
        StoreOnboardingPreferencesRequest $request
    ): RedirectResponse {
        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'name' => $validated['name'],
            ]);

            UserPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'locale' => $validated['locale'],
                    'currency_code' => $validated['currency_code'],
                    'timezone' => $validated['timezone'],
                    'date_format' => $validated['date_format'],
                    'time_format' => $validated['time_format'],
                    'week_starts_on' => $validated['week_starts_on'],
                ],
            );
        });

        return redirect()
            ->route('onboarding.accounts')
            ->with(
                'status',
                'Preferensi berhasil disimpan. Lanjutkan dengan menyiapkan rekening awal.'
            );
    }

    public function accounts(
        Request $request
    ): View|RedirectResponse {
        $user = $request->user()->load([
            'preference',
            'accounts',
        ]);

        if ($user->preference === null) {
            return redirect()
                ->route('onboarding.preferences.edit')
                ->with(
                    'warning',
                    'Lengkapi preferensi personal terlebih dahulu.'
                );
        }

        $existingAccounts = $user->accounts
            ->map(
                fn (Account $account): array => [
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'institution' => $account->institution,
                    'initial_balance' => $account->initial_balance,
                    'account_number_last_four' => $account->account_number_last_four,
                    'color' => $account->color ?? '#2563EB',
                ]
            )
            ->all();

        return view('onboarding.accounts', [
            'user' => $user,
            'accountRows' => $existingAccounts !== []
                ? $existingAccounts
                : config('laras.account_presets'),
            'accountTypes' => AccountType::cases(),
        ]);
    }

    public function storeAccounts(
        StoreOnboardingAccountsRequest $request
    ): RedirectResponse {
        if (! $request->user()->preference()->exists()) {
            return redirect()
                ->route('onboarding.preferences.edit')
                ->with(
                    'warning',
                    'Lengkapi preferensi personal terlebih dahulu.'
                );
        }

        $validatedAccounts = $request->validated('accounts');
        $userId = $request->user()->id;

        DB::transaction(function () use (
            $userId,
            $validatedAccounts
        ): void {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($userId);

            /*
             * Permintaan kedua yang tiba bersamaan tidak boleh
             * membuat rekening duplikat.
             */
            if ($user->onboarding_completed_at !== null) {
                return;
            }

            $currencyCode = $user->preference()
                ->value('currency_code');

            /*
             * Selama onboarding belum selesai, daftar rekening
             * boleh diganti secara utuh tanpa menyisakan data lama.
             */
            $user->accounts()
                ->withTrashed()
                ->forceDelete();

            foreach (
                array_values($validatedAccounts) as $index => $account
            ) {
                $type = AccountType::from($account['type']);

                $user->accounts()->create([
                    'name' => $account['name'],
                    'type' => $type,
                    'institution' => $account['institution'] ?? null,
                    'currency_code' => $currencyCode,
                    'initial_balance' => $account['initial_balance'],
                    'cached_balance' => $account['initial_balance'],
                    'account_number_last_four' => $account['account_number_last_four'] ?? null,
                    'color' => $account['color'],
                    'icon' => $this->accountIcon($type),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);
            }

            $user->forceFill([
                'onboarding_completed_at' => now(),
            ])->save();
        }, 3);

        return redirect()
            ->route('dashboard')
            ->with(
                'status',
                'Pengaturan awal selesai. Selamat datang di Laras.'
            );
    }

    private function preferencesView(Request $request): View
    {
        return view('onboarding.show', [
            'user' => $request->user()->load('preference'),
            'preference' => $request->user()->preference,
            'defaults' => config('laras.defaults'),
        ]);
    }

    private function accountIcon(AccountType $type): string
    {
        return match ($type) {
            AccountType::Bank => 'landmark',
            AccountType::EWallet => 'smartphone',
            AccountType::Cash => 'wallet',
            AccountType::Investment => 'chart-no-axes-combined',
            AccountType::Other => 'circle-dollar-sign',
        };
    }
}
