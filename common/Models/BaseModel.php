<?php namespace Common\Models;

use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string                 $id
 * @property \MongoDB\BSON\ObjectID $_id
 * @property \Carbon\Carbon         $created_at
 */
abstract class BaseModel extends Model
{

    protected static $unguarded = true;
    const UPDATED_AT = null;

    /**
     * @param $query
     * @param $columnName
     * @param $value
     *
     * @return mixed
     */
    public function scopeDate($query, $columnName, $value)
    {
        if (starts_with($value, 'nullable_not:')) {
            $boundaries = date_boundaries(substr($value, 13));
            $query->where(function ($subQuery) use ($columnName, $boundaries) {
                $subQuery->whereNull($columnName)->orWhere($columnName, '<', $boundaries[0])->orWhere($columnName, '>=', $boundaries[1]);
            });

            return $query;
        }

        if (starts_with($value, 'not:')) {
            $boundaries = date_boundaries(substr($value, 4));
            $query->where(function ($subQuery) use ($columnName, $boundaries) {
                $subQuery->where($columnName, '<', $boundaries[0])->orWhere($columnName, '>=', $boundaries[1]);
            });

            return $query;
        }

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