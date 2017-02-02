<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Comment extends BaseModel
{
    use SoftDeletingTrait;

    protected $table = 'bug_comments';
    protected $dates = ['deleted_at'];

    public function comments()
    {
        return $this->hasMany('App\Models\Comment', 'parent_id');
    }

    public function bugs()
    {
        return $this->belongsToMany('App\Models\Bug');
    }
}