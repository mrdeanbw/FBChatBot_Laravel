<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Models
 *
 * @property int $id
 */
abstract class BaseModel extends Model
{

    protected $guarded = ['id'];

    public function scopeDate($query, $columnName, $value)
    {
        $boundaries = date_boundaries($value);

        $query->where($columnName, '>=', $boundaries[0])->where($columnName, '<', $boundaries[1]);

        return $query;
    }
}