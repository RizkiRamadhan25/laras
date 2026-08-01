<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\MoveAccountRequest;
use App\Http\Requests\SaveAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user()->load('preference');

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->get();

        $archivedAccounts = $user->accounts()
            ->onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get();

        $totalBalance = $accounts->reduce(
            static fn (
                string $total,
                Account $account
            ): string => bcadd(
                $total,
                $account->cached_balance,
                2
            ),
            '0.00'
        );

        return view('accounts.index', [
            'user' => $user,
            'accounts' => $accounts,
            'archivedAccounts' => $archivedAccounts,
            'totalBalance' => $totalBalance,
        ]);
    }

    public function create(Request $request): View
    {
        return view('accounts.create', [
            'user' => $request->user()->load('preference'),
            'accountTypes' => AccountType::cases(),
        ]);
    }

    public function store(
        SaveAccountRequest $request
    ): RedirectResponse {
        $this->accountService->create(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('accounts.index')
            ->with('status', 'Rekening berhasil ditambahkan.');
    }

    public function edit(
        Request $request,
        int $account
    ): View {
        return view('accounts.edit', [
            'user' => $request->user()->load('preference'),
            'account' => $this->ownedAccount(
                $request,
                $account
            ),
            'accountTypes' => AccountType::cases(),
        ]);
    }

    public function update(
        SaveAccountRequest $request,
        int $account
    ): RedirectResponse {
        $this->accountService->update(
            $request->user(),
            $account,
            $request->validated()
        );

        return redirect()
            ->route('accounts.index')
            ->with('status', 'Rekening berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        int $account
    ): RedirectResponse {
        try {
            $this->accountService->archive(
                $request->user(),
                $account
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('accounts.index')
                ->with('warning', $exception->getMessage());
        }

        return redirect()
            ->route('accounts.index')
            ->with('status', 'Rekening berhasil diarsipkan.');
    }

    public function restore(
        Request $request,
        int $account
    ): RedirectResponse {
        $this->accountService->restore(
            $request->user(),
            $account
        );

        return redirect()
            ->route('accounts.index')
            ->with('status', 'Rekening berhasil diaktifkan kembali.');
    }

    public function move(
        MoveAccountRequest $request,
        int $account
    ): RedirectResponse {
        $this->accountService->move(
            $request->user(),
            $account,
            $request->validated('direction')
        );

        return redirect()->route('accounts.index');
    }

    private function ownedAccount(
        Request $request,
        int $accountId
    ): Account {
        return $request->user()
            ->accounts()
            ->whereKey($accountId)
            ->firstOrFail();
    }
}
