<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-ink antialiased">
    <main class="flex min-h-screen items-center justify-center px-5">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight">{{ config('app.name') }}</h1>
                <p class="mt-2 text-sm text-muted">Catat utang warung dengan rapi.</p>
            </div>

            @error('google')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <a
                href="{{ route('google.redirect') }}"
                class="flex w-full items-center justify-center gap-3 rounded-full bg-primary px-5 py-3.5 text-sm font-bold text-white transition-opacity hover:opacity-90"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" d="M21.35 12.24c0-.72-.06-1.42-.18-2.09H12v3.96h5.24a4.48 4.48 0 0 1-1.94 2.94v2.44h3.14c1.84-1.69 2.91-4.18 2.91-7.25Z"/>
                    <path fill="currentColor" d="M12 21.6c2.63 0 4.83-.87 6.44-2.36l-3.14-2.44c-.87.58-1.98.92-3.3.92-2.54 0-4.69-1.71-5.46-4.02H3.3v2.52A9.74 9.74 0 0 0 12 21.6Z"/>
                    <path fill="currentColor" d="M6.54 13.7A5.86 5.86 0 0 1 6.23 12c0-.59.1-1.16.3-1.7V7.78H3.3A9.74 9.74 0 0 0 2.4 12c0 1.55.37 3.02 1.03 4.22l3.11-2.52Z"/>
                    <path fill="currentColor" d="M12 6.28c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.34 14.63 2.4 12 2.4a9.74 9.74 0 0 0-8.7 5.38l3.24 2.52C7.31 7.99 9.46 6.28 12 6.28Z"/>
                </svg>
                Masuk dengan Google
            </a>
        </div>
    </main>
</body>
</html>
