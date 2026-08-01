<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
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
        $ownedNotification =
            $this->ownedNotification(
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

    public function open(
        Request $request,
        string $notification
    ): RedirectResponse {
        $ownedNotification =
            $this->ownedNotification(
                $request->user(),
                $notification
            );

        $ownedNotification->markAsRead();

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

        /*
         * Detail langganan belum memiliki antarmuka.
         * Setelah Langkah 5C, notifikasi pengingat dan gagal
         * dapat diarahkan ke halaman detail langganan.
         */
        return redirect()
            ->route('notifications.index')
            ->with(
                'status',
                'Notifikasi telah ditandai sebagai dibaca.'
            );
    }

    private function ownedNotification(
        User $user,
        string $notificationId
    ): DatabaseNotification {
        return $user->notifications()
            ->findOrFail($notificationId);
    }
}
