<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Budget;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

class GlobalSearchService
{
    private const RESULT_LIMIT_PER_GROUP = 5;

    /**
     * @return array{
     *     query: string,
     *     total: int,
     *     groups: list<array{
     *         key: string,
     *         label: string,
     *         items: list<array<string, mixed>>
     *     }>
     * }
     */
    public function search(
        User $user,
        string $query
    ): array {
        $pattern = $this->likePattern($query);

        $groups = collect([
            $this->navigationGroup($query),
            $this->activityGroup($user, $pattern),
            $this->transactionGroup($user, $pattern),
            $this->accountGroup($user, $pattern),
            $this->budgetGroup($user, $pattern),
            $this->subscriptionGroup($user, $pattern),
        ])
            ->filter(
                static fn (array $group): bool => $group['items'] !== []
            )
            ->values();

        return [
            'query' => $query,
            'total' => $groups->sum(
                static fn (array $group): int => count($group['items'])
            ),
            'groups' => $groups->all(),
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function navigationGroup(
        string $query
    ): array {
        $needle = Str::lower($query);

        $items = collect(
            $this->navigationItems()
        )
            ->filter(
                static function (
                    array $item
                ) use ($needle): bool {
                    $haystack = Str::lower(
                        implode(' ', [
                            $item['title'],
                            $item['description'],
                            implode(
                                ' ',
                                $item['keywords']
                            ),
                        ])
                    );

                    return Str::contains(
                        $haystack,
                        $needle
                    );
                }
            )
            ->take(self::RESULT_LIMIT_PER_GROUP)
            ->map(
                static function (
                    array $item
                ): array {
                    unset($item['keywords']);

                    return $item;
                }
            )
            ->values()
            ->all();

        return $this->group(
            'navigation',
            'Navigasi',
            $items
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function activityGroup(
        User $user,
        string $pattern
    ): array {
        $items = Activity::query()
            ->where('user_id', $user->id)
            ->where(
                function ($query) use ($pattern): void {
                    $query
                        ->where('title', 'like', $pattern)
                        ->orWhere(
                            'description',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'location',
                            'like',
                            $pattern
                        );
                }
            )
            ->latest('updated_at')
            ->limit(self::RESULT_LIMIT_PER_GROUP)
            ->get()
            ->map(
                static fn (Activity $activity): array => [
                    'id' => 'activity:'.$activity->id,
                    'kind' => 'activity',
                    'title' => $activity->title,
                    'description' => implode(' • ', [
                        $activity->type->label(),
                        'Prioritas '
                            .strtolower(
                                $activity->priority->label()
                            ),
                    ]),
                    'meta' => $activity->status->label(),
                    'url' => route(
                        'activities.edit',
                        $activity
                    ),
                    'icon' => $activity->type->icon(),
                ]
            )
            ->all();

        return $this->group(
            'activities',
            'Aktivitas',
            $items
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function transactionGroup(
        User $user,
        string $pattern
    ): array {
        $items = Transaction::query()
            ->where('user_id', $user->id)
            ->where(
                function ($query) use ($pattern): void {
                    $query
                        ->where(
                            'description',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'counterparty',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'reference_number',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'notes',
                            'like',
                            $pattern
                        );
                }
            )
            ->latest('occurred_at')
            ->limit(self::RESULT_LIMIT_PER_GROUP)
            ->get()
            ->map(
                static fn (
                    Transaction $transaction
                ): array => [
                    'id' => 'transaction:'
                        .$transaction->id,
                    'kind' => 'transaction',
                    'title' => $transaction->description,
                    'description' => collect([
                        $transaction->type->label(),
                        $transaction->counterparty,
                    ])->filter()->implode(' • '),
                    'meta' => collect([
                        $transaction->status->label(),
                        $transaction->occurred_at
                            ?->translatedFormat('d M Y'),
                    ])->filter()->implode(' • '),
                    'url' => route(
                        'transactions.show',
                        $transaction
                    ),
                    'icon' => 'receipt-text',
                ]
            )
            ->all();

        return $this->group(
            'transactions',
            'Transaksi',
            $items
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function accountGroup(
        User $user,
        string $pattern
    ): array {
        $items = Account::query()
            ->where('user_id', $user->id)
            ->where(
                function ($query) use ($pattern): void {
                    $query
                        ->where('name', 'like', $pattern)
                        ->orWhere(
                            'institution',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'account_number_last_four',
                            'like',
                            $pattern
                        );
                }
            )
            ->orderByDesc('is_active')
            ->latest('updated_at')
            ->limit(self::RESULT_LIMIT_PER_GROUP)
            ->get()
            ->map(
                static fn (Account $account): array => [
                    'id' => 'account:'.$account->id,
                    'kind' => 'account',
                    'title' => $account->name,
                    'description' => collect([
                        $account->institution,
                        $account->type->label(),
                    ])->filter()->implode(' • '),
                    'meta' => $account->is_active
                        ? 'Aktif'
                        : 'Tidak aktif',
                    'url' => route(
                        'accounts.edit',
                        $account
                    ),
                    'icon' => $account->icon
                        ?: 'wallet-cards',
                ]
            )
            ->all();

        return $this->group(
            'accounts',
            'Rekening',
            $items
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function budgetGroup(
        User $user,
        string $pattern
    ): array {
        $items = Budget::query()
            ->with('financeCategory:id,name')
            ->where('user_id', $user->id)
            ->where(
                function ($query) use ($pattern): void {
                    $query
                        ->where('name', 'like', $pattern)
                        ->orWhereHas(
                            'financeCategory',
                            static fn ($categoryQuery) => $categoryQuery->where(
                                'name',
                                'like',
                                $pattern
                            )
                        );
                }
            )
            ->orderByDesc('is_active')
            ->latest('updated_at')
            ->limit(self::RESULT_LIMIT_PER_GROUP)
            ->get()
            ->map(
                static fn (Budget $budget): array => [
                    'id' => 'budget:'.$budget->id,
                    'kind' => 'budget',
                    'title' => $budget->name,
                    'description' => collect([
                        $budget->financeCategory?->name,
                        $budget->period_type->label(),
                    ])->filter()->implode(' • '),
                    'meta' => $budget->is_active
                        ? 'Aktif'
                        : 'Tidak aktif',
                    'url' => route(
                        'budgets.show',
                        $budget
                    ),
                    'icon' => 'piggy-bank',
                ]
            )
            ->all();

        return $this->group(
            'budgets',
            'Anggaran',
            $items
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function subscriptionGroup(
        User $user,
        string $pattern
    ): array {
        $items = Subscription::query()
            ->where('user_id', $user->id)
            ->where(
                function ($query) use ($pattern): void {
                    $query
                        ->where('name', 'like', $pattern)
                        ->orWhere(
                            'provider',
                            'like',
                            $pattern
                        );
                }
            )
            ->latest('updated_at')
            ->limit(self::RESULT_LIMIT_PER_GROUP)
            ->get()
            ->map(
                static fn (
                    Subscription $subscription
                ): array => [
                    'id' => 'subscription:'
                        .$subscription->id,
                    'kind' => 'subscription',
                    'title' => $subscription->name,
                    'description' => collect([
                        $subscription->provider,
                        $subscription->recurringLabel(),
                    ])->filter()->implode(' • '),
                    'meta' => $subscription->status->label(),
                    'url' => route(
                        'subscriptions.show',
                        $subscription
                    ),
                    'icon' => 'repeat-2',
                ]
            )
            ->all();

        return $this->group(
            'subscriptions',
            'Langganan',
            $items
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function navigationItems(): array
    {
        return [
            [
                'id' => 'navigation:dashboard',
                'kind' => 'navigation',
                'title' => 'Dashboard',
                'description' => 'Ringkasan kondisi Laras',
                'meta' => 'Navigasi',
                'url' => route('dashboard'),
                'icon' => 'layout-dashboard',
                'keywords' => [
                    'beranda',
                    'ringkasan',
                    'utama',
                ],
            ],
            [
                'id' => 'navigation:activities',
                'kind' => 'navigation',
                'title' => 'Aktivitas',
                'description' => 'Kelola tugas, acara, dan deadline',
                'meta' => 'Navigasi',
                'url' => route('activities.index'),
                'icon' => 'list-todo',
                'keywords' => [
                    'tugas',
                    'jadwal',
                    'prioritas',
                ],
            ],
            [
                'id' => 'navigation:accounts',
                'kind' => 'navigation',
                'title' => 'Rekening',
                'description' => 'Kelola sumber dana dan saldo',
                'meta' => 'Navigasi',
                'url' => route('accounts.index'),
                'icon' => 'wallet-cards',
                'keywords' => [
                    'bank',
                    'dompet',
                    'saldo',
                    'keuangan',
                ],
            ],
            [
                'id' => 'navigation:transactions',
                'kind' => 'navigation',
                'title' => 'Transaksi',
                'description' => 'Riwayat pemasukan dan pengeluaran',
                'meta' => 'Navigasi',
                'url' => route('transactions.index'),
                'icon' => 'receipt-text',
                'keywords' => [
                    'pemasukan',
                    'pengeluaran',
                    'transfer',
                    'riwayat',
                ],
            ],
            [
                'id' => 'navigation:budgets',
                'kind' => 'navigation',
                'title' => 'Anggaran',
                'description' => 'Atur batas pengeluaran',
                'meta' => 'Navigasi',
                'url' => route('budgets.index'),
                'icon' => 'piggy-bank',
                'keywords' => [
                    'budget',
                    'batas',
                    'pengeluaran',
                ],
            ],
            [
                'id' => 'navigation:subscriptions',
                'kind' => 'navigation',
                'title' => 'Langganan',
                'description' => 'Kelola pembayaran berulang',
                'meta' => 'Navigasi',
                'url' => route('subscriptions.index'),
                'icon' => 'repeat-2',
                'keywords' => [
                    'subscription',
                    'tagihan',
                    'billing',
                ],
            ],
            [
                'id' => 'navigation:analysis',
                'kind' => 'navigation',
                'title' => 'Analisis',
                'description' => 'Lihat pola dan distribusi pengeluaran',
                'meta' => 'Navigasi',
                'url' => route('analysis.index'),
                'icon' => 'chart-no-axes-combined',
                'keywords' => [
                    'grafik',
                    'insight',
                    'laporan',
                ],
            ],
            [
                'id' => 'navigation:recommendations',
                'kind' => 'navigation',
                'title' => 'Rekomendasi',
                'description' => 'Saran personal berdasarkan datamu',
                'meta' => 'Navigasi',
                'url' => route('recommendations.index'),
                'icon' => 'lightbulb',
                'keywords' => [
                    'saran',
                    'personal',
                    'insight',
                ],
            ],
            [
                'id' => 'navigation:settings',
                'kind' => 'navigation',
                'title' => 'Pengaturan',
                'description' => 'Profil, preferensi, dan keamanan',
                'meta' => 'Navigasi',
                'url' => route('settings.index'),
                'icon' => 'settings',
                'keywords' => [
                    'profil',
                    'preferensi',
                    'keamanan',
                    'privasi',
                ],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{
     *     key: string,
     *     label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function group(
        string $key,
        string $label,
        array $items
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'items' => $items,
        ];
    }

    private function likePattern(
        string $query
    ): string {
        return '%'.str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $query
        ).'%';
    }
}
