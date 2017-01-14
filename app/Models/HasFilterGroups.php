<?php
namespace App\Models;

trait HasFilterGroups
{
    
    public function filter_groups()
    {
        return $this->filterGroups();
    }

    public function filterGroups()
    {
        return $this->morphMany(AudienceFilterGroup::class, 'context');
    }
    
    protected static function bootHasFilterGroups() {
        static::deleting(function(HasFilterGroupsInterface $model) {
            $model->deleteFilterGroups();
        });
    }
    
    public function deleteFilterGroups()
    {
        $this->filterGroups->each(function($group){
            $group->delete();
        });
    }
}