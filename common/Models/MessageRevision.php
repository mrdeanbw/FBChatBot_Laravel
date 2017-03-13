<?php namespace Common\Models;

/**
 * @property array                  $stats
 * @property \MongoDB\BSON\ObjectID $message_id
 * @property ImageFile              $file
 * @property Card[]                 $cards
 */
class MessageRevision extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $multiArrayModels = [
        'cards'    => Card::class,
        'buttons'  => Button::class,
        'messages' => Message::class . '::factory'
    ];

    public $arrayModels = ['file' => ImageFile::class];

}
