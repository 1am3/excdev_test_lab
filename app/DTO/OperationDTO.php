<?php

namespace App\DTO;

class OperationDTO
{
    public int $id;
    public int $user_id;
    public string $type;
    public float $amount;
    public float $balance_before;
    public float $balance_after;
    public string $status;
    public ?string $description;
    public string $created_at;
    public string $updated_at;

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->user_id = $data['user_id'] ?? 0;
        $this->type = $data['type'] ?? '';
        $this->amount = $data['amount'] ?? 0.0;
        $this->balance_before = $data['balance_before'] ?? 0.0;
        $this->balance_after = $data['balance_after'] ?? 0.0;
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->description = $data['description'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public static function fromModel(\App\Models\Operation $operation): self
    {
        return new self([
            'id' => $operation->id,
            'user_id' => $operation->user_id,
            'type' => $operation->type,
            'amount' => $operation->amount,
            'balance_before' => $operation->balance_before,
            'balance_after' => $operation->balance_after,
            'status' => $operation->status,
            'description' => $operation->description,
            'created_at' => $operation->created_at?->toISOString(),
            'updated_at' => $operation->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
