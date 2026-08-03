<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\UUID;

class NotificationOutbox extends Model
{
    use UUID;

    protected $table = 'notification_outbox';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_type',
        'file_name',
        'total_trn',
        'total_amount',
        'actor_name',
        'recipient_group',
        'status',
        'sms_payload',
        'email_payload',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'total_trn'    => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::orderedUuid();
            }
        });
    }
}
