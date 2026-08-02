<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_common_http_errors_use_laras_error_pages(): void
    {
        $cases = [
            403 => 'Akses tidak diizinkan',
            404 => 'Halaman tidak ditemukan',
            419 => 'Sesi telah berakhir',
            429 => 'Terlalu banyak permintaan',
            503 => 'Laras sedang dalam perawatan',
        ];

        foreach ($cases as $status => $title) {
            Route::middleware('web')
                ->get(
                    '/_test/error-'.$status,
                    static fn () => abort($status)
                );

            $this
                ->get('/_test/error-'.$status)
                ->assertStatus($status)
                ->assertSee($title)
                ->assertSee('Kode referensi:')
                ->assertHeader('X-Request-ID');
        }
    }

    public function test_unexpected_exception_hides_technical_details(): void
    {
        config()->set(
            'app.debug',
            false
        );

        Route::middleware('web')
            ->get(
                '/_test/error-500',
                static function (): never {
                    throw new RuntimeException(
                        'Rahasia internal untuk pengujian.'
                    );
                }
            );

        $this
            ->get('/_test/error-500')
            ->assertStatus(500)
            ->assertSee('Terjadi kesalahan')
            ->assertSee('Kode referensi:')
            ->assertDontSee(
                'Rahasia internal untuk pengujian.'
            )
            ->assertHeader('X-Request-ID');
    }
}
