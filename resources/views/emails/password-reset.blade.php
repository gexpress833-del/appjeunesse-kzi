<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation du mot de passe</title>
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
                <p style="color:#0f766e;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Sécurité du compte</p>
                <h2 style="color:#0f172a;font-size:24px;margin:12px 0;">Réinitialiser votre mot de passe</h2>
                <p style="color:#475569;font-size:16px;line-height:1.6;">Bonjour {{ $user->full_name }}, une demande de réinitialisation a été effectuée pour votre compte.</p>
                <p style="color:#475569;font-size:16px;line-height:1.6;">Ce lien est valable pendant 60 minutes. Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.</p>
                <p style="margin-top:28px;"><a href="{{ $resetUrl }}" style="display:inline-block;background:#0f766e;border-radius:10px;color:#ffffff;font-weight:bold;padding:13px 20px;text-decoration:none;">Créer un nouveau mot de passe</a></p>
            </td>
        </tr>
        <tr>
            <td style="border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;padding:18px 32px;">La Parole Eternelle — Kolwezi · appjeunesse-kzi</td>
        </tr>
    </table>
</body>
</html>
