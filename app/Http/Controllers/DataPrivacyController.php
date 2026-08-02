<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ExportPersonalDataRequest;
use App\Services\AccountDeletionService;
use App\Services\PersonalDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataPrivacyController extends Controller
{
    public function __construct(
        private readonly PersonalDataExportService $exportService,
        private readonly AccountDeletionService $deletionService
    ) {}

    public function export(
        ExportPersonalDataRequest $request
    ): BinaryFileResponse {
        $archive = $this
            ->exportService
            ->createArchive(
                $request->user()
            );

        return response()
            ->download(
                $archive['path'],
                $archive['filename'],
                [
                    'Content-Type' => 'application/zip',

                    'Cache-Control' => 'no-store, private, max-age=0',

                    'Pragma' => 'no-cache',

                    'Expires' => '0',

                    'X-Content-Type-Options' => 'nosniff',

                    'X-Robots-Tag' => 'noindex, nofollow, noarchive',
                ]
            )
            ->deleteFileAfterSend(true);
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
