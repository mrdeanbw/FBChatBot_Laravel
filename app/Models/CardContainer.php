<?php namespace App\Models;

/**
 * App\Models\CardContainer
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
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereContextId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereContextType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereSubtitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereIsDisabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\CardContainer whereTemplateId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class CardContainer extends MessageBlock
{

    protected static $singleTableType = 'card_container';
}
