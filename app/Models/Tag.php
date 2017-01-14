<?php

namespace App\Models;


/**
 * App\Models\Tag
 *
 * @property integer                                                                $id
 * @property string                                                                 $tag
 * @property integer                                                                $page_id
 * @property \Carbon\Carbon                                                         $created_at
 * @property \Carbon\Carbon                                                         $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscriber[] $subscribers
 * @property-read \App\Models\Page                                                  $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag whereTag($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Tag extends BaseModel
{

    use BelongsToPage;

    protected $guarded = ['id'];

    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class);
    }

}
