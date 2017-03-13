<?php namespace Common\Services;

use Common\Models\Card;
use Common\Models\Image;
use Common\Models\Message;
use MongoDB\BSON\ObjectID;
use Common\Models\SentMessage;
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
        'button'         => ['title', 'url', 'messages', 'template_id', 'actions'],
    ];

    /**
     * @type MessageRevision[]
     */
    protected $messageRevisions;
    /**
     * @type bool
     */
    protected $forMainMenuButtons;
    /**
     * @type array
     */
    protected $delete = [
        'image_files'       => [],
        'sent_messages'     => [],
        'message_revisions' => []
    ];
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
     * @param array $input
     *
     * @return \Common\Models\Message[]
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
     * @return \Common\Models\Message[]
     */
    public function correspondInputMessagesToOriginal(array $input, array $original = [], $botId, $allowReadOnly = false)
    {
        $this->messageRevisions = [];

        $ret = $this->makeMessages($input, $original, $botId, $allowReadOnly, true);
        
        $this->files->delete($this->delete['image_files']);
        $this->sentMessageRepo->bulkDelete($this->delete['sent_messages']);
        $this->messageRevisionRepo->bulkDelete($this->delete['message_revisions']);
        $this->delete = [
            'image_files'       => [],
            'sent_messages'     => [],
            'message_revisions' => []

        ];

        if ($this->versioning && $this->messageRevisions) {

            foreach ($this->messageRevisions as $i => &$version) {
                $version['bot_id'] = $botId;
                $version['message_id'] = $version['id'];

                if ($this->forMainMenuButtons) {
                    $version['clicks'] = ['total' => 0, 'subscribers' => []];
                    $ret[$i]->last_revision_id = $version['_id'] = new ObjectID(null);
                }

                unset($version['id']);
            }

            $this->messageRevisionRepo->bulkCreate($this->messageRevisions);
        }

        $this->messageRevisions = [];

        return $ret;
    }

    /**
     * @param array $input
     * @param array $original
     * @param       $botId
     * @param bool  $allowReadOnly
     * @param bool  $versioningEnabled
     * @return array
     */
    protected function makeMessages(array $input, array $original = [], $botId, $allowReadOnly = false, $versioningEnabled = false)
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

            if ($this->forMainMenuButtons && ! $isNew) {
                $inputMessage->last_revision_id = $originalMessage->last_revision_id;
            }

            if (! $allowReadOnly) {
                $inputMessage->readonly = $isNew? false : $originalMessage->readonly;
            }

            if ($inputMessage->type === 'button') {
                $tags = array_merge($inputMessage->actions['add_tags'], $inputMessage->actions['remove_tags']);
                if ($tags) {
                    $this->botRepo->createTagsForBot($botId, $tags);
                }
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

            if ($this->versioning && $versioningEnabled && ($isNew || $this->messagesAreDifferent($inputMessage, $originalMessage))) {
                $this->messageRevisions[] = get_object_vars($inputMessage);
            }
        }

        $this->setModelsToBeDeleted($original->toArray(), new Collection());
        $this->moveReadonlyBlockToTheBottom($normalized);

        return $normalized;
    }

    /**
     * @param array      $messages
     * @param Collection $revisions
     * @return array
     */
    private function setModelsToBeDeleted(array $messages, Collection $revisions)
    {
        foreach ($messages as $message) {
            if ($message->readonly) {
                continue;
            }

            if (in_array($message->type, ['text', 'image', 'card_container'])) {
                $revisions = $this->messageRevisionRepo->getMessageRevisions($message->id);
            }

            if ($message->type == 'button' && $message->messages) {
                $this->setModelsToBeDeleted($message->messages, new Collection());
            }

            if (in_array($message->type, ['image', 'card'])) {
                $this->setImageFilesToBeDeleted($message, $revisions);
            }

            if ($message->type == 'card_container') {
                $this->setModelsToBeDeleted($message->cards, $revisions);
            }

            if (in_array($message->type, ['text', 'card'])) {
                $this->setModelsToBeDeleted($message->buttons, $revisions);
            }

            if (in_array($message->type, ['text', 'image', 'card_container'])) {
                $this->setSentMessagesToBeDeleted($message);
                $revisions = $revisions->map(function (MessageRevision $revision) {
                    return $revision->_id;
                })->toArray();
                $this->delete['message_revisions'] = array_merge($this->delete['message_revisions'], $revisions);
            }
        }
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

        $ext = $message->file->type;
        if (! in_array($ext, ['png', 'jpg', 'gif'])) {
            $ext = 'png';
        }

        $fileName = $this->randomFileName($ext);

        $image = ImageManagerStatic::make($message->file->encoded);
        $image->encode($ext);

        $message->file->encoded = null;
        $message->file->path = public_path("img/uploads/{$fileName}");

        $image->save($message->file->path);

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

    /**
     * @param Card|Image $message
     * @param Collection $revisions
     */
    private function setImageFilesToBeDeleted($message, Collection $revisions)
    {
        if ($message->type == 'image') {
            /** @type MessageRevision $revision */
            foreach ($revisions as $revision) {
                $this->delete['image_files'][] = $revision->file->path;
            }

            return;
        }

        /** @type MessageRevision $revision */
        foreach ($revisions as $revision) {
            foreach ($revision->cards as $card) {
                if ($card->id == $message->id) {
                    $this->delete['image_files'][] = $card->file->path;
                }
            }
        }
    }

    /**
     * @param $message
     * @return array
     */
    private function setSentMessagesToBeDeleted($message)
    {
        $sentMessageIds = $this->sentMessageRepo->getAllForMessage($message->id)->map(function (SentMessage $sentMessage) {
            return $sentMessage->_id;
        })->toArray();

        $this->delete['sent_messages'] = array_merge($this->delete['sent_messages'], $sentMessageIds);
    }
}