<?php

namespace App\Models;


/**
 * App\Models\WelcomeMessage
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $blocks
 * @property-read \App\Models\Page                                                    $page
 * @mixin \Eloquent
 * @property integer                                                                  $id
 * @property string                                                                   $text
 * @property integer                                                                  $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\WelcomeMessage whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\WelcomeMessage whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\WelcomeMessage wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\WelcomeMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\WelcomeMessage whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 */
class DefaultReply extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, BelongsToPage;

    protected $guarded = ['id'];
}
