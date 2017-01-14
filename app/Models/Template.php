<?php

namespace App\Models;

/**
 * App\Models\Template
 *
 * @property integer                                                                  $id
 * @property string                                                                   $name
 * @property integer                                                                  $page_id
 * @property \Carbon\Carbon                                                           $created_at
 * @property \Carbon\Carbon                                                           $updated_at
 * @property-read \App\Models\Page                                                    $page
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $blocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @property boolean $is_explicit
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Template whereIsExplicit($value)
 */
class Template extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, BelongsToPage;

    protected $casts = ['is_explicit' => 'boolean'];
    protected $guarded = ['id'];
}
