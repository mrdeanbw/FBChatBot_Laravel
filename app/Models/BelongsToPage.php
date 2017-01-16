<?php namespace App\Models;

trait BelongsToPage
{

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}