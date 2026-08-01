<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\PersonalRecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardRecommendationComposer
{
    public function __construct(
        private readonly PersonalRecommendationService $recommendationService
    ) {
    }

    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $view->with(
                'dashboardRecommendations',
                [
                    'items' => collect(),
                    'summary' => [
                        'total' => 0,
                        'critical' => 0,
                        'attention' => 0,
                        'insight' => 0,
                    ],
                    'generated_at' => null,
                    'has_more' => false,
                ]
            );

            return;
        }

        $recommendations =
            $this->recommendationService
                ->build($user);

        $items = $recommendations['items'];

        $view->with(
            'dashboardRecommendations',
            [
                /*
                 * Dashboard hanya menampilkan tiga
                 * rekomendasi teratas.
                 */
                'items' => $items
                    ->take(3)
                    ->values(),

                'summary' =>
                    $recommendations['summary'],

                'generated_at' =>
                    $recommendations[
                        'generated_at'
                    ],

                'has_more' =>
                    $items->count() > 3,
            ]
        );
    }
}
