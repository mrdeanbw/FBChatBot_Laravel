<?php namespace App\Services;

use App\Models\Card;
use App\Models\Image;
use App\Models\Message;
use MongoDB\BSON\ObjectID;
use App\Models\MessageRevision;
use Illuminate\Support\Collection;
use Intervention\Image\ImageManagerStatic;
use Dingo\Api\Exception\ValidationHttpException;
use App\Repositories\Bot\BotRepositoryInterface;
use App\Repositories\Template\TemplateRepositoryInterface;
use App\Repositories\MessageRevision\MessageRevisionRepositoryInterface;

class MessageService
{

    const diffFields = [
        'text'           => ['text', 'buttons'],
        'image'          => ['image_url'],
        'card_container' => ['cards'],
        'card'           => ['title', 'subtitle', 'url', 'image_url', 'buttons'],
        'button'         => ['title', 'url', 'messages', 'template_id', 'actions'],
    ];

    /** @type MessageRevision[] */
    protected $messageRevisions;

    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;
    /**
     * @type MessageRevisionRepositoryInterface
     */
    private $messageRevisionRepo;

    /**
     * MessageBlockService constructor.
     *
     * @param TemplateRepositoryInterface        $templateRepo
     * @param BotRepositoryInterface             $botRepo
     * @param MessageRevisionRepositoryInterface $messageRevisionRepo
     */
    public function __construct(
        BotRepositoryInterface $botRepo,
        TemplateRepositoryInterface $templateRepo,
        MessageRevisionRepositoryInterface $messageRevisionRepo
    ) {
        $this->botRepo = $botRepo;
        $this->templateRepo = $templateRepo;
        $this->messageRevisionRepo = $messageRevisionRepo;
    }

    /**
     * @param array $input
     *
     * @return \App\Models\Message[]
     */
    public static function normalizeMessages(array $input)
    {
        return array_map(function ($message) {
            return is_array($message)? Message::factory($message, true) : $message;
        }, $input);
    }

    /**
     * @param Message[] $input
     * @param Message[] $original
     * @param           $botId
     * @param bool      $allowReadOnly
     * @return \App\Models\Message[]
     */
    public function correspondInputMessagesToOriginal(array $input, array $original = [], $botId, $allowReadOnly = false)
    {
        $this->messageRevisions = [];

        $ret = $this->makeMessages($input, $original, $botId, $allowReadOnly, true);

        if ($this->messageRevisions) {
            foreach ($this->messageRevisions as &$version) {
                $version['bot_id'] = $botId;
                $version['message_id'] = $version['id'];
                unset($version['id']);
            }
            $this->messageRevisionRepo->bulkCreate($this->messageRevisions);
            $this->messageRevisions = [];
        }


        return $ret;
    }


    protected function makeMessages(array $input, array $original = [], $botId, $allowReadOnly = false, $versioningEnabled = false)
    {
        $original = (new Collection($original))->keyBy(function (Message $message) {
            return $message->id->__toString();
        });

        $normalized = [];

        foreach ($input as $inputMessage) {

            if ($isNew = empty($inputMessage->id)) {
                $inputMessage->id = new ObjectID(null);
            } else {
                $inputMessage->id = new ObjectID($inputMessage->id);
            }

            $originalMessage = null;

            // If the message id is not in the original messages,
            // it means the user entered an invalid id (manually)
            // just skip it for now.
            if (! $isNew && ! ($originalMessage = $original->pull($inputMessage->id->__toString()))) {
                continue;
            }

            $inputMessage->type = $isNew? $inputMessage->type : $originalMessage->type;
            if (is_null($inputMessage->readonly)) {
                $inputMessage->readonly = false;
            }

            if (! $allowReadOnly) {
                $inputMessage->readonly = $isNew? false : $originalMessage->readonly;
            }

            if ($inputMessage->type === 'button') {
                $tags = array_merge($inputMessage->actions['add_tags'], $inputMessage->actions['remove_tags']);
                $this->botRepo->createTagsForBot($botId, $tags);
                if ($inputMessage->messages) {
                    $inputMessage->messages = $this->makeMessages($inputMessage->messages, $isNew? [] : $originalMessage->messages, $botId, $allowReadOnly);
                    if (! $inputMessage->messages && ! $inputMessage->url) {
                        throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
                    }
                }
            }

            if (in_array($inputMessage->type, ['image', 'card'])) {
                $this->persistImageFile($inputMessage);
            }

            if ($inputMessage->type === 'card_container') {
                $inputMessage->cards = $this->makeMessages($inputMessage->cards, $isNew? [] : $originalMessage->cards, $botId, $allowReadOnly);
                if (! $inputMessage->cards) {
                    throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
                }
            }

            if (in_array($inputMessage->type, ['text', 'card'])) {
                $inputMessage->buttons = $this->makeMessages($inputMessage->buttons, $isNew? [] : $originalMessage->buttons, $botId, $allowReadOnly);
            }

            $normalized[] = $inputMessage;

            if ($versioningEnabled && ($isNew || $this->messagesAreDifferent($inputMessage, $originalMessage))) {
                $this->messageRevisions[] = get_object_vars($inputMessage);
            }
        }

        // @todo handle deleted messages (those in $current and not in $input): Delete Sent Messages records, Delete revisions, Delete image files.

        $this->moveReadonlyBlockToTheBottom($normalized);

        return $normalized;
    }

    /**
     * Moves the disabled message blocks to the end of the array, while maintaining the input order.
     *
     * @param Message[] $messages
     */
    private function moveReadonlyBlockToTheBottom(array &$messages)
    {
        stable_usort($messages, function (Message $a, Message $b) {
            $aIsReadOnly = (bool)$a->readonly;
            $bIsReadOnly = (bool)$b->readonly;

            return $aIsReadOnly < $bIsReadOnly? -1 : ($aIsReadOnly > $bIsReadOnly? 1 : 0);
        });
    }

    /**
     * @param Card|Image|Message $message
     */
    private function persistImageFile(Message $message)
    {
        // No Image Changes.
        if (! $message->file) {
            return;
        }

        $image = ImageManagerStatic::make($message->file->encoded);
        $image->encode('png');
        $fileName = $this->randomFileName('png');
        $image->save(public_path("img/uploads/{$fileName}"));
        $message->image_url = config('app.url') . 'img/uploads/' . $fileName;
    }

    /**
     * Generate a random file name.
     *
     * @param $extension
     *
     * @return string
     */
    protected function randomFileName($extension)
    {
        $fileName = time() . md5(uniqid()) . '.' . $extension;

        return $fileName;
    }

    /**
     * @param Message $inputMessage
     * @param Message $originalMessage
     * @return bool
     */
    private function messagesAreDifferent(Message $inputMessage, Message $originalMessage)
    {
        foreach (self::diffFields[$inputMessage->type] as $field) {

            if (! is_array($inputMessage->{$field})) {
                if ($inputMessage->{$field} != $originalMessage->{$field}) {
                    return true;
                }
                continue;
            }

            if (is_array($inputMessage->{$field})) {

                if (count($inputMessage->{$field}) != count($originalMessage->{$field})) {
                    return true;
                }

                if (! isset($inputMessage->{$field[0]}) || ! is_a($inputMessage->{$field[0]}, Message::class)) {
                    if ($inputMessage->{$field} != $originalMessage->{$field}) {
                        return true;
                    }
                    continue;
                }

                for ($i = 0; $i < count($inputMessage->{$field}); $i++) {
                    if ($this->messagesAreDifferent($inputMessage->{$field}[$i], $originalMessage[$i]->{$field}[$i])) {
                        return true;
                    }
                }
            }

        }

        return false;
    }
}