<?php namespace App\Services;

use App\Models\Card;
use App\Models\Image;
use App\Models\Button;
use App\Models\Message;
use Dingo\Api\Exception\ValidationHttpException;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use Intervention\Image\ImageManagerStatic;
use App\Repositories\Bot\BotRepositoryInterface;
use App\Repositories\Template\TemplateRepositoryInterface;

class MessageService
{

    /**
     * @type ImageFileService
     */
    private $imageFiles;

    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;

    /**
     * MessageBlockService constructor.
     *
     * @param TemplateRepositoryInterface $templateRepo
     * @param ImageFileService            $imageFiles
     * @param BotRepositoryInterface      $botRepo
     */
    public function __construct(TemplateRepositoryInterface $templateRepo, ImageFileService $imageFiles, BotRepositoryInterface $botRepo)
    {
        $this->imageFiles = $imageFiles;
        $this->templateRepo = $templateRepo;
        $this->botRepo = $botRepo;
    }

    /**
     * @param array $input
     * @return \App\Models\Message[]
     */
    public function normalizeMessages(array $input)
    {
        return array_map(function ($message) {
            return is_array($message)? Message::factory($message, true) : $message;
        }, $input);
    }

    /**
     * @param Message[] $input
     * @param Message[] $current
     * @param           $botId
     * @return \App\Models\Message[]
     */
    public function makeMessages(array $input, array $current = [], $botId)
    {
        $current = (new Collection($current))->keyBy(function (Message $message) {
            return $message->id->__toString();
        });

        $normalized = [];

        foreach ($input as $message) {

            if ($isNew = empty($message->id)) {
                $message->id = new ObjectID();
            } else {
                $message->id = new ObjectID($message->id);
            }

            // If the message id is not in the original messages,
            // it means the user entered an invalid id (manually)
            // just skip it for now.
            if (! $isNew && ! ($original = $current->pull($message->id->__toString()))) {
                continue;
            }

            // @todo add additional field? like stats when creating?
            $message->type = $isNew? $message->type : $original->type;
            $message->readonly = $isNew? false : $original->readonly;

            if ($message->type === 'button') {
                $tags = array_merge($message->actions['add_tags'], $message->actions['remove_tags']);
                $this->botRepo->createTagsForBot($botId, $tags);
                if ($message->messages) {
                    $message->messages = $this->makeMessages($message->messages, $isNew? [] : $original->messages, $botId);
                    if (! $message->messages && ! $message->url) {
                        throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
                    }
                }
            }

            if (in_array($message->type, ['image', 'card'])) {
                $this->persistImageFile($message);
            }

            if ($message->type === 'card_container') {
                $message->cards = $this->makeMessages($message->cards, $isNew? [] : $original->cards, $botId);
                if (! $message->cards) {
                    throw new ValidationHttpException(["messages" => ["Invalid Messages"]]);
                }
            }

            if (in_array($message->type, ['text', 'card'])) {
                $message->buttons = $this->makeMessages($message->buttons, $isNew? [] : $original->buttons, $botId);
            }

            $normalized[] = $message;
        }

        // handle deleted messages (those in $current and not in $input)
        // @todo Remove history? Soft delete? Remove Message Instances... etc?


        $this->moveReadonlyBlockToTheBottom($normalized);

        return $normalized;
    }

    /**
     * Moves the disabled message blocks to the end of the array, while maintaining the input order.
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
     * @param $extension
     * @return string
     */
    protected function randomFileName($extension)
    {
        $fileName = time() . md5(uniqid()) . '.' . $extension;

        return $fileName;
    }

}