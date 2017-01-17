<?php namespace App\Models;

/**
 * App\Models\Button
 *
 * @property int                                                                         $id
 * @property int                                                                         $order
 * @property string                                                                      $type
 * @property int                                                                         $context_id
 * @property string                                                                      $context_type
 * @property string                                                                      $text
 * @property string                                                                      $image_url
 * @property string                                                                      $title
 * @property string                                                                      $subtitle
 * @property string                                                                      $url
 * @property bool                                                                        $is_disabled
 * @property string                                                                      $deleted_at
 * @property \Carbon\Carbon                                                              $created_at
 * @property \Carbon\Carbon                                                              $updated_at
 * @property int                                                                         $template_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[]             $tags
 * @property-read \App\Models\Template                                                   $template
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent                          $context
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstance[] $instances
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]    $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]    $unorderedMessageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ChangeLog[]       $changeLogs
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Button whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Button extends MessageBlock
{

    protected static $singleTableType = 'button';
    protected static $persisted = ['title', 'url', 'template_id'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withPivot(['add']);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function addTags()
    {
        return $this->tags()->wherePivot('add', 1);
    }

    public function removeTags()
    {
        return $this->tags()->wherePivot('add', 0);
    }
}
