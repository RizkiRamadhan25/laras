<?php

namespace App\Http\Controllers;

use App\Services\DashboardAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics
    ) {
    }

    public function index(Request $request): View
    {
        return view(
            'dashboard.index',
            $this->analytics->build(
                $request->user()
            )
        );
    }
}
