<?php

namespace Tests\Unit;

use App\Models\Operation;
use App\Models\User;
use App\Repositories\OperationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OperationRepository $operationRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operationRepository = new OperationRepository(new Operation());
    }

    public function test_find_by_user_id()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);
        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => 50,
        ]);

        $operations = $this->operationRepository->findByUserId($user->id);

        $this->assertCount(2, $operations);
        $this->assertEquals($user->id, $operations->first()->user_id);
    }

    public function test_get_deposits()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => 50,
        ]);

        $deposits = $this->operationRepository->getDeposits($user->id);

        $this->assertCount(1, $deposits);
        $this->assertEquals(Operation::TYPE_DEPOSIT, $deposits->first()->type);
    }

    public function test_get_withdrawals()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => 50,
        ]);

        $withdrawals = $this->operationRepository->getWithdrawals($user->id);

        $this->assertCount(1, $withdrawals);
        $this->assertEquals(Operation::TYPE_WITHDRAWAL, $withdrawals->first()->type);
    }

    public function test_get_completed_operations()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'status' => Operation::STATUS_COMPLETED,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        Operation::create([
            'user_id' => $user->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 50,
        ]);

        $completedOps = $this->operationRepository->getCompletedOperations($user->id);

        $this->assertCount(1, $completedOps);
        $this->assertEquals(Operation::STATUS_COMPLETED, $completedOps->first()->status);
    }

    public function test_get_pending_operations()
    {
        Operation::create([
            'user_id' => User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass'])->id,
            'status' => Operation::STATUS_COMPLETED,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);
        Operation::create([
            'user_id' => User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass'])->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);
        Operation::create([
            'user_id' => User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass'])->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        $pendingOps = $this->operationRepository->getPendingOperations();

        $this->assertCount(2, $pendingOps);
        $pendingOps->each(function ($op) {
            $this->assertEquals(Operation::STATUS_PENDING, $op->status);
        });
    }

    public function test_complete_operation()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $operation = Operation::create([
            'user_id' => $user->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        $result = $this->operationRepository->completeOperation($operation->id);

        $this->assertTrue($result);
        $this->assertEquals(Operation::STATUS_COMPLETED, $operation->fresh()->status);
    }

    public function test_fail_operation()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $operation = Operation::create([
            'user_id' => $user->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        $result = $this->operationRepository->failOperation($operation->id);

        $this->assertTrue($result);
        $this->assertEquals(Operation::STATUS_FAILED, $operation->fresh()->status);
    }

    public function test_cancel_operation()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $operation = Operation::create([
            'user_id' => $user->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        $result = $this->operationRepository->cancelOperation($operation->id);

        $this->assertTrue($result);
        $this->assertEquals(Operation::STATUS_CANCELLED, $operation->fresh()->status);
    }

    public function test_get_total_deposits_sum()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
            'status' => Operation::STATUS_COMPLETED,
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 50,
            'status' => Operation::STATUS_COMPLETED,
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 25,
            'status' => Operation::STATUS_PENDING,
        ]);

        $total = $this->operationRepository->getTotalDepositsSum($user->id);

        $this->assertEquals(150, $total);
    }

    public function test_get_total_withdrawals_sum()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => 30,
            'status' => Operation::STATUS_COMPLETED,
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => 20,
            'status' => Operation::STATUS_COMPLETED,
        ]);

        $total = $this->operationRepository->getTotalWithdrawalsSum($user->id);

        $this->assertEquals(50, $total);
    }

    public function test_get_operations_count()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);
        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_WITHDRAWAL,
            'amount' => 50,
        ]);
        Operation::create([
            'user_id' => $user->id,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 25,
        ]);

        $count = $this->operationRepository->getOperationsCount($user->id);

        $this->assertEquals(3, $count);
    }

    public function test_get_largest_operations()
    {
        Operation::create([
            'user_id' => User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass'])->id,
            'amount' => 100,
            'type' => Operation::TYPE_DEPOSIT,
        ]);
        Operation::create([
            'user_id' => User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass'])->id,
            'amount' => 500,
            'type' => Operation::TYPE_DEPOSIT,
        ]);
        Operation::create([
            'user_id' => User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass'])->id,
            'amount' => 200,
            'type' => Operation::TYPE_DEPOSIT,
        ]);
        Operation::create([
            'user_id' => User::create(['name' => 'User 4', 'email' => 'user4@example.com', 'password' => 'pass'])->id,
            'amount' => 300,
            'type' => Operation::TYPE_DEPOSIT,
        ]);

        $largest = $this->operationRepository->getLargestOperations(2);

        $this->assertCount(2, $largest);
        $this->assertEquals(500, $largest->first()->amount);
        $this->assertEquals(300, $largest->get(1)->amount);
    }

    public function test_update_operation_status()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $operation = Operation::create([
            'user_id' => $user->id,
            'status' => Operation::STATUS_PENDING,
            'type' => Operation::TYPE_DEPOSIT,
            'amount' => 100,
        ]);

        $result = $this->operationRepository->updateOperationStatus(
            $operation->id,
            Operation::STATUS_FAILED
        );

        $this->assertTrue($result);
        $this->assertEquals(Operation::STATUS_FAILED, $operation->fresh()->status);
    }
}
