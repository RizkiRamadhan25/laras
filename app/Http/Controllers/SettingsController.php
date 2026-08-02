<?php

namespace App\Http\Controllers;

use App\Enums\SecurityEventType;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\UserPreference;
use App\Services\ProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly ProfilePhotoService $photoService
    ) {}

    public function index(): View
    {
        $user = request()
            ->user()
            ->loadMissing('preference');

        $previewTimezone =
            $user->preference?->timezone
            ?? 'Asia/Jakarta';

        $previewNow = now()->timezone(
            $previewTimezone
        );

        $securityEvents = $user
            ->securityEvents()
            ->latest('occurred_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $lastPasswordChangedAt = $user
            ->securityEvents()
            ->where(
                'type',
                SecurityEventType::PasswordChanged
                    ->value
            )
            ->latest('occurred_at')
            ->latest('id')
            ->first()
            ?->occurred_at;

        return view(
            'settings.index',
            [
                'user' => $user,

                'preference' => $user->preference,

                'securityEvents' => $securityEvents,

                'lastPasswordChangedAt' => $lastPasswordChangedAt,

                'timezones' => [
                    'Asia/Jakarta' => [
                        'label' => 'Waktu Indonesia Barat',

                        'short_label' => 'WIB',

                        'description' => 'Jakarta, Sumatra, Jawa, Kalimantan Barat dan Tengah',
                    ],

                    'Asia/Makassar' => [
                        'label' => 'Waktu Indonesia Tengah',

                        'short_label' => 'WITA',

                        'description' => 'Bali, Sulawesi, Nusa Tenggara, Kalimantan Selatan, Timur dan Utara',
                    ],

                    'Asia/Jayapura' => [
                        'label' => 'Waktu Indonesia Timur',

                        'short_label' => 'WIT',

                        'description' => 'Maluku dan Papua',
                    ],
                ],

                'dateFormats' => [
                    'd/m/Y' => $previewNow->format('d/m/Y')
                        .' — Hari/Bulan/Tahun',

                    'd-m-Y' => $previewNow->format('d-m-Y')
                        .' — Hari-Bulan-Tahun',

                    'Y-m-d' => $previewNow->format('Y-m-d')
                        .' — Tahun-Bulan-Hari',
                ],

                'timeFormats' => [
                    'H:i' => $previewNow->format('H:i')
                        .' — Format 24 jam',

                    'h:i A' => $previewNow->format('h:i A')
                        .' — Format 12 jam',
                ],

                'currencies' => [
                    'IDR' => [
                        'name' => 'Rupiah Indonesia',

                        'symbol' => 'Rp',
                    ],

                    'USD' => [
                        'name' => 'Dolar Amerika Serikat',

                        'symbol' => '$',
                    ],

                    'SGD' => [
                        'name' => 'Dolar Singapura',

                        'symbol' => 'S$',
                    ],

                    'MYR' => [
                        'name' => 'Ringgit Malaysia',

                        'symbol' => 'RM',
                    ],

                    'EUR' => [
                        'name' => 'Euro',
                        'symbol' => '€',
                    ],
                ],

                'weekStarts' => [
                    1 => 'Senin',
                    0 => 'Minggu',
                    6 => 'Sabtu',
                ],
            ]
        );
    }

    public function updateProfile(
        UpdateProfileRequest $request
    ): RedirectResponse {
        $user = $request->user();

        $user->forceFill([
            'name' => $request->validated(
                'name'
            ),
        ])->save();

        return redirect(
            route('settings.index')
            .'#profile'
        )
            ->with(
                'status',
                'Profil berhasil diperbarui.'
            );
    }

    public function updatePreferences(
        UpdatePreferencesRequest $request
    ): RedirectResponse {
        $user = $request->user();

        DB::transaction(
            function () use (
                $user,
                $request
            ): void {
                UserPreference::query()
                    ->updateOrCreate(
                        [
                            'user_id' => $user->id,
                        ],
                        array_merge(
                            [
                                'locale' => $user
                                    ->preference
                                    ?->locale
                                    ?? 'id',
                            ],
                            $request->validated()
                        )
                    );
            },
            3
        );

        $user->unsetRelation(
            'preference'
        );

        return redirect(
            route('settings.index')
            .'#preferences'
        )
            ->with(
                'status',
                'Preferensi berhasil diperbarui.'
            );
    }

    public function updatePhoto(
        UpdateProfilePhotoRequest $request
    ): RedirectResponse {
        $this->photoService->replace(
            $request->user(),
            $request->file('photo')
        );

        return redirect(
            route('settings.index')
            .'#profile'
        )
            ->with(
                'status',
                'Foto profil berhasil diperbarui.'
            );
    }

    public function destroyPhoto(
        Request $request
    ): RedirectResponse {
        $this->photoService->delete(
            $request->user()
        );

        return redirect(
            route('settings.index')
            .'#profile'
        )
            ->with(
                'status',
                'Foto profil berhasil dihapus.'
            );
    }
}
