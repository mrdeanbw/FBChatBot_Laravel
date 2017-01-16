<?php namespace App\Models;

/**
 * App\Models\Text
 *
 * @property int                                                                      $id
 * @property int                                                                      $order
 * @property string                                                                   $type
 * @property int                                                                      $context_id
 * @property string                                                                   $context_type
 * @property string                                                                   $text
 * @property string                                                                   $image_url
 * @property string                                                                   $title
 * @property string                                                                   $subtitle
 * @property string                                                                   $url
 * @property bool                                                                     $is_disabled
 * @property string                                                                   $deleted_at
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property int                                                                      $template_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ChangeLog[]    $changeLogs
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Text whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Text extends MessageBlock
{

    protected static $persisted = ['text'];
    protected static $singleTableType = 'text';
}
