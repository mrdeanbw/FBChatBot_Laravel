<?php

namespace App\Models;

use App\Events\SequenceFiltersWereAltered;

/**
 * App\Models\Sequence
 *
 * @property integer                                                                     $id
 * @property integer                                                                     $page_id
 * @property string                                                                      $name
 * @property \Carbon\Carbon                                                              $created_at
 * @property \Carbon\Carbon                                                              $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SequenceMessage[] $messages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SequenceMessage[] $unorderedMessages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscriber[]      $subscribers
 * @property-read \App\Models\Page                                                       $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 * @property boolean $filter_enabled
 * @property string $filter_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AudienceFilterGroup[] $filterGroups
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence whereFilterEnabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Sequence whereFilterType($value)
 */
class Sequence extends BaseModel implements HasFilterGroupsInterface
{

    use BelongsToPage, HasFilterGroups;

    protected $guarded = ['id'];
    protected $casts = ['filter_enabled' => 'boolean'];

    public function messages()
    {
        return $this->hasMany(SequenceMessage::class)->orderBy('order');
    }

    public function unorderedMessages()
    {
        return $this->hasMany(SequenceMessage::class);
    }

    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class)->withTimestamps();
    }
}
