<?php namespace App\Models;

/**
 * App\Models\Template
 *
 * @property int                                                                      $id
 * @property string                                                                   $name
 * @property bool                                                                     $is_explicit
 * @property int                                                                      $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \App\Models\Page                                                    $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereIsExplicit($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Template extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, BelongsToPage;

    protected $casts = ['is_explicit' => 'boolean'];
}
