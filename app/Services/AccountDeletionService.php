<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AccountDeletionService
{
    public function delete(
        User $user
    ): void {
        $userId = (int) $user->getKey();
        $email = (string) $user->email;
        $notifiableType = $user
            ->getMorphClass();
        $profilePhotoPath =
            $user->profile_photo_path;

        DB::transaction(
            function () use (
                $userId,
                $email,
                $notifiableType,
                $profilePhotoPath
            ): void {
                DB::table('sessions')
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->delete();

                DB::table('notifications')
                    ->where(
                        'notifiable_type',
                        $notifiableType
                    )
                    ->where(
                        'notifiable_id',
                        $userId
                    )
                    ->delete();

                DB::table(
                    'password_reset_tokens'
                )
                    ->where(
                        'email',
                        $email
                    )
                    ->delete();

                $deletedRows = DB::table(
                    'users'
                )
                    ->where(
                        'id',
                        $userId
                    )
                    ->delete();

                if ($deletedRows !== 1) {
                    throw new RuntimeException(
                        'Akun gagal dihapus secara permanen.'
                    );
                }

                if (
                    filled($profilePhotoPath)
                    && Storage::disk('public')
                        ->exists($profilePhotoPath)
                    && ! Storage::disk('public')
                        ->delete($profilePhotoPath)
                ) {
                    throw new RuntimeException(
                        'Foto profil gagal dihapus.'
                    );
                }
            },
            3
        );
    }
}
