<?php namespace App\Models;

/**
 * App\Models\DefaultReply
 *
 * @property int                                                                      $id
 * @property int                                                                      $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \App\Models\Page                                                    $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DefaultReply whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DefaultReply wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DefaultReply whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DefaultReply whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class DefaultReply extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, BelongsToPage;

}
