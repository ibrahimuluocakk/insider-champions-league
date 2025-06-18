<?php

namespace App\Services;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all items.
     */
    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Get items with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Find an item by ID.
     */
    public function findById(int $id): ?Model
    {
        return $this->repository->find($id);
    }

    /**
     * Find an item by ID or fail.
     */
    public function findByIdOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Create a new item.
     */
    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * Update an existing item.
     */
    public function update(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete an item.
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get total count.
     */
    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * Find items by specific column.
     */
    public function findBy(string $column, mixed $value): Collection
    {
        return $this->repository->findBy($column, $value);
    }

    /**
     * Find first item by specific column.
     */
    public function findOneBy(string $column, mixed $value): ?Model
    {
        return $this->repository->findOneBy($column, $value);
    }
}
