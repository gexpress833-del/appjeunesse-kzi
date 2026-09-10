<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoleUpdated extends Notification
{
    use Queueable;

    public function __construct(public object $user, public string $role, public ?string $department = null)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $departmentText = filled($this->department) ? ' · Département : '.$this->department : '';

        return [
            'title' => 'Rôle mis à jour',
            'message' => 'Votre rôle a été défini sur '.$this->role.$departmentText.'.',
            'type' => 'role_updated',
            'role' => $this->role,
            'department' => $this->department,
        ];
    }
}
