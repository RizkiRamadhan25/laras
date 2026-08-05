<?php

namespace App\Http\Controllers;

use App\Enums\DataDeletionScope;
use App\Enums\RecommendationInteractionType;
use App\Http\Requests\PurgeRecommendationInteractionsRequest;
use App\Http\Requests\RecommendationFeedbackRequest;
use App\Models\User;
use App\Services\DataDeletionPreviewService;
use App\Services\DataDeletionService;
use App\Services\PersonalRecommendationService;
use App\Services\RecommendationInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly PersonalRecommendationService $recommendationService,
        private readonly RecommendationInteractionService $interactionService,
        private readonly DataDeletionPreviewService $deletionPreview,
        private readonly DataDeletionService $deletion
    ) {}

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

                'recommendations' => $this
                    ->recommendationService
                    ->build($user),
            ]
        );
    }

    public function open(
        Request $request,
        string $recommendation
    ): RedirectResponse {
        $user = $request->user();

        $item = $this->currentItem(
            $user,
            $recommendation
        );

        $this->interactionService
            ->record(
                user: $user,
                item: $item,

                type: RecommendationInteractionType::Opened
            );

        return redirect()->to(
            $item['action_url']
        );
    }

    public function feedback(
        RecommendationFeedbackRequest $request,
        string $recommendation
    ): RedirectResponse {
        $user = $request->user();

        $item = $this->currentItem(
            $user,
            $recommendation
        );

        $type =
            RecommendationInteractionType::from(
                $request->validated(
                    'interaction_type'
                )
            );

        $this->interactionService
            ->record(
                user: $user,
                item: $item,
                type: $type
            );

        $message = match ($type) {
            RecommendationInteractionType::FollowedUp => 'Rekomendasi ditandai sebagai sudah ditindaklanjuti.',

            RecommendationInteractionType::Dismissed => 'Rekomendasi akan disembunyikan selama 24 jam.',

            RecommendationInteractionType::Irrelevant => 'Rekomendasi ditandai tidak relevan.',

            RecommendationInteractionType::Opened => 'Rekomendasi telah dibuka.',
        };

        return redirect()
            ->route(
                'recommendations.index'
            )
            ->with(
                'status',
                $message
            );
    }

    public function history(
        Request $request
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        $interactions = $user
            ->recommendationInteractions()
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'recommendations.history',
            [
                'user' => $user,

                'interactions' => $interactions,
            ]
        );
    }

    public function historyDeletionPreview(
        PurgeRecommendationInteractionsRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $preview = $this->deletionPreview
            ->recommendationInteractions(
                user: $request->user(),
                scope: DataDeletionScope::from(
                    $validated['scope']
                ),
                interactionIds: $validated[
                    'interaction_ids'
                ] ?? [],
                olderThanDays: $validated[
                    'older_than_days'
                ] ?? null
            );

        return response()->json([
            'data' => $preview,
        ]);
    }

    public function purgeHistory(
        PurgeRecommendationInteractionsRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        $deletedCount = $this->deletion
            ->deleteRecommendationInteractions(
                user: $request->user(),
                scope: DataDeletionScope::from(
                    $validated['scope']
                ),
                interactionIds: $validated[
                    'interaction_ids'
                ] ?? [],
                olderThanDays: $validated[
                    'older_than_days'
                ] ?? null
            );

        return back()->with(
            'status',
            $deletedCount === 0
                ? 'Tidak ada riwayat rekomendasi yang dihapus.'
                : $deletedCount.' riwayat rekomendasi berhasil dihapus.'
        );
    }

    public function destroyHistory(
        Request $request,
        int $interaction
    ): RedirectResponse {
        $deletedCount = $this->deletion
            ->deleteRecommendationInteractions(
                user: $request->user(),
                scope: DataDeletionScope::Selected,
                interactionIds: [
                    $interaction,
                ]
            );

        return back()->with(
            'status',
            $deletedCount === 1
                ? 'Riwayat rekomendasi berhasil dihapus.'
                : 'Riwayat rekomendasi tidak ditemukan.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function currentItem(
        User $user,
        string $recommendationKey
    ): array {
        $item = $this
            ->recommendationService
            ->build($user)['items']
            ->firstWhere(
                'key',
                $recommendationKey
            );

        abort_if(
            $item === null,
            404
        );

        return $item;
    }
}
