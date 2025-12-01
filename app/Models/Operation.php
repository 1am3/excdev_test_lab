<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operation extends Model
{
    use HasFactory;

    protected $table = 'operations';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'description',
    ];


    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDeposits($query)
    {
        return $query->where('type', self::TYPE_DEPOSIT);
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', self::TYPE_WITHDRAWAL);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isDeposit(): bool
    {
        return $this->type === self::TYPE_DEPOSIT;
    }

    public function isWithdrawal(): bool
    {
        return $this->type === self::TYPE_WITHDRAWAL;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
