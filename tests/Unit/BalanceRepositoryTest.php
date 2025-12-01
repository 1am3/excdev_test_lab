<?php

namespace Tests\Unit;

use App\Models\Balance;
use App\Models\User;
use App\Repositories\BalanceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BalanceRepository $balanceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balanceRepository = new BalanceRepository(new Balance());
    }

    public function test_find_by_user_id()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $balance = Balance::create(['user_id' => $user->id, 'balance' => 0]);

        $found = $this->balanceRepository->findByUserId($user->id);

        $this->assertInstanceOf(Balance::class, $found);
        $this->assertEquals($balance->id, $found->id);
    }

    public function test_first_or_create_for_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $balance = $this->balanceRepository->firstOrCreateForUser($user->id);

        $this->assertInstanceOf(Balance::class, $balance);
        $this->assertEquals($user->id, $balance->user_id);
        $this->assertEquals(0, $balance->balance);
    }

    public function test_increase_balance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $balance = Balance::create(['user_id' => $user->id, 'balance' => 100]);

        $this->balanceRepository->increaseBalance($user->id, 50);

        $updatedBalance = $balance->fresh();
        $this->assertEquals(150, $updatedBalance->balance);
    }

    public function test_decrease_balance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $balance = Balance::create(['user_id' => $user->id, 'balance' => 100]);

        $this->balanceRepository->decreaseBalance($user->id, 30);

        $updatedBalance = $balance->fresh();
        $this->assertEquals(70, $updatedBalance->balance);
    }

    public function test_get_balance_value()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        Balance::create(['user_id' => $user->id, 'balance' => 250]);

        $balance = $this->balanceRepository->getBalanceValue($user->id);

        $this->assertEquals(250, $balance);
    }

    public function test_has_enough_balance()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        Balance::create(['user_id' => $user->id, 'balance' => 100]);

        $this->assertTrue($this->balanceRepository->hasEnoughBalance($user->id, 50));
        $this->assertFalse($this->balanceRepository->hasEnoughBalance($user->id, 150));
    }

    public function test_add_to_all_users()
    {
        User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass']);
        User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass']);
        User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass']);
        $this->balanceRepository->createMissingForAllUsers();

        $affectedRows = $this->balanceRepository->addToAllUsers(100);

        $this->assertEquals(3, $affectedRows);

        Balance::all()->each(function ($balance) {
            $this->assertEquals(100, $balance->balance);
        });
    }

    public function test_get_total_balance_sum()
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass']);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass']);

        Balance::create(['user_id' => $user1->id, 'balance' => 100]);
        Balance::create(['user_id' => $user2->id, 'balance' => 200]);
        Balance::create(['user_id' => $user3->id, 'balance' => 300]);

        $total = $this->balanceRepository->getTotalBalanceSum();

        $this->assertEquals(600, $total);
    }
}
