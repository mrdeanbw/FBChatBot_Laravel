<?php namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

/**
 * App\Models\MessageBlock
 *
 * @property integer                                                                  $id
 * @property integer                                                                  $order
 * @property string                                                                   $type
 * @property array                                                                    $options
 * @property integer                                                                  $context_id
 * @property string                                                                   $context_type
 * @property boolean                                                                  $is_disabled
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent                       $context
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $children
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereOptions($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read mixed                                                               $template
 * @property-read mixed                                                               $tag_tags
 * @property-read mixed                                                               $untag_tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $message_blocks
 * @property array                                                                    $hidden_options
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereHiddenOptions($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscriber[]   $subscribers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @property string $text
 * @property string $image_url
 * @property string $title
 * @property string $subtitle
 * @property string $url
 * @property string $deleted_at
 * @property integer $template_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstance[] $instances
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ChangeLog[] $changeLogs
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageBlock whereTemplateId($value)
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

    protected $guarded = ['id'];

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
