<?php namespace App\Models;

/**
 * App\Models\AudienceFilterGroup
 *
 * @property int                                                                            $id
 * @property int                                                                            $context_id
 * @property string                                                                         $context_type
 * @property string                                                                         $type
 * @property \Carbon\Carbon                                                                 $created_at
 * @property \Carbon\Carbon                                                                 $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AudienceFilterRule[] $rules
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent                             $context
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class AudienceFilterGroup extends BaseModel
{

    public function rules()
    {
        return $this->hasMany(AudienceFilterRule::class, 'group_id');
    }

    public function context()
    {
        return $this->morphTo();
    }
}
