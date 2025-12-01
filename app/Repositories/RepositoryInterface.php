<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?Model;
    public function firstOrCreate(array $attributes, array $values = []): Model;
    public function create(array $attributes): Model;
    public function update(Model $model, array $attributes): bool;
    public function delete(Model $model): bool;
    public function findBy(array $criteria): ?Model;
    public function findByCriteria(array $criteria): Collection;
    public function getModel(): Model;
}
