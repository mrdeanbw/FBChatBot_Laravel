<?php namespace App\Models;

/**
 * App\Models\HasFilterGroupsInterface
 *
 * @property int                                                                             $page_id
 * @property bool                                                                            $filter_enabled
 * @property string                                                                          $filter_type
 * @property-read \App\Models\Page                                                           $page
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AudienceFilterGroup[] $filterGroups
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereFilterEnabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereFilterType($value)
 */
interface HasFilterGroupsInterface
{

    public function filterGroups();

    public function filter_groups();

    public function page();

    public function deleteFilterGroups();
}