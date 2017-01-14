<?php namespace App\Models;


/**
 * App\Models\MessageInstance
 *
 * @property integer $id
 * @property integer $subscriber_id
 * @property integer $message_block_id
 * @property string $sent_at
 * @property integer $page_id
 * @property string $facebook_id
 * @property \Carbon\Carbon $read_at
 * @property \Carbon\Carbon $delivered_at
 * @property integer $clicks
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\MessageBlock $messageBlock
 * @property-read \App\Models\Subscriber $subscriber
 * @property-read \App\Models\Page $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereSubscriberId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereMessageBlockId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereSentAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereFacebookId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereReadAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereDeliveredAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereClicks($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MessageInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class MessageInstance extends BaseModel
{
    protected $dates = ['read_at', 'delivered_at'];
    protected $casts = ['clicks' => 'integer'];
    
    public function message_block()
    {
        return $this->messageBlock();
    }    
    
    public function messageBlock()
    {
        return $this->belongsTo(MessageBlock::class);
    }
    
    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
