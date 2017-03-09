<?php namespace Common\Models;

/**
 * @property string    $name
 * @property bool      $explicit
 * @property Message[] $messages
 * @property Bot       $bot
 */
class Template extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $multiArrayModels = ['messages' => Message::class . '::factory'];
}
