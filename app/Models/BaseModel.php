<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{

    public function scopeDate($query, $columnName, $value)
    {
        $boundaries = date_boundaries($value);

        if (! is_null($boundaries)) {
            $query->where($columnName, '>=', $boundaries[0])->where($columnName, '<', $boundaries[1]);
        }

        return $query;
    }
}