<?php namespace App\Models;

/**
 * @property string   $status
 * @property Template $template
 * @property string   $template_id
 */
class Broadcast extends BaseModel
{

    protected $dates = ['send_at'];
}
