<?php namespace Common\Services;

use Common\Models\Card;
use Common\Models\Image;
use Common\Models\Message;
use MongoDB\BSON\ObjectID;
use Common\Models\MessageRevision;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Intervention\Image\ImageManagerStatic;
use Dingo\Api\Exception\ValidationHttpException;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\Template\TemplateRepositoryInterface;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;
use Common\Repositories\MessageRevision\MessageRevisionRepositoryInterface;

class MessageService
{

    const diffFields = [
        'text'           => ['text', 'buttons'],
        'image'          => ['image_url'],
        'card_container' => ['cards'],
        'card'           => ['title', 'subtitle', 'url', 'image_url', 'buttons'],
        'button'         => ['title', 'url', 'messages', 'template_id', 'add_tags', 'remove_tags', 'add_sequences', 'remove_sequences', 'subscribe', 'unsubscribe'],
    ];

    /**
     * @type MessageRevision[]
     */
    protected $newMessageRevisions = [];

    /**
     * @type MessageRevision[]
     */
    protected $messageRevisions = [];
    /**
     * @type bool
     */
    protected $forMainMenuButtons;
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
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;
    /**
     * @type bool
     */
    private $versioning = true;
    /**
     * @type Filesystem
     */
    private $files;

    /**
     * MessageBlockService constructor.
     *
     * @param Filesystem                         $files
     * @param BotRepositoryInterface             $botRepo
     * @param TemplateRepositoryInterface        $templateRepo
     * @param SentMessageRepositoryInterface     $sentMessageRepo
     * @param MessageRevisionRepositoryInterface $messageRevisionRepo
     */
    public function __construct(
        Filesystem $files,
        BotRepositoryInterface $botRepo,
        TemplateRepositoryInterface $templateRepo,
        SentMessageRepositoryInterface $sentMessageRepo,
        MessageRevisionRepositoryInterface $messageRevisionRepo
    ) {
        $this->files = $files;
        $this->botRepo = $botRepo;
        $this->templateRepo = $templateRepo;
        $this->sentMessageRepo = $sentMessageRepo;
        $this->messageRevisionRepo = $messageRevisionRepo;
    }

    /**
     * @param Message[] $input
     * @param Message[] $original
     * @param ObjectID  $botId
     * @param bool      $allowReadOnly
     * @return \Common\Models\Message[]
     */
    public function correspondInputMessagesToOriginal(array $input, array $original = [], ObjectID $botId, $allowReadOnly = false)
    {
        $this->newMessageRevisions = [];

        $ret = $this->makeMessages($input, $original, $botId, $allowReadOnly);

        if ($this->versioning && $this->newMessageRevisions) {
            $this->messageRevisions = array_merge($this->messageRevisions, $this->newMessageRevisions);
        }

        $this->newMessageRevisions = [];

        return $ret;
    }

    public function persistMessageRevisions()
    {
        if ($this->messageRevisions) {
            $this->messageRevisionRepo->bulkCreate($this->messageRevisions);
            $this->messageRevisions = [];
        }
    }

    /**
     * @param array $input
     * @param array $original
     * @param       $botId
     * @param bool  $allowReadOnly
     * @return array
     */
    protected function makeMessages(array $input, array $original = [], $botId, $allowReadOnly = false)
    {
        $original = (new Collection($original))->keyBy(function (Message $message) {
            return (string)$message->id;
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
            if (! $isNew && ! ($originalMessage = $original->pull((string)$inputMessage->id))) {
                continue;
            }

            $inputMessage->type = $isNew? $inputMessage->type : $originalMessage->type;
            if (is_null($inputMessage->readonly)) {
                $inputMessage->readonly = false;
            }

            if (! $isNew && $originalMessage->last_revision_id) {
                $inputMessage->last_revision_id = $originalMessage->last_revision_id;
            }

            if (! $allowReadOnly) {
                if (! $isNew && $originalMessage->readonly) {
                    $inputMessage->readonly = $originalMessage->readonly;
                } else {
                    unset($inputMessage->readonly);
                }
            }

            if ($inputMessage->type === 'button' && $inputMessage->messages) {
                $inputMessage->messages = $this->makeMessages($inputMessage->messages, ($isNew || ! $originalMessage->messages)? [] : $originalMessage->messages, $botId, $allowReadOnly);
                if (! $inputMessage->messages) {
                    throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
                }
            }

            if (in_array($inputMessage->type, ['image', 'card'])) {
                $this->persistImageFile($inputMessage, $originalMessage);
            }

            if ($inputMessage->type === 'card_container' && $inputMessage->cards) {
                $inputMessage->cards = $this->makeMessages($inputMessage->cards, $isNew? [] : $originalMessage->cards, $botId, $allowReadOnly);
                if (! $inputMessage->cards) {
                    throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
                }
            }

            if (in_array($inputMessage->type, ['text', 'card']) && $inputMessage->buttons) {
                $inputMessage->buttons = $this->makeMessages($inputMessage->buttons, $isNew || ! $originalMessage->buttons? [] : $originalMessage->buttons, $botId, $allowReadOnly);
            }

            $normalized[] = $inputMessage;

            if ($this->versioning && in_array($inputMessage->type, ['text', 'image', 'card_container']) && ($isNew || $this->messagesAreDifferent($inputMessage, $originalMessage))) {
                $temp = get_object_vars($inputMessage);
                $temp['bot_id'] = $botId;
                $temp['message_id'] = $temp['id'];
                $inputMessage->last_revision_id = $temp['_id'] = new ObjectID(null);
                unset($temp['id']);
                $this->newMessageRevisions[] = $temp;
            }
        }

        if ($this->versioning) {
            foreach ($original->toArray() as $message) {
                if ($message->readonly) {
                    continue;
                }
                if (! $message->deleted_at) {
                    $message->deleted_at = mongo_date();
                }
                $normalized[] = $message;
            }
        }

        foreach ($original->toArray() as $message) {
            if ($message->readonly) {
                $normalized[] = $message;
            }
        }

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
     * @param Card|Image $message
     * @param Card|Image $original
     */
    private function persistImageFile($message, $original)
    {
        // No Image Changes.
        if (! $message->file) {
            if ($original && $original->file) {
                $message->file = $original->file;
            } else {
                unset($message->file);
            }

            return;
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/gif'  => 'gif'
        ];

        $ext = isset($map[$message->file->type])? $map[$message->file->type] : 'png';

        $fileName = $this->randomFileName($ext);
        $filePath = public_path("img/uploads/{$fileName}");

        if ($ext == 'gif' && starts_with($message->file->encoded, 'data:image/gif;base64,')) {
            file_put_contents($filePath, base64_decode(substr($message->file->encoded, 22)));
        } else {
            $image = ImageManagerStatic::make($message->file->encoded)->encode($ext);
            $message->file->path = $filePath;
            $image->save($message->file->path);
        }

        $message->file->encoded = null;
        $message->image_url = rtrim(config('app.url'), '/') . '/img/uploads/' . $fileName;
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
                if ($inputMessage->{$field} !== $originalMessage->{$field}) {
                    return true;
                }
                continue;
            }

            if (is_array($inputMessage->{$field})) {

                if (count($inputMessage->{$field}) !== count($originalMessage->{$field})) {
                    return true;
                }

                if (! isset($inputMessage->{$field[0]}) || ! is_a($inputMessage->{$field[0]}, Message::class)) {
                    if ($inputMessage->{$field} !== $originalMessage->{$field}) {
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

    /**
     * @param boolean $versioning
     * @return MessageService
     */
    public function setVersioning($versioning)
    {
        $this->versioning = $versioning;

        return $this;
    }

    /**
     * @param bool $value
     * @return MessageService
     */
    public function forMainMenuButtons($value = true)
    {
        $this->forMainMenuButtons = $value;

        return $this;
    }
}