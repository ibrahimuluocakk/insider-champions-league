<?php

namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Find a model by its primary key.
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a model by its primary key or throw an exception.
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Get all models.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get models with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new model.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a model.
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * Delete a model.
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * Find models by specific column.
     */
    public function findBy(string $column, mixed $value): Collection
    {
        return $this->model->where($column, $value)->get();
    }

    /**
     * Find first model by specific column.
     */
    public function findOneBy(string $column, mixed $value): ?Model
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Get models with where conditions.
     */
    public function where(array $conditions): Collection
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->get();
    }

    /**
     * Count total models.
     */
    public function count(): int
    {
        return $this->model->count();
    }
}
