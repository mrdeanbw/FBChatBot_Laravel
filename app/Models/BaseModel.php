<?php namespace App\Models;

use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * App\Models\Models
 *
 * @property string                 $id
 * @property \MongoDB\BSON\ObjectID $_id
 * @property \Carbon\Carbon         $created_at
 * @property \Carbon\Carbon         $updated_at
 */
abstract class BaseModel extends Model
{

    protected $guarded = ['_id', 'id'];

    /**
     * @param $query
     * @param $columnName
     * @param $value
     *
     * @return mixed
     */
    public function scopeDate($query, $columnName, $value)
    {
        $boundaries = date_boundaries($value);

        $query->where($columnName, '>=', $boundaries[0])->where($columnName, '<', $boundaries[1]);

        return $query;
    }


    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if ($key === '_id') {
            return $this->getAttributeFromArray($key);
        }

        return parent::getAttributeValue($key);
    }
}