<?php

namespace App\Http\Controllers;

use App\Services\PersonalRecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly PersonalRecommendationService $recommendationService
    ) {
    }

    public function index(
        Request $request
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        return view(
            'recommendations.index',
            [
                'user' => $user,

                'recommendations' =>
                    $this
                        ->recommendationService
                        ->build($user),
            ]
        );
    }
}
