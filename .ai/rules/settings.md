---
paths:
  - 'app/Models/**,app/Http/Controllers/SettingsController.php,routes/web.php,resources/views/settings/**'
---

# Settings

## Pastor-only department nominations
Treat Portail jeunesse and ECODIM as church departments with codes youth and ecodim. Only pasteur_n1 may nominate or remove a department leader; persist leader_user_id, leader_assigned_by, and leader_assigned_at plus an active scoped role assignment. The pastor manages church administration only; department leaders receive source-specific permissions.
