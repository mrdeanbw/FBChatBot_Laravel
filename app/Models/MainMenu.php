<?php namespace App\Models;

/**
 * App\Models\MainMenu
 *
 * @property int                                                                      $id
 * @property int                                                                      $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \App\Models\Page                                                    $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\MainMenu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class MainMenu extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, BelongsToPage;

}
