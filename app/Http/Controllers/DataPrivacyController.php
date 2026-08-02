<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Services\AccountDeletionService;
use App\Services\PersonalDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPrivacyController extends Controller
{
    public function __construct(
        private readonly PersonalDataExportService $exportService,
        private readonly AccountDeletionService $deletionService
    ) {
    }

    public function export(
        Request $request
    ): StreamedResponse {
        $user = $request
            ->user()
            ->loadMissing(
                'preference'
            );

        $archive = $this
            ->exportService
            ->build($user);

        $filename = sprintf(
            'laras-data-%s.json',
            now()
                ->setTimezone(
                    $user->preference
                        ?->timezone
                    ?? config(
                        'app.timezone',
                        'Asia/Jakarta'
                    )
                )
                ->format(
                    'Ymd-His'
                )
        );

        return response()->streamDownload(
            function () use (
                $archive
            ): void {
                echo json_encode(
                    $archive,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                );
            },
            $filename,
            [
                'Content-Type' =>
                    'application/json; charset=UTF-8',

                'Cache-Control' =>
                    'no-store, private',

                'X-Content-Type-Options' =>
                    'nosniff',
            ]
        );
    }

    public function destroy(
        DeleteAccountRequest $request
    ): RedirectResponse {
        $this->deletionService
            ->delete(
                $request->user()
            );

        Auth::logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Akun dan seluruh data Laras berhasil dihapus.'
            );
    }
}
