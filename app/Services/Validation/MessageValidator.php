<?php namespace App\Services\Validation;

use App\Services\ImageFileService;
use Illuminate\Validation\Validator as LaravelValidator;

class MessageValidator extends LaravelValidator
{

    private static $instance = null;

    /** @var  LaravelValidator */
    private $originalValidator;

    /** @type  ImageFileService::class */
    private $imageFiles;

    /**
     * @param LaravelValidator $validator
     *
     * @return MessageValidator
     */
    public static function FromInstance(LaravelValidator $validator)
    {
        if (! self::$instance) {
            self::$instance = new self($validator->getTranslator(), $validator->getData(), $validator->getRules(), $validator->getCustomMessages(),
                $validator->getCustomAttributes());
        }

        self::$instance->originalValidator = $validator;
        self::$instance->imageFiles = app(ImageFileService::class);

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
        $image = array_get($input, 'image_url');
        if (! $image) {
            $this->setErrorMessage("You must upload an image to your image message.");

            return false;
        }

        if (! $this->imageFiles->validateSubmittedImage($image)) {
            $this->setErrorMessage("Your image message is invalid. Only image files of size less than 5MB are allowed.");

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

        $image = array_get($card, 'image_url');
        if ($image && ! $this->imageFiles->validateSubmittedImage($image)) {
            $this->setErrorMessage("Your card image is invalid. Only image files of size less than 5MB are allowed.");

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

        $template = array_get($button, 'template', []);
        if (! array_get($template, 'name') && ! array_get($template, 'messages')) {
            $template = [];
        }

        if ($template && ! $this->_validateTemplate("{$attribute}.template", $template)) {
            return false;
        }

        $url = array_get($button, 'url');
        if (! array_get($template, 'id') && ! array_get($template, 'messages') && ! $this->validateRequired($attribute, $url)) {
            $this->setErrorMessage("Every button must have an associated URL, child messages or message tree.");

            return false;
        }

        if ($url && ! $this->validateUrl($attribute, $url) && ! $this->validateUrl($attribute, "https://{$url}")) {
            $this->setErrorMessage("Your button url is invalid.");

            return false;
        }

        $addTags = array_get($button, 'actions.add_tags', []);
        if (! $this->_validateTags($attribute, $addTags)) {
            return false;
        }

        $removeTags = array_get($button, 'actions.remove_tags', []);
        if (! $this->_validateTags($attribute, $removeTags)) {
            return false;
        }

        if (array_intersect($addTags, $removeTags)) {
            $this->setErrorMessage("You cannot add the same tag to 'Tag' and 'Untag' actions.");

            return false;
        }

        $addSequences = array_get($button, 'actions.add_sequences', []);
        if (! $this->_validateSequences($attribute, $addSequences)) {
            return false;
        }

        $removeSequences = array_get($button, 'actions.remove_sequences', []);
        if (! $this->_validateSequences($attribute, $removeSequences)) {
            return false;
        }

        if (array_intersect(array_column($addSequences, 'id'), array_column($removeSequences, 'id'))) {
            $this->setErrorMessage("You cannot add the same sequence to 'Subscribe to sequence' and 'Unsubscribe from sequence' actions.");

            return false;
        }


        return true;
    }

    protected function _validateTags($attribute, $tags)
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


    private function _validateSequences($attribute, $sequence){
        throw new \Exception("Not implemented");
    }

    protected function _validateTemplate($attribute, $template)
    {
        if (! $this->validateArray($attribute, $template)) {
            $this->setErrorMessage("The template format is invalid.");

            return false;
        }

        $id = array_get($template, 'id');
        if ($id && ! $this->originalValidator->validateExists($attribute, $id, ['templates', 'id'])) {
            $this->setErrorMessage("The template is invalid.");

            return false;
        }

        $name = array_get($template, 'name');
        if (! $this->validateRequired($attribute, $name)) {
            $this->setErrorMessage("Every subtree name is required.");

            return false;
        }

        if (! $this->validateMax($attribute, $name, [250])) {
            $this->setErrorMessage("The subtree name may not be greater than 250 characters.");

            return false;
        }

        foreach (array_get($template, 'messages', []) as $message) {
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
}