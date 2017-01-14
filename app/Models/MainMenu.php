<?php

namespace App\Models;


/**
 * App\Models\MainMenu
 *
 * @property integer                                                                  $id
 * @property integer                                                                  $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property-read \App\Models\Page                                                    $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 */
class MainMenu extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, BelongsToPage;

    protected $guarded = ['id'];

}
