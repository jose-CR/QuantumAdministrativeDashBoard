<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Alert extends Model
{
    public const TYPE_UPCOMING_PAYMENT = 'upcoming_payment';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_FOLLOW_UP = 'follow_up';
    public const TYPE_CUSTOM = 'custom';
    public const STATUS_SENT = 'sent';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const TYPE_OVERDUE = 'overdue';

    protected $fillable = [
        'client_id',
        'credit_id',
        'installment_id',
        'user_id',
        'assigned_user_id',
        'type',
        'title',
        'message',
        'alert_at',
        'status',
        'shown_at',
        'sent_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alert_at' => 'datetime',
            'shown_at' => 'datetime',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
    
    public static function getTypes(): array{
        return [
            self::TYPE_UPCOMING_PAYMENT => 'Próximo pago',
            self::TYPE_REMINDER         => 'Recordatorio',
            self::TYPE_FOLLOW_UP        => 'Seguimiento',
            self::TYPE_CUSTOM           => 'Personalizada',
            self::TYPE_OVERDUE          => 'Vencida',
        ];
    }

    public static function getManualTypes(): array
    {
        return [
            self::TYPE_REMINDER => 'Recordatorio',
            self::TYPE_FOLLOW_UP => 'Seguimiento',
            self::TYPE_CUSTOM => 'Personalizada',
        ];
    }

    public static function getTypeLabel(string $type): string
    {
        return self::getTypes()[$type] ?? $type;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id', 'id');
    }

    public function assignedAlerts()
    {
        return $this->hasMany(Alert::class, 'assigned_user_id');
    }


    public function createdAlerts()
    {
        return $this->hasMany(Alert::class, 'user_id');
    }

    
}
