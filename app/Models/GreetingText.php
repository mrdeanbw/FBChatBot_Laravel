<?php

namespace App\Models;


/**
 * App\Models\GreetingText
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $blocks
 * @property-read \App\Models\Page                                                    $page
 * @mixin \Eloquent
 * @property integer                                                                  $id
 * @property string                                                                   $text
 * @property integer                                                                  $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 */
class GreetingText extends BaseModel
{

    use BelongsToPage;

    protected $guarded = ['id'];
}
