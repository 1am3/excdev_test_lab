<?php

namespace App\DTO;

class UserDTO
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $email_verified_at;
    public string $created_at;
    public string $updated_at;
    public ?float $current_balance;
    public ?float $total_deposits;
    public ?float $total_withdrawals;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->email_verified_at = $data['email_verified_at'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
        $this->current_balance = $data['current_balance'] ?? null;
        $this->total_deposits = $data['total_deposits'] ?? null;
        $this->total_withdrawals = $data['total_withdrawals'] ?? null;
    }

    public static function fromModel(\App\Models\User $user): self
    {
        return new self([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'current_balance' => $user->current_balance,
            'total_deposits' => $user->total_deposits,
            'total_withdrawals' => $user->total_withdrawals,
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'current_balance' => $this->current_balance,
            'total_deposits' => $this->total_deposits,
            'total_withdrawals' => $this->total_withdrawals,
        ];
    }
}
