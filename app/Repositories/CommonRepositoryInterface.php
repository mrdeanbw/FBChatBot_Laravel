<?php namespace App\Repositories;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface CommonRepositoryInterface
{
    
    /**
     * @return string
     */
    public function model();

    /**
     * Find resource by ID.
     * @param string $id
     * @return BaseModel|null
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
}
