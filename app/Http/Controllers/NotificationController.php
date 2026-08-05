<?php

namespace App\Http\Controllers;

use App\Enums\DataDeletionScope;
use App\Http\Requests\PurgeNotificationsRequest;
use App\Services\DataDeletionPreviewService;
use App\Services\DataDeletionService;
use App\Services\OwnedResourceGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly DataDeletionPreviewService $deletionPreview,
        private readonly DataDeletionService $deletion,
        private readonly OwnedResourceGuard $ownership
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = in_array(
            $request->query('filter'),
            [
                'all',
                'unread',
                'read',
            ],
            true
        )
            ? $request->query('filter')
            : 'all';

        $query = $user->notifications()
            ->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        if ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'user' => $user,
            'notifications' => $notifications,
            'selectedFilter' => $filter,

            'summary' => [
                'all' => $user->notifications()
                    ->count(),

                'unread' => $user
                    ->unreadNotifications()
                    ->count(),

                'read' => $user
                    ->readNotifications()
                    ->count(),
            ],
        ]);
    }

    public function markRead(
        Request $request,
        string $notification
    ): RedirectResponse {
        $ownedNotification = $this->ownership
            ->notification(
                $request->user(),
                $notification
            );

        $ownedNotification->markAsRead();

        return back()->with(
            'status',
            'Notifikasi ditandai sebagai sudah dibaca.'
        );
    }

    public function markAllRead(
        Request $request
    ): RedirectResponse {
        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return back()->with(
            'status',
            'Semua notifikasi telah ditandai sebagai dibaca.'
        );
    }

    public function deletionPreview(
        PurgeNotificationsRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $preview = $this->deletionPreview
            ->notifications(
                user: $request->user(),
                scope: DataDeletionScope::from(
                    $validated['scope']
                ),
                notificationIds: $validated[
                    'notification_ids'
                ] ?? [],
                olderThanDays: $validated[
                    'older_than_days'
                ] ?? null
            );

        return response()->json([
            'data' => $preview,
        ]);
    }

    public function purge(
        PurgeNotificationsRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        $deletedCount = $this->deletion
            ->deleteNotifications(
                user: $request->user(),
                scope: DataDeletionScope::from(
                    $validated['scope']
                ),
                notificationIds: $validated[
                    'notification_ids'
                ] ?? [],
                olderThanDays: $validated[
                    'older_than_days'
                ] ?? null
            );

        return back()->with(
            'status',
            $deletedCount === 0
                ? 'Tidak ada notifikasi yang dihapus.'
                : $deletedCount.' notifikasi berhasil dihapus.'
        );
    }

    public function destroy(
        Request $request,
        string $notification
    ): RedirectResponse {
        $deletedCount = $this->deletion
            ->deleteNotifications(
                user: $request->user(),
                scope: DataDeletionScope::Selected,
                notificationIds: [
                    $notification,
                ]
            );

        return back()->with(
            'status',
            $deletedCount === 1
                ? 'Notifikasi berhasil dihapus.'
                : 'Notifikasi tidak ditemukan.'
        );
    }

    public function open(
        Request $request,
        string $notification
    ): RedirectResponse {
        $ownedNotification = $this->ownership
            ->notification(
                $request->user(),
                $notification
            );

        $ownedNotification->markAsRead();

        $budgetId = $ownedNotification
            ->data['budget_id']
                ?? null;

        $budgetPeriodId = $ownedNotification
            ->data['budget_period_id']
                ?? null;

        if ($budgetId !== null) {
            $budget = $request->user()
                ->budgets()
                ->find((int) $budgetId);

            if ($budget !== null) {
                $parameters = [
                    'budget' => $budget->id,
                ];

                if ($budgetPeriodId !== null) {
                    $periodExists = $budget
                        ->periods()
                        ->whereKey(
                            (int) $budgetPeriodId
                        )
                        ->exists();

                    if ($periodExists) {
                        $parameters['period'] =
                            (int) $budgetPeriodId;
                    }
                }

                return redirect()->route(
                    'budgets.show',
                    $parameters
                );
            }
        }

        $transactionId = $ownedNotification
            ->data['transaction_id']
                ?? null;

        if ($transactionId !== null) {
            $transaction = $request->user()
                ->transactions()
                ->find((int) $transactionId);

            if ($transaction !== null) {
                return redirect()->route(
                    'transactions.show',
                    $transaction->id
                );
            }
        }

        $subscriptionId = $ownedNotification
            ->data['subscription_id']
                ?? null;

        $billingId = $ownedNotification
            ->data['subscription_billing_id']
                ?? null;

        if (
            $subscriptionId !== null
            && $billingId !== null
        ) {
            $subscription = $request->user()
                ->subscriptions()
                ->find((int) $subscriptionId);

            if ($subscription !== null) {
                $billing = $subscription
                    ->billings()
                    ->find((int) $billingId);

                if ($billing !== null) {
                    return redirect()->route(
                        'subscriptions.billings.show',
                        [
                            'subscription' => $subscription->id,

                            'billing' => $billing->id,
                        ]
                    );
                }
            }
        }

        if ($subscriptionId !== null) {
            $subscription = $request->user()
                ->subscriptions()
                ->find((int) $subscriptionId);

            if ($subscription !== null) {
                return redirect()->route(
                    'subscriptions.show',
                    $subscription->id
                );
            }
        }

        return redirect()
            ->route('notifications.index')
            ->with(
                'status',
                'Notifikasi telah ditandai sebagai dibaca.'
            );
    }
}
