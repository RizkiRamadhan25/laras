<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __invoke(
        GlobalSearchRequest $request,
        GlobalSearchService $searchService
    ): JsonResponse {
        return response()->json(
            $searchService->search(
                $request->user(),
                $request->string('q')->toString()
            )
        );
    }
}
