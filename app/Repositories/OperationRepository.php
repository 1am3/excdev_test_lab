<?php

namespace App\Repositories;

use App\Models\Operation;
use App\DTO\OperationDTO;
use Illuminate\Database\Eloquent\Collection;

class OperationRepository extends BaseRepository
{
    public function __construct(Operation $model)
    {
        parent::__construct($model);
    }

    public function findByUserId(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getDeposits(int $userId = null): Collection
    {
        $query = $this->model->deposits();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    public function getWithdrawals(int $userId = null): Collection
    {
        $query = $this->model->withdrawals();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    public function getCompletedOperations(int $userId = null): Collection
    {
        $query = $this->model->completed();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    public function getPendingOperations(): Collection
    {
        return $this->model->where('status', Operation::STATUS_PENDING)->get();
    }

    public function getFailedOperations(): Collection
    {
        return $this->model->where('status', Operation::STATUS_FAILED)->get();
    }

    public function getOperationsByDateRange($from, $to, int $userId = null): Collection
    {
        $query = $this->model->whereBetween('created_at', [$from, $to]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getOperationsByStatus(string $status, int $userId = null): Collection
    {
        $query = $this->model->where('status', $status);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function completeOperation(int $operationId): bool
    {
        $operation = $this->find($operationId);
        return $operation ? $operation->update(['status' => Operation::STATUS_COMPLETED]) : false;
    }

    public function failOperation(int $operationId): bool
    {
        $operation = $this->find($operationId);
        return $operation ? $operation->update(['status' => Operation::STATUS_FAILED]) : false;
    }

    public function cancelOperation(int $operationId): bool
    {
        $operation = $this->find($operationId);
        return $operation ? $operation->update(['status' => Operation::STATUS_CANCELLED]) : false;
    }

    public function getTotalDepositsSum(int $userId = null): float
    {
        $query = $this->model->deposits()->completed();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->sum('amount');
    }

    public function getTotalWithdrawalsSum(int $userId = null): float
    {
        $query = $this->model->withdrawals()->completed();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->sum('amount');
    }

    public function getOperationsCount(int $userId = null): int
    {
        $query = $this->model;

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }

    public function getRecentOperations(int $limit = 10, int $userId = null)
    {
        $query = $this->model->with('user')->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->paginate($limit);
    }

    public function getLargestOperations(int $limit = 10, string $type = null): Collection
    {
        $query = $this->model->orderBy('amount', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->limit($limit)->get();
    }

    public function updateOperationStatus(int $operationId, string $status): bool
    {
        $operation = $this->find($operationId);
        return $operation ? $operation->update(['status' => $status]) : false;
    }

    // DTO methods
    public function getRecentOperationsDTO(int $limit = 10, int $userId = null)
    {
        $query = $this->model->with('user')->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $operations = $query->paginate($limit);

        // Convert operations to DTOs
        $operations->getCollection()->transform(function ($operation) {
            return OperationDTO::fromModel($operation);
        });

        return $operations;
    }

    public function findOperationDTO(int $id): ?OperationDTO
    {
        $operation = $this->find($id);
        return $operation ? OperationDTO::fromModel($operation) : null;
    }

    public function getOperationsByUserIdDTO(int $userId): array
    {
        $operations = $this->findByUserId($userId);
        return $operations->map(fn($operation) => OperationDTO::fromModel($operation))->toArray();
    }
}
