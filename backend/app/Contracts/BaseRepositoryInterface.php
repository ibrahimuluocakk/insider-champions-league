<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Find a model by its primary key.
     */
    public function find(int $id): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     */
    public function findOrFail(int $id): Model;

    /**
     * Get all models.
     */
    public function all(): Collection;

    /**
     * Get models with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new model.
     */
    public function create(array $data): Model;

    /**
     * Update a model.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a model.
     */
    public function delete(int $id): bool;

    /**
     * Find models by specific column.
     */
    public function findBy(string $column, mixed $value): Collection;

    /**
     * Find first model by specific column.
     */
    public function findOneBy(string $column, mixed $value): ?Model;

    /**
     * Get models with where conditions.
     */
    public function where(array $conditions): Collection;

    /**
     * Count total models.
     */
    public function count(): int;
}
