<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bug extends BaseModel
{
    protected $table = 'bug_reports';

    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    public function screenshots()
    {
        return $this->hasMany('App\Models\BugScreenshot');
    }
}