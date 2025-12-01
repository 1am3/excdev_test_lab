<?php

namespace Tests\Unit;

use App\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TestRepository(new User());
    }

    public function test_all_returns_collection()
    {
        User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass']);
        User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass']);
        User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass']);

        $result = $this->repository->all();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_paginate_returns_length_aware_paginator()
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'pass'
            ]);
        }

        $result = $this->repository->paginate(2);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        $this->assertEquals(5, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    public function test_find_existing_record()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $result = $this->repository->find($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_non_existing_record_returns_null()
    {
        $result = $this->repository->find(999);

        $this->assertNull($result);
    }

    public function test_first_or_create_creates_new_record()
    {
        $data = ['email' => 'unique@example.com', 'name' => 'Test', 'password' => 'pass'];

        $result = $this->repository->firstOrCreate($data);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('unique@example.com', $result->email);
    }

    public function test_first_or_create_returns_existing_record()
    {
        $user = User::create([
            'email' => 'existing@example.com',
            'name' => 'Test',
            'password' => 'pass'
        ]);

        $result = $this->repository->firstOrCreate(['email' => 'existing@example.com']);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_create_with_additional_data()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('test@example.com', $result->email);
    }

    public function test_update_existing_record()
    {
        $user = User::create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'pass'
        ]);

        $result = $this->repository->update($user, ['name' => 'New Name']);

        $this->assertTrue($result);
        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_delete_existing_record()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'delete@example.com',
            'password' => 'pass'
        ]);

        $result = $this->repository->delete($user);

        $this->assertTrue($result);
        $this->assertNull(User::find($user->id));
    }

    public function test_find_by_criteria()
    {
        $user = User::create([
            'email' => 'findme@example.com',
            'name' => 'Find Me',
            'password' => 'pass'
        ]);

        $result = $this->repository->findBy(['email' => 'findme@example.com']);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_criteria_returns_null_when_not_found()
    {
        $result = $this->repository->findBy(['email' => 'nonexistent@example.com']);

        $this->assertNull($result);
    }

    public function test_find_by_criteria_multiple_conditions()
    {
        $user = User::create([
            'name' => 'Unique Name',
            'email' => 'unique@example.com',
            'password' => 'pass'
        ]);

        $result = $this->repository->findBy([
            'name' => 'Unique Name',
            'email' => 'unique@example.com'
        ]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_criteria_returns_collection()
    {
        User::create(['name' => 'Same Name', 'email' => 'same1@example.com', 'password' => 'pass']);
        User::create(['name' => 'Same Name', 'email' => 'same2@example.com', 'password' => 'pass']);

        $results = $this->repository->findByCriteria(['name' => 'Same Name']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        $this->assertCount(2, $results);
    }

    public function test_get_model_returns_model_instance()
    {
        $model = $this->repository->getModel();

        $this->assertInstanceOf(User::class, $model);
    }
}

class TestRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
