<?php namespace App\Models;

/**
 * @property string    name
 * @property bool      explicit
 * @property Message[] messages
 */
class Template extends BaseModel
{
    use HasEmbeddedArrayModels;

    public $multiArrayModels = [
        'messages' => Message::class . '::factory',
    ];

}
