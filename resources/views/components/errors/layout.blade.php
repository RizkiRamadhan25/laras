@props([
    'status',
    'title',
    'message',
    'hint' => null,
])

@php
    $requestId = request()
        ->attributes
        ->get('request_id');

    /*
     * Halaman error tidak bergantung pada session atau
     * query autentikasi agar tetap aman ketika layanan
     * pendukung sedang bermasalah.
     */
    $primaryUrl = route('login');
    $primaryLabel = 'Kembali ke halaman masuk';
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>{{ $status }} — {{ $title }} | Laras</title>

    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system,
                BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at top left, #dbeafe 0, transparent 36rem),
                #f8fafc;
            color: #0f172a;
        }

        main {
            width: min(92vw, 44rem);
            padding: 2rem;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.96);
            padding: clamp(1.5rem, 5vw, 3rem);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.10);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: #0f172a;
            font-weight: 700;
        }

        .logo {
            display: grid;
            width: 2.75rem;
            height: 2.75rem;
            place-items: center;
            border-radius: 0.9rem;
            background: #123a73;
            color: #ffffff;
        }

        .status {
            margin-top: 2.5rem;
            color: #2563eb;
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0.75rem 0 0;
            font-size: clamp(2rem, 7vw, 3.25rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .message {
            margin: 1rem 0 0;
            color: #475569;
            font-size: 1rem;
            line-height: 1.75;
        }

        .hint {
            margin: 1rem 0 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.65;
        }

        .reference {
            margin-top: 1.5rem;
            border-radius: 0.9rem;
            background: #f1f5f9;
            padding: 0.9rem 1rem;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.78rem;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .button {
            display: inline-flex;
            min-height: 2.85rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.8rem;
            padding: 0.75rem 1.1rem;
            color: inherit;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
        }

        .button-primary {
            background: #1d4ed8;
            color: #ffffff;
        }

        .button-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .footer {
            margin-top: 1.5rem;
            color: #94a3b8;
            font-size: 0.78rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <main>
        <section class="card">
            <div class="brand">
                <span class="logo">L</span>
                <span>Laras</span>
            </div>

            <p class="status">
                Kesalahan {{ $status }}
            </p>

            <h1>{{ $title }}</h1>

            <p class="message">
                {{ $message }}
            </p>

            @if ($hint)
                <p class="hint">
                    {{ $hint }}
                </p>
            @endif

            @if (is_string($requestId))
                <p class="reference">
                    Kode referensi: {{ $requestId }}
                </p>
            @endif

            <div class="actions">
                <a
                    href="{{ $primaryUrl }}"
                    class="button button-primary"
                >
                    {{ $primaryLabel }}
                </a>

                <a
                    href="{{ url()->current() }}"
                    class="button button-secondary"
                >
                    Muat ulang halaman
                </a>
            </div>
        </section>

        <p class="footer">
            Selaraskan hari, tentukan langkah.
        </p>
    </main>
</body>
</html>
