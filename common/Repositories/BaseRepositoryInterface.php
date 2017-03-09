<?php namespace Common\Repositories;

use Common\Models\BaseModel;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface BaseRepositoryInterface
{

    /**
     * @return string
     */
    public function model();

    /**
     * @param array $filterBy
     * @param array $orderBy
     * @param array $columns
     *
     * @return Collection
     */
    public function getAll(array $filterBy = [], array $orderBy = [], array $columns = ['*']);

    /**
     * @param array $filterBy
     * @param array $orderBy
     * @param array $columns
     *
     * @return BaseModel
     */
    public function getOne(array $filterBy = [], array $orderBy = [], array $columns = ['*']);

    /**
     * @param array $filterBy
     * @param array $orderBy
     * @return int
     */
    public function count(array $filterBy = [], array $orderBy = []);

    /**
     * Get a paginated ordered list of all subscribers matching given criteria.
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginate($page, array $filterBy, array $orderBy, $perPage);

    /**
     * Find resource by ID.
     * @param string $id
     * @return array|BaseModel|null
     */
    public function findById($id);

    /**
     * Find resource by ID or throw an exception if it doesn't exist.
     * @param string $id
     * @return BaseModel
     * @throws ModelNotFoundException
     */
    public function findByIdOrFail($id);

    /**
     * Create a resource.
     * @param array $data
     * @return BaseModel
     */
    public function create(array $data);

    /**
     * Bulk create resource.
     * @param array $data
     * @return bool
     */
    public function bulkCreate(array $data);

    /**
     * Update a resource.
     * @param BaseModel $model
     * @param array     $data
     */
    public function update($model, array $data);

    /**
     * Delete a resource.
     * @param BaseModel $model
     */
    public function delete($model);

    /**
     * @param $ids
     */
    public function bulkDelete(array $ids);
}
