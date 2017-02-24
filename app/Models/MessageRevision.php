<?php namespace App\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property array    stats
 * @property ObjectID $message_id
 */
class MessageRevision extends BaseModel
{
    use HasEmbeddedArrayModels;

    public $multiArrayModels = [
        'cards'    => Card::class,
        'buttons'  => Button::class,
        'messages' => Message::class . '::factory'
    ];

}
