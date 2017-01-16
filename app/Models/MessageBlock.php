<?php namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

/**
 * App\Models\MessageBlock
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
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent                          $context
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstance[] $instances
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]    $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]    $unorderedMessageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ChangeLog[]       $changeLogs
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class MessageBlock extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, SingleTableInheritanceTrait, LogChanges, SoftDeletes;

    protected $table = "message_blocks";

    protected static $singleTableTypeField = 'type';

    protected static $singleTableSubclasses = [
        Text::class,
        Image::class,
        CardContainer::class,
        Card::class,
        Button::class
    ];

    protected static $persisted = ['order', 'type', 'is_disabled', 'context_id', 'context_type'];


    protected $casts = [
        'is_disabled'    => 'boolean',
        'tags.pivot.add' => 'boolean'
    ];

    public function context()
    {
        return $this->morphTo();
    }

    public function superContext()
    {
        $types = array_unique(array_merge([get_class($this)], static::$singleTableSubclasses));

        $model = $this;

        while (in_array($model->context_type, $types)) {
            $model = $model->context;
        }

        return $model->context;
    }

    public function instances()
    {
        return $this->hasMany(MessageInstance::class, 'message_block_id');
    }

    public function page()
    {
        return $this->superContext()->page();
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function (MessageBlock $messageBlock) {
            $context = $messageBlock->context()->withTrashed()->firstOrFail();
            $order = 1;
            foreach ($context->message_blocks as $sibling) {
                if ($messageBlock->id == $sibling->id) {
                    continue;
                }
                $sibling->order = $order++;
                $sibling->save();
            }
        });
    }
}
