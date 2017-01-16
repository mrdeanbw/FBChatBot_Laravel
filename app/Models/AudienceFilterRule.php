<?php namespace App\Models;

/**
 * App\Models\AudienceFilterRule
 *
 * @property int $id
 * @property int $group_id
 * @property string $key
 * @property string $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\AudienceFilterGroup $filterGroup
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterRule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterRule whereGroupId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterRule whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterRule whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterRule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class AudienceFilterRule extends BaseModel
{

    protected $guarded = ['id'];

    public function filterGroup()
    {
        return $this->belongsTo(AudienceFilterGroup::class, 'group_id');
    }
}
