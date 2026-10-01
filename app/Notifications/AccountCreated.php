<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountCreated extends Notification
{
    use Queueable;

    public function __construct(public User $account, public User $createdBy) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, message: string, type: string, account_id: int, created_by: int, click_action: string} */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nouveau compte à valider',
            'message' => $this->createdBy->full_name.' a créé le compte de '.$this->account->full_name.'.',
            'type' => 'account_created',
            'account_id' => $this->account->id,
            'created_by' => $this->createdBy->id,
            'click_action' => $notifiable instanceof User && $notifiable->isAdmin()
                ? '/utilisateurs?status=pending'
                : '/notifications',
        ];
    }
}
