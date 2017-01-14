<?php

namespace App\Models;


/**
 * App\Models\Image
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
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Image whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Image extends MessageBlock
{

    protected static $singleTableType = 'image';
    protected static $persisted = ['image_url'];
}
