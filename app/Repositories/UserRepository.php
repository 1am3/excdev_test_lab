<?php

namespace App\Repositories;

use App\Models\Operation;
use App\Models\User;
use App\DTO\UserDTO;
use App\DTO\OperationDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findWithBalance(int $id): ?User
    {
        return $this->model->with('balance')->find($id);
    }

    public function findWithOperations(int $id): ?User
    {
        return $this->model->with('operations')->find($id);
    }

    public function createWithBalance(array $attributes): User
    {
        $user = $this->create($attributes);
        $user->balance()->create(['balance' => 0]);
        return $user;
    }

    public function getUsersWithBalance(): Collection
    {
        return $this->model->with('balance')->get();
    }

    public function getUsersWithRecentActivity(int $days = 30): Collection
    {
        return $this->model->with('operations')
            ->whereHas('operations', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->get();
    }

    public function deposit(int $userId, float $amount, string $description = null): Operation
    {
        $user = $this->find($userId);
        if (!$user) {
            throw new \Exception("Пользователь с ID {$userId} не найден");
        }
        return $user->deposit($amount, $description);
    }

    public function withdraw(int $userId, float $amount, string $description = null): Operation
    {
        $user = $this->find($userId);
        if (!$user) {
            throw new \Exception("Пользователь с ID {$userId} не найден");
        }
        return $user->withdraw($amount, $description);
    }

    public function tryWithdraw(int $userId, float $amount, string $description = null): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        return $user->tryWithdraw($amount, $description);
    }

    public function getBalance(int $userId): float
    {
        $user = $this->find($userId);
        if (!$user) {
            throw new \Exception("Пользователь с ID {$userId} не найден");
        }
        return $user->current_balance;
    }

    public function hasEnoughBalance(int $userId, float $amount): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        return $user->hasEnoughBalance($amount);
    }

    public function getOperationsHistory(int $userId, $from = null, $to = null): Collection
    {
        $user = $this->find($userId);
        if (!$user) {
            throw new \Exception("Пользователь с ID {$userId} не найден");
        }
        return $user->getOperationsHistory($from, $to);
    }

    public function transfer(int $fromUserId, int $toUserId, float $amount, string $description = null): array
    {
        $fromUser = $this->find($fromUserId);
        $toUser = $this->find($toUserId);

        if (!$fromUser) {
            throw new \Exception("Отправитель с ID {$fromUserId} не найден");
        }

        if (!$toUser) {
            throw new \Exception("Получатель с ID {$toUserId} не найден");
        }

        if (!$fromUser->hasEnoughBalance($amount)) {
            throw new \Exception('Недостаточно средств для перевода');
        }

        $withdrawOperation = $fromUser->withdraw($amount, $description ?? 'Перевод пользователю #' . $toUserId);
        $depositOperation = $toUser->deposit($amount, $description ?? 'Перевод от пользователя #' . $fromUserId);

        return [$withdrawOperation, $depositOperation];
    }

    // DTO methods
    public function findUserDTO(int $id): ?UserDTO
    {
        $user = $this->find($id);
        return $user ? UserDTO::fromModel($user) : null;
    }

    public function findWithBalanceDTO(int $id): ?UserDTO
    {
        $user = $this->findWithBalance($id);
        return $user ? UserDTO::fromModel($user) : null;
    }

    public function getOperationsHistoryDTO(int $userId, $from = null, $to = null): array
    {
        $operations = $this->getOperationsHistory($userId, $from, $to);
        return $operations->map(fn($operation) => OperationDTO::fromModel($operation))->toArray();
    }

    public function getUsersWithBalanceDTO(): array
    {
        $users = $this->getUsersWithBalance();
        return $users->map(fn($user) => UserDTO::fromModel($user))->toArray();
    }
}
