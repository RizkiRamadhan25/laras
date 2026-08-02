<?php

namespace App\Http\Controllers;

use App\Enums\FinanceFlowType;
use App\Enums\TransactionType;
use App\Http\Requests\CancelTransactionRequest;
use App\Http\Requests\FilterTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Account;
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionPostingService $postingService
    ) {}

    public function index(
        FilterTransactionsRequest $request
    ): View {
        $user = $request->user()->load('preference');
        $filters = $request->validated();

        $query = $user->transactions()
            ->with([
                'entries.account',
                'entries.financeCategory',
            ]);

        $query->when(
            $filters['type'] ?? null,
            fn (Builder $query, string $type): Builder => $query->where('type', $type)
        );

        $query->when(
            $filters['status'] ?? null,
            fn (Builder $query, string $status): Builder => $query->where('status', $status)
        );

        $query->when(
            $filters['account_id'] ?? null,
            fn (Builder $query, int|string $accountId): Builder => $query->whereHas(
                'entries',
                fn (Builder $entryQuery): Builder => $entryQuery->where(
                    'account_id',
                    (int) $accountId
                )
            )
        );

        $query->when(
            $filters['search'] ?? null,
            function (
                Builder $query,
                string $search
            ): Builder {
                $escapedSearch = addcslashes(
                    $search,
                    '%_\\'
                );

                return $query->where(
                    function (Builder $searchQuery) use (
                        $escapedSearch
                    ): void {
                        $pattern = '%'.$escapedSearch.'%';

                        $searchQuery
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
                );
            }
        );

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');

        if (isset($filters['date_from'])) {
            $dateFrom = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $filters['date_from'],
                $timezone
            )
                ->startOfDay()
                ->setTimezone(config('app.timezone'));

            $query->where(
                'occurred_at',
                '>=',
                $dateFrom
            );
        }

        if (isset($filters['date_to'])) {
            $dateTo = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $filters['date_to'],
                $timezone
            )
                ->endOfDay()
                ->setTimezone(config('app.timezone'));

            $query->where(
                'occurred_at',
                '<=',
                $dateTo
            );
        }

        $transactions = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $accounts = Account::withTrashed()
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('transactions.index', [
            'user' => $user,
            'transactions' => $transactions,
            'accounts' => $accounts,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user()->load('preference');

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('accounts.index')
                ->with(
                    'warning',
                    'Tambahkan minimal satu rekening aktif sebelum membuat transaksi.'
                );
        }

        $categories = $user->financeCategories()
            ->where('is_active', true)
            ->get();

        $availableTypes = [
            TransactionType::Income->value,
            TransactionType::Expense->value,
            TransactionType::Transfer->value,
        ];

        $selectedType = in_array(
            $request->query('type'),
            $availableTypes,
            true
        )
            ? $request->query('type')
            : TransactionType::Expense->value;

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');

        return view('transactions.create', [
            'user' => $user,
            'accounts' => $accounts,
            'incomeCategories' => $categories->filter(
                fn ($category): bool => in_array(
                    $category->flow_type,
                    [
                        FinanceFlowType::Income,
                        FinanceFlowType::Both,
                    ],
                    true
                )
            ),
            'expenseCategories' => $categories->filter(
                fn ($category): bool => in_array(
                    $category->flow_type,
                    [
                        FinanceFlowType::Expense,
                        FinanceFlowType::Both,
                    ],
                    true
                )
            ),
            'selectedType' => $selectedType,
            'defaultOccurredAt' => now($timezone)
                ->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(
        StoreTransactionRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $transaction = match ($data['type']) {
                TransactionType::Income->value => $this->postingService->postIncome(
                    user: $request->user(),
                    accountId: (int) $data['account_id'],
                    categoryId: (int) $data['category_id'],
                    amount: $data['amount'],
                    data: $this->transactionMetadata($data)
                ),

                TransactionType::Expense->value => $this->postingService->postExpense(
                    user: $request->user(),
                    accountId: (int) $data['account_id'],
                    categoryId: (int) $data['category_id'],
                    amount: $data['amount'],
                    data: $this->transactionMetadata($data)
                ),

                TransactionType::Transfer->value => $this->postingService->postTransfer(
                    user: $request->user(),
                    sourceAccountId: (int) $data['account_id'],
                    destinationAccountId: (int) $data['destination_account_id'],
                    amount: $data['amount'],
                    adminFee: $data['admin_fee'] ?? '0',
                    data: $this->transactionMetadata($data)
                ),

                default => throw new DomainException(
                    'Jenis transaksi tidak tersedia.'
                ),
            };
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->with(
                    'warning',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'transactions.show',
                $transaction->id
            )
            ->with(
                'status',
                'Transaksi berhasil dicatat.'
            );
    }

    public function show(
        Request $request,
        int $transaction
    ): View {
        $ownedTransaction = $request->user()
            ->transactions()
            ->with([
                'entries.account',
                'entries.financeCategory',
            ])
            ->findOrFail($transaction);

        return view('transactions.show', [
            'user' => $request->user()->load('preference'),
            'transaction' => $ownedTransaction,
        ]);
    }

    public function cancel(
        CancelTransactionRequest $request,
        int $transaction
    ): RedirectResponse {
        try {
            $this->postingService->cancel(
                user: $request->user(),
                transactionId: $transaction,
                reason: $request->validated('reason')
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route(
                    'transactions.show',
                    $transaction
                )
                ->with(
                    'warning',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'transactions.show',
                $transaction
            )
            ->with(
                'status',
                'Transaksi berhasil dibatalkan dan saldo telah dikembalikan.'
            );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function transactionMetadata(array $data): array
    {
        return [
            'occurred_at' => $data['occurred_at'],
            'description' => $data['description'] ?? null,
            'counterparty' => $data['counterparty'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
