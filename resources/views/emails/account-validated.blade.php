<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compte validé</title>
</head>
<body style="margin:0;background:#f1f5f9;color:#172033;font-family:Arial,sans-serif;padding:32px 16px;">
    <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;">
        <tr>
            <td style="background:#07111f;padding:28px;text-align:center;">
                <img src="{{ config('app.url') }}/logoEglise.jpg" width="64" height="64" alt="La Parole Eternelle" style="border:2px solid #2dd4bf;border-radius:18px;object-fit:cover;">
                <h1 style="color:#ffffff;font-size:22px;margin:16px 0 0;">appjeunesse-kzi</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="color:#0f766e;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Compte validé</p>
                <h2 style="color:#0f172a;font-size:24px;margin:12px 0;">Bienvenue {{ $user->full_name }}</h2>
                <p style="color:#475569;font-size:16px;line-height:1.6;">Votre compte a été validé par l’administrateur. Vous pouvez maintenant accéder à votre espace membre et profiter des fonctionnalités de la plateforme.</p>
                <p style="margin-top:28px;"><a href="{{ route('login') }}" style="display:inline-block;background:#0f766e;border-radius:10px;color:#ffffff;font-weight:bold;padding:13px 20px;text-decoration:none;">Accéder à mon espace</a></p>
            </td>
        </tr>
        <tr>
            <td style="border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;padding:18px 32px;">La Parole Eternelle — Kolwezi · appjeunesse-kzi</td>
        </tr>
    </table>
</body>
</html>
