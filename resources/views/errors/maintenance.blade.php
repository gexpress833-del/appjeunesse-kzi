<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-center text-white">
    <main class="max-w-lg rounded-3xl border border-cyan-400/20 bg-white/[0.05] p-8 shadow-2xl shadow-cyan-950/30">
        <p class="text-4xl">⚙️</p>
        <h1 class="mt-5 text-2xl font-bold">Application temporairement indisponible</h1>
        <p class="mt-3 text-slate-400">Une mise à jour est en cours. Merci de revenir dans quelques instants.</p>
    </main>
</body>
</html>