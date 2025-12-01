<?php

namespace App\Jobs;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBalanceOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public string $operationType;
    public float $amount;
    public ?string $description;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $operationType, float $amount, ?string $description = null)
    {
        $this->userId = $userId;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->description = $description;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $userRepository = app(UserRepository::class);
            $user = $userRepository->find($this->userId);

            if (!$user) {
                Log::error('ProcessBalanceOperation: User not found', [
                    'user_id' => $this->userId,
                    'operation_type' => $this->operationType,
                    'amount' => $this->amount
                ]);
                return;
            }

            Log::info('ProcessBalanceOperation: Starting operation', [
                'user_id' => $this->userId,
                'email' => $user->email,
                'operation_type' => $this->operationType,
                'amount' => $this->amount,
                'current_balance' => $user->current_balance
            ]);

            if ($this->operationType === 'deposit') {
                $operation = $userRepository->deposit($this->userId, $this->amount, $this->description);

                Log::info('ProcessBalanceOperation: Deposit completed', [
                    'user_id' => $this->userId,
                    'operation_id' => $operation->id,
                    'new_balance' => $user->current_balance
                ]);

            } elseif ($this->operationType === 'withdraw') {
                $operation = $userRepository->withdraw($this->userId, $this->amount, $this->description);

                Log::info('ProcessBalanceOperation: Withdrawal completed', [
                    'user_id' => $this->userId,
                    'operation_id' => $operation->id,
                    'new_balance' => $user->current_balance
                ]);

            } else {
                Log::warning('ProcessBalanceOperation: Unknown operation type', [
                    'user_id' => $this->userId,
                    'operation_type' => $this->operationType,
                    'amount' => $this->amount
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessBalanceOperation: Operation failed', [
                'user_id' => $this->userId,
                'operation_type' => $this->operationType,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Повторная попытка через 60 секунд при неудаче
            if ($this->attempts() < 3) {
                $this->release(60);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessBalanceOperation: Job failed permanently', [
            'user_id' => $this->userId,
            'operation_type' => $this->operationType,
            'amount' => $this->amount,
            'error' => $exception->getMessage()
        ]);
    }
}
