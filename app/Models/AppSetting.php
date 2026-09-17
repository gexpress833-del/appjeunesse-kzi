<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'church_name',
    'application_name',
    'timezone',
    'logo_url',
    'phone',
    'email',
    'address',
    'attendance_statuses',
    'attendance_editable_hours',
    'notification_settings',
    'communication_settings',
    'maintenance_mode',
])]
class AppSetting extends Model
{
    protected function casts(): array
    {
        return [
            'attendance_statuses' => 'array',
            'attendance_editable_hours' => 'integer',
            'notification_settings' => 'array',
            'communication_settings' => 'array',
            'maintenance_mode' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'church_name' => 'La Parole Éternelle Kolwezi',
            'application_name' => 'appjeunesse-kzi',
            'timezone' => 'Africa/Lubumbashi',
            'attendance_statuses' => ['present', 'absent', 'late', 'excused'],
            'attendance_editable_hours' => 48,
            'notification_settings' => [
                'new_registration' => true,
                'event_created' => true,
                'attendance_recorded' => true,
                'social_visit' => true,
                'video_comment' => true,
            ],
            'communication_settings' => [
                'carousel_enabled' => true,
                'events_enabled' => true,
                'video_comments_enabled' => true,
                'video_likes_enabled' => true,
            ],
        ]);
    }
}
