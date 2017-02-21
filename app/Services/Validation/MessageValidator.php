<?php namespace App\Services\Validation;

use Intervention\Image\ImageManagerStatic;
use Illuminate\Validation\Validator as LaravelValidator;

class MessageValidator extends LaravelValidator
{

    private static $instance = null;

    /** @var  LaravelValidator */
    private $originalValidator;

    /**
     * @param LaravelValidator $validator
     *
     * @return MessageValidator
     */
    public static function FromInstance(LaravelValidator $validator)
    {
        if (! self::$instance) {
            self::$instance = new self(
                $validator->getTranslator(),
                $validator->getData(),
                $validator->getRules(),
                $validator->getCustomMessages(),
                $validator->getCustomAttributes()
            );
        }

        self::$instance->originalValidator = $validator;

        return self::$instance;
    }


    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateMessage($attribute, $value, $parameters)
    {

        if (! is_array($value)) {
            $this->setErrorMessage("Your messages format is invalid.");

            return false;
        }

        $allowedTypes = $parameters?: ['text', 'card_container', 'image'];

        $type = array_get($value, 'type');

        if (! $type && count($allowedTypes) === 1) {
            $type = $allowedTypes[0];
        }

        if (! in_array($type, $allowedTypes)) {
            $this->setErrorMessage("Your message type has to be: '" . implode("', or '", $allowedTypes) . "'");

            return false;
        }

        return $this->{"_validate" . camel_case($type)}("{$attribute}.{$type}", $value);
    }


    private function _validateText($attribute, $input)
    {
        if (! $this->validateArray($attribute, $input)) {
            $this->setErrorMessage("Your text message format is invalid.");

            return false;
        }

        $text = array_get($input, 'text');

        if (! $this->validateRequired($attribute, $text)) {
            $this->setErrorMessage("Your text message body is required.");

            return false;
        }

        if (! $this->validateBetween($attribute, $text, [1, 320])) {
            $this->setErrorMessage("Your text message body must be between 1 and 320 characters.");

            return false;
        }

        return $this->_validateButtons($attribute, array_get($input, 'buttons', []));
    }

    private function _validateImage($attribute, $input)
    {
        $imageUrl = array_get($input, 'image_url');
        $file = array_get($input, 'file');
        if (! $imageUrl && ! $file) {
            $this->setErrorMessage("You must upload an image to your image message.");

            return false;
        }

        if ($file) {
            if (! $this->validateImageable($attribute, array_get($file, 'encoded'), ['5MB'])) {
                $this->setErrorMessage("Your image message is invalid. Only image files of size less than 5 MB are allowed.");

                return false;
            }
        }

        if ($imageUrl && ! $this->validateImageUrl($attribute, $imageUrl)) {
            $this->setErrorMessage("Your image message is invalid. Only image files of size less than 5 MB are allowed.");

            return false;
        }

        return true;
    }

    private function _validateCardContainer($attribute, $input)
    {
        return $this->_validateCards($attribute, array_get($input, 'cards', []));
    }

    private function _validateCards($attribute, $cards)
    {
        if (! $this->validateArray($attribute, $cards)) {
            $this->setErrorMessage("Your cards format is invalid.");

            return false;
        }

        if (! $this->validateBetween($attribute, $cards, [1, 10])) {
            $this->setErrorMessage("For each card message, you must add between 1 and 10 cards.");

            return false;
        }

        foreach ($cards as $i => $card) {
            if (! $this->_validateCard("{$attribute}.cards.{$i}", $card)) {
                return false;
            }
        }

        return true;
    }

    private function _validateCard($attribute, $card)
    {
        if (! is_array($card)) {
            $this->setErrorMessage("Your card format is invalid.");

            return false;
        }

        $title = array_get($card, 'title');
        if (! $this->validateRequired($attribute, $title)) {
            $this->setErrorMessage("Your card title is required.");

            return false;
        }

        if (! $this->validateBetween($attribute, $title, [1, 80])) {
            $this->setErrorMessage("Your card title must be between 1 and 80 characters.");

            return false;
        }

        if (! $this->validateMax($attribute, array_get($card, 'subtitle'), [80])) {
            $this->setErrorMessage("Your card subtitle must be between 1 and 80 characters.");

            return false;
        }

        $url = array_get($card, 'url');

        if ($url && ! $this->validateUrl($attribute, $url) && ! $this->validateUrl($attribute, "https://{$url}")) {
            $this->setErrorMessage("Your card url is invalid.");

            return false;
        }

        if (($file = array_get($card, 'file')) && ! $this->validateImageable($attribute, array_get($file, 'encoded'), ['5MB'])) {
            $this->setErrorMessage("Your card has an invalid image. Only image files of size less than 5 MB are allowed.");

            return false;
        }

        if (($image = array_get($card, 'image_url')) && ! $this->validateImageUrl($attribute, $image)) {
            $this->setErrorMessage("Your card has an invalid image. Only image files of size less than 5 MB are allowed.");

            return false;
        }

        return $this->_validateButtons($attribute, array_get($card, 'buttons', []));
    }

    private function _validateButtons($attribute, $buttons)
    {
        if (! $this->validateArray($attribute, $buttons)) {
            $this->setErrorMessage("Your buttons format is invalid.");

            return false;
        }

        if (! $this->validateMax($attribute, $buttons, [3])) {
            $this->setErrorMessage("For each text/card, you may not have more than 3 buttons.");

            return false;
        }

        foreach ($buttons as $i => $button) {
            if (! $this->_validateButton("{$attribute}.buttons.{$i}", $button)) {
                return false;
            }
        }

        return true;
    }

    private function _validateButton($attribute, $button)
    {
        if (! is_array($button)) {
            $this->setErrorMessage("Your button format is invalid.");

            return false;
        }

        $title = array_get($button, 'title');
        if (! $this->validateRequired($attribute, $title)) {
            $this->setErrorMessage("Your button title is required.");

            return false;
        }


        if (! $this->validateBetween($attribute, $title, [1, 30])) {
            $this->setErrorMessage("Your button title must be between 1 and 30 characters.");

            return false;
        }

        $messages = array_get($button, 'messages', []);

        if ($template = array_get($button, 'template', [])) {
            if (! $this->_validateTemplate($attribute, $template)) {
                return false;
            }
            $messages = [];
        }

        if (! $this->_validateButtonMessages($attribute, $messages)) {
            return false;
        }


        $url = array_get($button, 'url');
        if (! $template && ! $messages && ! $this->validateRequired($attribute, $url)) {
            $this->setErrorMessage("Every button must have an associated URL, child messages or message tree.");

            return false;
        }

        if ($url && ! $this->validateUrl($attribute, $url) && ! $this->validateUrl($attribute, "https://{$url}")) {
            $this->setErrorMessage("Your button url is invalid.");

            return false;
        }

        $addTags = array_get($button, 'actions.add_tags', []);
        if (! $this->validateTags($attribute, $addTags)) {
            return false;
        }

        $removeTags = array_get($button, 'actions.remove_tags', []);
        if (! $this->validateTags($attribute, $removeTags)) {
            return false;
        }

        if (array_intersect($addTags, $removeTags)) {
            $this->setErrorMessage("You cannot add the same tag to 'Tag' and 'Untag' actions.");

            return false;
        }

        $addSequences = array_get($button, 'actions.add_sequences', []);
        if (! $this->validateSequences($attribute, $addSequences)) {
            return false;
        }

        $removeSequences = array_get($button, 'actions.remove_sequences', []);
        if (! $this->validateSequences($attribute, $removeSequences)) {
            return false;
        }

        if (array_intersect(array_column($addSequences, 'id'), array_column($removeSequences, 'id'))) {
            $this->setErrorMessage("You cannot add the same sequence to 'Subscribe to sequence' and 'Unsubscribe from sequence' actions.");

            return false;
        }


        return true;
    }

    public function validateTags($attribute, $tags)
    {
        if (! $this->validateArray($attribute, $tags)) {
            $this->setErrorMessage("Your button tags format is invalid.");

            return false;
        }

        foreach ($tags as $tag) {
            if (! $this->validateRequired($attribute, $tag) || ! $this->validateString($attribute, $tag)) {
                $this->setErrorMessage("Your button tag format is invalid.");

                return false;
            }
        }

        return true;
    }


    public function validateSequences($attribute, $sequences)
    {
        if (! $this->validateArray($attribute, $sequences)) {
            $this->setErrorMessage("Your sequences format is invalid.");

            return false;
        }

        foreach ($sequences as $sequence) {
            $id = array_get($sequence, 'id');

            if (! $id) {
                $this->setErrorMessage("Your sequences format is invalid.");

                return false;
            }

            //@todo where exists and bot_id = $botId

            //            if (! $this->validateExists($attribute, $id, ['sequences', 'id',])) {
            //                $this->setErrorMessage("Your sequences format is invalid.");
            //
            //                return false;
            //            }
        }

        return true;

    }

    protected function _validateTemplate($attribute, $template)
    {
        $id = array_get($template, 'id');
        if (! $this->validateRequired($attribute, $id) || ! $this->originalValidator->validateExists($attribute, $id, ['templates', '_id'])) {
            $this->setErrorMessage("The template is invalid.");

            return false;
        }

        return true;
    }

    protected function _validateButtonMessages($attribute, $messages)
    {
        if (! $this->validateArray($attribute, $messages)) {
            $this->setErrorMessage("The template format is invalid.");

            return false;
        }

        if (! $this->validateMax($attribute, $messages, [10])) {
            $this->setErrorMessage("Every subtree cannot have more than 10 sibling messages.");

            return false;
        }

        foreach ($messages as $message) {
            if (! $this->validateMessage("{$attribute}.messages", $message, [])) {
                return false;
            }
        }

        return true;
    }

    private function setErrorMessage($errorMessage)
    {
        $this->originalValidator->setCustomMessages([
            'message' => $errorMessage
        ]);
    }

    private function setErrorMessageFromRule($attribute, $rule, $parameters = [])
    {
        $message = $this->getMessage($attribute, $rule);
        $message = $this->doReplacements($message, $attribute, $rule, $parameters);
        $this->setErrorMessage($message);
    }


    /**
     * Checks if the URL actually belongs to this web app, and the file pointed to exists.
     * @param $attribute
     * @param $imageUrl
     * @return bool
     */
    protected function validateImageUrl($attribute, $imageUrl)
    {
        if (! $this->validateUrl($attribute, $imageUrl)) {
            return false;
        }
        $url = parse_url($imageUrl);
        $arr = explode('/', $imageUrl);
        $original = parse_url(config('app.url'));

        return ($url['host'] == $original['host'] && array_get($url, 'port') == array_get($original, 'port') && count($arr) >= 2 && is_file(public_path('img/uploads/' . $arr[count($arr) - 1])));
    }

    protected function validateImageable($attribute, $value, $params)
    {
        try {
            $image = ImageManagerStatic::make($value);

            if (isset($params[0])) {
                $path = storage_path() . '/' . uniqid();
                $image->save($path);
                $isValid = $image->filesize() <= to_bytes($params[0]);
                unlink($path);

                return $isValid;
            }

            return true;
        } catch (\Exception $e) {
            dd($e->getMessage());

            return false;
        }

    }

}