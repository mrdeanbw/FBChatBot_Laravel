<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends BaseModel
{
    protected $table = 'bug_comments';

    public function comments()
    {
        return $this->hasMany('App\Models\Comment', 'parent_id');
    }

    public function bugs()
    {
        return $this->belongsToMany('App\Models\Bug');
    }
}