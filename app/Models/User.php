<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function balance(): HasOne
    {
        return $this->hasOne(Balance::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Operation::class)->where('type', Operation::TYPE_DEPOSIT);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Operation::class)->where('type', Operation::TYPE_WITHDRAWAL);
    }

    public function completedOperations(): HasMany
    {
        return $this->hasMany(Operation::class)->where('status', Operation::STATUS_COMPLETED);
    }

    private function getBalanceFromDatabase(): float
    {
        $balance = $this->balance()->first();
        return $balance ? $balance->balance : 0;
    }

    public function getCurrentBalanceAttribute(): float
    {
        // Если запись в balance не существует, инициализируем её суммой из операций
        if (!$this->balance) {
            $calculatedBalance = $this->completedOperations()
                ->selectRaw('SUM(CASE WHEN type = "deposit" THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0;

            $this->balance()->create(['balance' => $calculatedBalance]);

            // Получаем свежий баланс из БД
            $balance = $this->balance()->first();
            return $balance ? $balance->balance : 0;
        }

        return $this->balance->balance;
    }

    public function getTotalDepositsAttribute(): float
    {
        return $this->operations()
            ->deposits()
            ->completed()
            ->sum('amount');
    }

    public function getTotalWithdrawalsAttribute(): float
    {
        return $this->operations()
            ->withdrawals()
            ->completed()
            ->sum('amount');
    }

    public function hasEnoughBalance(float $amount): bool
    {
        return $this->current_balance >= $amount;
    }

    public function deposit(float $amount, string $description = null): Operation
    {
        // Получаем текущий баланс напрямую из базы данных или создаем нулевой
        $currentBalance = $this->getBalanceFromDatabase();

        $operation = $this->operations()->create([
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance + $amount,
            'status' => Operation::STATUS_COMPLETED,
            'description' => $description,
        ]);

        // Обновляем баланс в таблице balance
        $balanceRecord = $this->balance ?: $this->balance()->create(['balance' => 0]);
        $balanceRecord->increment('balance', $amount);

        return $operation;
    }

    public function withdraw(float $amount, string $description = null): Operation
    {
        // Получаем текущий баланс напрямую из базы данных
        $currentBalance = $this->getBalanceFromDatabase();

        if ($currentBalance < $amount) {
            throw new \Exception('Недостаточно средств на балансе');
        }

        $operation = $this->operations()->create([
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance - $amount,
            'status' => Operation::STATUS_COMPLETED,
            'description' => $description,
        ]);

        // Обновляем баланс в таблице balance (уменьшаем)
        $balanceRecord = $this->balance ?: $this->balance()->create(['balance' => 0]);
        $balanceRecord->decrement('balance', $amount);

        return $operation;
    }

    public function tryWithdraw(float $amount, string $description = null): bool
    {
        try {
            $this->withdraw($amount, $description);
            return true;
        } catch (\Exception $e) {
            $this->operations()->create([
                'type' => Operation::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'balance_before' => $this->current_balance,
                'balance_after' => $this->current_balance,
                'status' => Operation::STATUS_FAILED,
                'description' => $description,
            ]);
            return false;
        }
    }

    public function getOperationsHistory($from = null, $to = null)
    {
        $query = $this->operations()->with(['user']);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
