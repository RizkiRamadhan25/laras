<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('preference');

        $accounts = $user->accounts()
            ->where('is_active', true)
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

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');

        $locale = $user->preference?->locale
            ?? config('laras.defaults.locale');

        $currentTime = now($timezone)->locale($locale);

        $hour = (int) $currentTime->format('G');

        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return view('dashboard.index', [
            'user' => $user,
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'greeting' => $greeting,
            'currentDate' => $currentTime
                ->translatedFormat('l, d F Y'),
        ]);
    }
}
