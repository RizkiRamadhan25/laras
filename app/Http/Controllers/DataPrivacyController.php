<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Services\PersonalDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPrivacyController extends Controller
{
    public function __construct(
        private readonly PersonalDataExportService $exportService
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
        $user = $request->user();

        $userId = $user->getKey();

        $profilePhotoPath =
            $user->profile_photo_path;

        DB::transaction(
            function () use ($userId): void {
                /*
                * Query Builder melakukan DELETE
                * permanen dan melewati SoftDeletes.
                */
                $deletedRows = DB::table('users')
                    ->where('id', $userId)
                    ->delete();

                if ($deletedRows !== 1) {
                    throw new \RuntimeException(
                        'Akun gagal dihapus secara permanen.'
                    );
                }
            },
            3
        );

        if (filled($profilePhotoPath)) {
            Storage::disk('public')->delete(
                $profilePhotoPath
            );
        }

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
