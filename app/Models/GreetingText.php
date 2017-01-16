<?php namespace App\Models;

/**
 * App\Models\GreetingText
 *
 * @property int                   $id
 * @property string                $text
 * @property int                   $page_id
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 * @property-read \App\Models\Page $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GreetingText whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class GreetingText extends BaseModel
{

    use BelongsToPage;

}
