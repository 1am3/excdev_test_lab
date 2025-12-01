<?php

namespace App\DTO;

class BalanceDTO
{
    public int $id;
    public int $user_id;
    public float $balance;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->user_id = $data['user_id'] ?? 0;
        $this->balance = $data['balance'] ?? 0.0;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public static function fromModel(\App\Models\Balance $balance): self
    {
        return new self([
            'id' => $balance->id,
            'user_id' => $balance->user_id,
            'balance' => $balance->balance,
            'created_at' => $balance->created_at?->toISOString(),
            'updated_at' => $balance->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'balance' => $this->balance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function hasEnough(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
