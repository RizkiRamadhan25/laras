<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProfilePhotoService
{
    public function replace(
        User $user,
        UploadedFile $photo
    ): string {
        $newPath = Image::fromUpload($photo)
            ->orient()
            ->cover(512, 512)
            ->toWebp()
            ->quality(82)
            ->storePubliclyAs(
                path: 'profile-photos/'
                    .$user->id,
                name: Str::uuid().'.webp',
                disk: 'public'
            );

        if ($newPath === false) {
            throw new RuntimeException(
                'Foto profil gagal disimpan.'
            );
        }

        $oldPath = null;

        try {
            DB::transaction(
                function () use (
                    $user,
                    $newPath,
                    &$oldPath
                ): void {
                    $lockedUser = User::query()
                        ->whereKey($user->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $oldPath = $lockedUser
                        ->profile_photo_path;

                    $lockedUser->forceFill([
                        'profile_photo_path' =>
                            $newPath,
                    ])->save();
                },
                3
            );
        } catch (Throwable $exception) {
            Storage::disk('public')
                ->delete($newPath);

            throw $exception;
        }

        $this->deleteOrphanSafely(
            $oldPath,
            $newPath,
            $user->id
        );

        $user->refresh();

        return $newPath;
    }

    public function delete(
        User $user
    ): void {
        $oldPath = null;

        DB::transaction(
            function () use (
                $user,
                &$oldPath
            ): void {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldPath = $lockedUser
                    ->profile_photo_path;

                $lockedUser->forceFill([
                    'profile_photo_path' => null,
                ])->save();
            },
            3
        );

        $this->deleteOrphanSafely(
            $oldPath,
            null,
            $user->id
        );

        $user->refresh();
    }

    private function deleteOrphanSafely(
        ?string $path,
        ?string $currentPath,
        int $userId
    ): void {
        if (
            blank($path)
            || $path === $currentPath
            || ! Storage::disk('public')
                ->exists($path)
        ) {
            return;
        }

        if (
            ! Storage::disk('public')
                ->delete($path)
        ) {
            Log::warning(
                'File foto profil lama gagal dihapus.',
                [
                    'user_id' => $userId,
                    'path' => $path,
                ]
            );
        }
    }
}
