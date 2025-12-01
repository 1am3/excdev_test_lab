<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    use HasFactory;


    protected $table = 'balance';

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updateBalance(float $newBalance): bool
    {
        return $this->update(['balance' => $newBalance]);
    }

    public function increase(float $amount): bool
    {
        return $this->increment('balance', $amount);
    }

    public function decrease(float $amount): bool
    {
        return $this->decrement('balance', $amount);
    }

    public function hasEnough(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
