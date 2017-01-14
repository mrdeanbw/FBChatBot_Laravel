<?php
namespace App\Models;

/**
 * App\Models\HasFilterGroupsInterface
 *
 * @property string                                                                          $filter_type
 * @property Page                                                                            $page
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AudienceFilterGroup[] $filter_groups
 */
interface HasFilterGroupsInterface
{

    public function filterGroups();

    public function filter_groups();

    public function page();
    
    public function deleteFilterGroups();
}