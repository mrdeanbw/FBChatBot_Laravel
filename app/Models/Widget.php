<?php
namespace App\Models;

/**
 * App\Models\Widget
 *
 * @property integer $id
 * @property integer $page_id
 * @property integer $sequence_id
 * @property string $name
 * @property string $type
 * @property string $options
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Sequence $sequence
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \App\Models\Page $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereSequenceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereOptions($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Widget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Widget extends BaseModel implements HasMessageBlocksInterface
{

    use BelongsToPage, HasMessageBlocks;

    protected $guarded = ['id'];

    protected $casts = ['options' => 'array'];


    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
