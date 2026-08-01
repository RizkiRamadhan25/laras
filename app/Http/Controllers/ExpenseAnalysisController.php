<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseAnalysisRequest;
use App\Services\ExpenseAnalysisService;
use Illuminate\View\View;

class ExpenseAnalysisController extends Controller
{
    public function __construct(
        private readonly ExpenseAnalysisService $expenseAnalysisService
    ) {
    }

    public function index(
        ExpenseAnalysisRequest $request
    ): View {
        $validated = $request->validated();

        $period = $validated['period']
            ?? 'month';

        $user = $request->user()->load(
            'preference'
        );

        $analysis =
            $this->expenseAnalysisService
                ->build(
                    user: $user,
                    selectedPeriod: $period
                );

        return view(
            'analysis.expenses',
            [
                'user' => $user,
                'analysis' => $analysis,
            ]
        );
    }
}
