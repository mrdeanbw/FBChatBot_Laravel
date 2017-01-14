<?php

namespace App\Models;

/**
 * App\Models\AudienceFilterGroup
 *
 * @property integer                                                                        $id
 * @property integer                                                                        $broadcast_id
 * @property string                                                                         $type
 * @property \Carbon\Carbon                                                                 $created_at
 * @property \Carbon\Carbon                                                                 $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AudienceFilterRule[] $rules
 * @property-read \App\Models\Broadcast                                                     $broadcast
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereBroadcastId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @property integer $context_id
 * @property string $context_type
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $context
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AudienceFilterGroup whereContextType($value)
 */
class AudienceFilterGroup extends BaseModel
{
    
    protected $guarded = ['id'];

    public function rules()
    {
        return $this->hasMany(AudienceFilterRule::class, 'group_id');
    }

    public function context()
    {
        return $this->morphTo();
    }
}
