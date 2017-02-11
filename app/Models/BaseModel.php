<?php namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

/**
 * App\Models\Models
 *
 * @property string $id
 * @property \MongoDB\BSON\ObjectID  $_id
 */
abstract class BaseModel extends Model
{

    protected $guarded = ['_id', 'id'];

    /**
     * @param $query
     * @param $columnName
     * @param $value
     * @return mixed
     */
    public function scopeDate($query, $columnName, $value)
    {
        $boundaries = date_boundaries($value);

        $query->where($columnName, '>=', $boundaries[0])->where($columnName, '<', $boundaries[1]);

        return $query;
    }
}