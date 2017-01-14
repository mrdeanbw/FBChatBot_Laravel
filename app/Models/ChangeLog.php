<?php

namespace App\Models;

/**
 * App\Models\ChangeLog
 *
 * @property integer $id
 * @property integer $context_id
 * @property string $context_type
 * @property string $before
 * @property string $after
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $context
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereBefore($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereAfter($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChangeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class ChangeLog extends BaseModel
{

    public function context()
    {
        return $this->morphTo();
    }
}
