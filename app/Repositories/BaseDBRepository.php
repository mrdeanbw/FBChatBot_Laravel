<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Models\BaseModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\UTCDatetime;

abstract class BaseDBRepository implements CommonRepositoryInterface
{

    /**
     * @param $id
     * @return BaseModel
     */
    public function findById($id)
    {
        /** @type string|BaseModel $model */
        $model = $this->model();

        return $model::find($id);
    }

    /**
     * @param $id
     * @return BaseModel
     */
    public function findByIdOrFail($id)
    {
        /** @type string|BaseModel $model */
        $model = $this->model();

        return $model::findOrFail($id);
    }

    /**
     * @param array $data
     * @return BaseModel
     */
    public function create(array $data)
    {
        /** @type string|BaseModel $model */
        $model = $this->model();

        return $model::create($data);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function bulkCreate(array $data)
    {
        $now = new UTCDateTime(round(microtime(true) * 1000));

        foreach (array_keys($data) as $i) {
            $data[$i]['created_at'] = $data[$i]['updated_at'] = $now;
        }

        /** @type string|BaseModel $model */
        $model = $this->model();

        return $model::insert($data);
    }


    /**
     * Update a model.
     * @param BaseModel $model
     * @param array     $data
     * @return bool
     */
    public function update($model, array $data)
    {
        $class = $this->model();
        $class::where('_id', $model->id)->update($data);
    }

    /**
     * Delete a model
     * @param BaseModel $model
     */
    public function delete($model)
    {
        $model->delete();
    }

    /**
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginate($page, array $filterBy, array $orderBy, $perPage)
    {
        /** @type string|BaseModel $model */
        $model = $this->model();

        $query = $model::query();

        foreach ($filterBy as $filter) {
            $this->applyQueryFilter($query, $filter);
        }

        $this->applyOrderBy($query, $orderBy);

        return $query->paginate((int)$perPage, ['*'], 'page', (int)$page);
    }

    /**
     * @param array   $filter with the following keys: type, attribute and value
     * @param Builder $query
     * @return Builder
     */
    protected function applyQueryFilter($query, array $filter)
    {

        switch ($filter['type']) {
            case 'exact':
                $query->where($filter['attribute'], '=', $filter['value']);
                break;

            case 'prefix':
                $query->where($filter['attribute'], 'regexp', "/^{$filter['value']}.*?/");
                break;

            case 'contains':
                $query->where($filter['attribute'], 'regexp', "/.*?{$filter['value']}.*?/");
                break;

            case 'date':
                $query->date($filter['attribute'], $filter['value']);
                break;
        }

        return $query;
    }


    /**
     * @param array   $orderBy
     * @param Builder $query
     */
    protected function applyOrderBy(Builder $query, array $orderBy)
    {
        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }
    }

}
