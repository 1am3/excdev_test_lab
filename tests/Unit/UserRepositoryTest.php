<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Operation;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new UserRepository(new User());
    }

    public function test_create_user()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $user = $this->userRepository->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
    }

    public function test_create_user_with_balance()
    {
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123'
        ];

        $user = $this->userRepository->createWithBalance($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->balance);
        $this->assertEquals(0, $user->balance->balance);
    }

    public function test_find_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $found = $this->userRepository->find($user->id);

        $this->assertEquals($user->id, $found->id);
        $this->assertEquals($user->email, $found->email);
    }

    public function test_find_non_existent_user_returns_null()
    {
        $found = $this->userRepository->find(999);

        $this->assertNull($found);
    }

    public function test_deposit()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $operation = $this->userRepository->deposit($user->id, 1000.00, 'Test deposit');

        $this->assertInstanceOf(Operation::class, $operation);
        $this->assertEquals(Operation::TYPE_DEPOSIT, $operation->type);
        $this->assertEquals(1000.00, $operation->amount);
        $this->assertEquals(0, $operation->balance_before);
        $this->assertEquals(1000.00, $operation->balance_after);
        $this->assertEquals(Operation::STATUS_COMPLETED, $operation->status);
        $this->assertEquals('Test deposit', $operation->description);
    }

    public function test_withdraw_with_sufficient_balance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);

        $operation = $this->userRepository->withdraw($user->id, 500.00, 'Test withdrawal');

        $this->assertInstanceOf(Operation::class, $operation);
        $this->assertEquals(Operation::TYPE_WITHDRAWAL, $operation->type);
        $this->assertEquals(500.00, $operation->amount);
        $this->assertEquals(1000.00, $operation->balance_before);
        $this->assertEquals(500.00, $operation->balance_after);
    }

    public function test_withdraw_with_insufficient_balance_throws_exception()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно средств на балансе');

        $this->userRepository->withdraw($user->id, 500.00);
    }

    public function test_try_withdraw_success()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);

        $success = $this->userRepository->tryWithdraw($user->id, 500.00, 'Test withdrawal');

        $this->assertTrue($success);
    }

    public function test_try_withdraw_failure()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $success = $this->userRepository->tryWithdraw($user->id, 500.00, 'Test withdrawal');

        $this->assertFalse($success);

        $operation = $user->operations()->latest()->first();
        $this->assertEquals(Operation::TYPE_WITHDRAWAL, $operation->type);
        $this->assertEquals(Operation::STATUS_FAILED, $operation->status);
    }

    public function test_get_balance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);
        $this->userRepository->withdraw($user->id, 300.00);

        $balance = $this->userRepository->getBalance($user->id);

        $this->assertEquals(700.00, $balance);
    }

    public function test_has_enough_balance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);

        $this->assertTrue($this->userRepository->hasEnoughBalance($user->id, 500.00));
        $this->assertFalse($this->userRepository->hasEnoughBalance($user->id, 1500.00));
    }

    public function test_transfer_between_users()
    {
        $fromUser = User::create([
            'name' => 'From User',
            'email' => 'from@example.com',
            'password' => 'password123'
        ]);
        $toUser = User::create([
            'name' => 'To User',
            'email' => 'to@example.com',
            'password' => 'password123'
        ]);

        $this->userRepository->deposit($fromUser->id, 1000.00);

        $operations = $this->userRepository->transfer(
            $fromUser->id,
            $toUser->id,
            500.00,
            'Test transfer'
        );

        $this->assertCount(2, $operations);

        $withdrawOp = $operations[0];
        $depositOp = $operations[1];

        $this->assertEquals(Operation::TYPE_WITHDRAWAL, $withdrawOp->type);
        $this->assertEquals(Operation::TYPE_DEPOSIT, $depositOp->type);
        $this->assertEquals(500.00, $withdrawOp->amount);
        $this->assertEquals(500.00, $depositOp->amount);

        $this->assertEquals(500.00, $this->userRepository->getBalance($fromUser->id));
        $this->assertEquals(500.00, $this->userRepository->getBalance($toUser->id));
    }

    public function test_transfer_insufficient_balance()
    {
        $fromUser = User::create([
            'name' => 'From User',
            'email' => 'from@example.com',
            'password' => 'password123'
        ]);
        $toUser = User::create([
            'name' => 'To User',
            'email' => 'to@example.com',
            'password' => 'password123'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно средств для перевода');

        $this->userRepository->transfer($fromUser->id, $toUser->id, 500.00);
    }

    public function test_get_operations_history()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $from = now()->subDays(5);
        $to = now();

        $this->userRepository->deposit($user->id, 100.00);
        $this->userRepository->withdraw($user->id, 50.00);

        $history = $this->userRepository->getOperationsHistory($user->id, $from, $to);

        $this->assertCount(2, $history);
    }

    public function test_current_balance_attribute()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);
        $this->userRepository->withdraw($user->id, 200.00);

        $this->assertEquals(800.00, $user->fresh()->current_balance);
    }

    public function test_total_deposits_attribute()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);
        $this->userRepository->deposit($user->id, 500.00);

        $this->assertEquals(1500.00, $user->fresh()->total_deposits);
    }

    public function test_total_withdrawals_attribute()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->userRepository->deposit($user->id, 1000.00);
        $this->userRepository->withdraw($user->id, 300.00);
        $this->userRepository->withdraw($user->id, 200.00);

        $this->assertEquals(500.00, $user->fresh()->total_withdrawals);
    }

    public function test_get_users_with_recent_activity()
    {
        $activeUser = User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => 'password123'
        ]);
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => 'password123'
        ]);

        $this->userRepository->deposit($activeUser->id, 100.00);

        $recentUsers = $this->userRepository->getUsersWithRecentActivity(1);

        $this->assertCount(1, $recentUsers);
        $this->assertEquals($activeUser->id, $recentUsers->first()->id);
    }
}
