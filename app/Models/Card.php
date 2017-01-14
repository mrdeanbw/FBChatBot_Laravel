<?php

namespace App\Models;


/**
 * App\Models\Card
 *
 * @property integer $id
 * @property integer $order
 * @property string $type
 * @property integer $context_id
 * @property string $context_type
 * @property string $text
 * @property string $image_url
 * @property string $title
 * @property string $subtitle
 * @property string $url
 * @property boolean $is_disabled
 * @property string $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $template_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $context
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstance[] $instances
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ChangeLog[] $changeLogs
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Card whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Card extends MessageBlock
{
    protected static $singleTableType = 'card';
    protected static $persisted = ['title', 'subtitle', 'url', 'image_url'];
}
