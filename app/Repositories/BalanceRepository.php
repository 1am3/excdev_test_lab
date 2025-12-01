<?php

namespace App\Repositories;

use App\Models\Balance;
use App\DTO\BalanceDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BalanceRepository extends BaseRepository
{
    public function __construct(Balance $model)
    {
        parent::__construct($model);
    }

    public function findByUserId(int $userId): ?Balance
    {
        return $this->findBy(['user_id' => $userId]);
    }

    public function firstOrCreateForUser(int $userId): Balance
    {
        return $this->firstOrCreate(['user_id' => $userId], ['balance' => 0]);
    }

    public function getBalancesAbove(float $amount): Collection
    {
        return $this->model->where('balance', '>', $amount)->get();
    }

    public function getBalancesBelow(float $amount): Collection
    {
        return $this->model->where('balance', '<', $amount)->get();
    }

    public function getZeroBalances(): Collection
    {
        return $this->model->where('balance', 0)->get();
    }

    public function increaseBalance(int $userId, float $amount): bool
    {
        $balance = $this->findByUserId($userId);
        return $balance ? $balance->increase($amount) : false;
    }

    public function decreaseBalance(int $userId, float $amount): bool
    {
        $balance = $this->findByUserId($userId);
        return $balance ? $balance->decrease($amount) : false;
    }

    public function updateBalance(int $userId, float $newBalance): bool
    {
        $balance = $this->findByUserId($userId);
        return $balance ? $balance->updateBalance($newBalance) : false;
    }

    public function getBalanceValue(int $userId): float
    {
        $balance = $this->findByUserId($userId);
        return $balance ? $balance->balance : 0;
    }

    public function hasEnoughBalance(int $userId, float $amount): bool
    {
        $balance = $this->findByUserId($userId);
        return $balance ? $balance->hasEnough($amount) : false;
    }

    public function addToAllUsers(float $amount): int
    {
        return $this->model->increment('balance', $amount);
    }

    public function resetAllBalances(): int
    {
        return $this->model->update(['balance' => 0]);
    }

    public function getTotalBalanceSum(): float
    {
        return $this->model->sum('balance');
    }

    public function createMissingForAllUsers(): int
    {
        $usersWithoutBalance = DB::table('users')
            ->leftJoin('balance', 'users.id', '=', 'balance.user_id')
            ->whereNull('balance.user_id')
            ->select('users.id')
            ->get();

        $count = 0;
        foreach ($usersWithoutBalance as $user) {
            $this->create(['user_id' => $user->id, 'balance' => 0]);
            $count++;
        }

        return $count;
    }

    // DTO methods
    public function getBalanceDTO(int $userId): ?BalanceDTO
    {
        $balance = $this->findByUserId($userId);
        return $balance ? BalanceDTO::fromModel($balance) : null;
    }

    public function getBalanceValueDTO(int $userId): BalanceDTO
    {
        $balance = $this->findByUserId($userId);
        if (!$balance) {
            $balance = $this->firstOrCreateForUser($userId);
        }
        return BalanceDTO::fromModel($balance);
    }
}
