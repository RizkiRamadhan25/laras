<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionBillingStatus;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Services\SubscriptionBillingProcessorService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionBillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionBillingProcessorService $billingProcessor
    ) {
    }

    public function show(
        Request $request,
        int $subscription,
        int $billing
    ): View {
        $ownedSubscription = Subscription::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->with([
                'account',
                'financeCategory',
            ])
            ->findOrFail($subscription);

        $ownedBilling = SubscriptionBilling::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'subscription_id',
                $ownedSubscription->id
            )
            ->with([
                'transaction',
            ])
            ->findOrFail($billing);

        return view(
            'subscriptions.billings.show',
            [
                'user' => $request->user()
                    ->load('preference'),

                'subscription' =>
                    $ownedSubscription,

                'billing' =>
                    $ownedBilling,
            ]
        );
    }

    public function retry(
        Request $request,
        int $subscription,
        int $billing
    ): RedirectResponse {
        try {
            $processed =
                $this->billingProcessor->retry(
                    user: $request->user(),

                    subscriptionId:
                        $subscription,

                    billingId:
                        $billing
                );
        } catch (DomainException $exception) {
            return back()->with(
                'warning',
                $exception->getMessage()
            );
        }

        if (
            $processed->status
            === SubscriptionBillingStatus::Posted
        ) {
            return redirect()
                ->route(
                    'subscriptions.billings.show',
                    [
                        'subscription' =>
                            $subscription,

                        'billing' =>
                            $processed->id,
                    ]
                )
                ->with(
                    'status',
                    'Tagihan berhasil dicatat sebagai transaksi pengeluaran.'
                );
        }

        if (
            $processed->status
            === SubscriptionBillingStatus::Failed
        ) {
            return redirect()
                ->route(
                    'subscriptions.billings.show',
                    [
                        'subscription' =>
                            $subscription,

                        'billing' =>
                            $processed->id,
                    ]
                )
                ->with(
                    'warning',
                    $processed->failure_reason
                        ?? 'Tagihan masih gagal diproses.'
                );
        }

        return redirect()
            ->route(
                'subscriptions.billings.show',
                [
                    'subscription' =>
                        $subscription,

                    'billing' =>
                        $processed->id,
                ]
            )
            ->with(
                'warning',
                'Tagihan belum dapat diproses.'
            );
    }
}
