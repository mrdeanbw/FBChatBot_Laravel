<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Models\BaseModel;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Jenssegers\Mongodb\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class DBBaseRepository implements BaseRepositoryInterface
{

    /**
     * @param array $filterBy
     * @param array $orderBy
     * @param array $columns
     *
     * @return Collection
     */
    public function getAll(array $filterBy = [], array $orderBy = [], array $columns = ['*'])
    {
        return $this->applyFilterByAndOrderBy($filterBy, $orderBy)->get($columns);
    }

    /**
     * @param array $filterBy
     * @param array $orderBy
     * @param array $columns
     *
     * @return BaseModel|null
     */
    public function getOne(array $filterBy = [], array $orderBy = [], array $columns = ['*'])
    {
        return $this->applyFilterByAndOrderBy($filterBy, $orderBy)->first($columns);
    }

    /**
     * @param array $filterBy
     * @param array $orderBy
     *
     * @return int
     */
    public function count(array $filterBy = [], array $orderBy = [])
    {
        return $this->applyFilterByAndOrderBy($filterBy, $orderBy)->count();
    }


    /**
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     *
     * @return Paginator
     */
    public function paginate($page, array $filterBy, array $orderBy, $perPage)
    {
        $query = $this->applyFilterByAndOrderBy($filterBy, $orderBy);

        return $query->paginate((int)$perPage, ['*'], 'page', (int)$page);
    }

    /**
     * @param string|ObjectID $id
     *
     * @return BaseModel
     */
    public function findById($id)
    {
        $filter = [['operator' => '=', 'key' => '_id', 'value' => $id]];

        return $this->getOne($filter);
    }

    /**
     * @param $id
     *
     * @return BaseModel
     */
    public function findByIdOrFail($id)
    {
        $filter = [['operator' => '=', 'key' => '_id', 'value' => $id]];

        if (! is_null($result = $this->getOne($filter))) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel($this->model(), $id);
    }

    /**
     * @param array $data
     *
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
     *
     * @return bool
     */
    public function bulkCreate(array $data)
    {
        $now = new UTCDateTime(round(microtime(true) * 1000));

        $data = array_map(function ($item) {
            return $this->normalizeCarbonDates($item);
        }, $data);

        foreach (array_keys($data) as $i) {
            $data[$i]['created_at'] = array_get($data[$i], 'created_at', $now);
            $data[$i]['updated_at'] = array_get($data[$i], 'updated_at', $now);
        }

        /** @type string|BaseModel $model */
        $model = $this->model();

        return $model::insert($data);
    }

    /**
     * Update a model.
     *
     * @param BaseModel $model
     * @param array     $data
     *
     * @return bool
     */
    public function update($model, array $data)
    {
        $class = $this->model();

        $model->fill($data);

        if (starts_with(key($data), '$')) {
            return $class::where('_id', $model->_id)->update($data);
        }

        $data = $this->normalizeCarbonDates($data);

        return $class::where('_id', $model->_id)->update($data);
    }

    /**
     * Delete a model
     *
     * @param BaseModel $model
     */
    public function delete($model)
    {
        $model->delete();
    }

    /**
     * @param array   $filter with the following keys: type, attribute and value
     * @param Builder $query
     *
     * @return Builder
     */
    protected function applyQueryFilter($query, array $filter)
    {

        switch ($filter['operator']) {
            case 'prefix':
                $query->where($filter['key'], 'regexp', "/^{$filter['value']}.*?/i");
                break;

            case 'contains':
                $query->where($filter['key'], 'regexp', "/.*?{$filter['value']}.*?/i");
                break;

            case 'date':
                $query->date($filter['key'], $filter['value']);
                break;

            default:
                $query->where($filter['key'], $filter['operator'], $filter['value']);

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

    /**
     * @param array $filterBy
     * @param array $orderBy
     *
     * @return Builder
     */
    protected function applyFilterByAndOrderBy(array $filterBy = [], array $orderBy = [])
    {
        /** @type string|BaseModel $model */
        $model = $this->model();

        $query = $model::query();

        foreach ($filterBy as $filter) {
            $this->applyQueryFilter($query, $filter);
        }

        $this->applyOrderBy($query, $orderBy);

        return $query;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function normalizeCarbonDates(array $data)
    {
        foreach ($data as $key => &$value) {
            if (is_a($value, Carbon::class)) {
                $value = mongo_date($value);
            }
        }

        return $data;
    }
}
