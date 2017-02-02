<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingTrait;


class Bug extends BaseModel
{
    use SoftDeletingTrait;

    protected $table = 'bug_reports';
    protected $dates = ['deleted_at'];

    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    public function screenshots()
    {
        return $this->hasMany('App\Models\BugScreenshot');
    }
}