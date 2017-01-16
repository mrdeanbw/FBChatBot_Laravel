<?php namespace App\Models;

/**
 * App\Models\AutoReplyRule
 *
 * @property int $id
 * @property int $page_id
 * @property bool $is_disabled
 * @property int $template_id
 * @property string $mode
 * @property string $keyword
 * @property string $action
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Template $template
 * @property-read \App\Models\Page $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereMode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereKeyword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereAction($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AutoReplyRule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class AutoReplyRule extends BaseModel
{

    use BelongsToPage;

    protected $guarded = ['id'];
    protected $casts = ['is_disabled' => 'boolean'];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
