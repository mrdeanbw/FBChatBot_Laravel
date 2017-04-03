<?php namespace Common\Services\Validation;

use Common\Models\Bot;
use Intervention\Image\ImageManagerStatic;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\Exception\InvalidArgumentException;
use Illuminate\Validation\Validator as LaravelValidator;
use Common\Repositories\Template\TemplateRepositoryInterface;

class BotValidator extends LaravelValidator
{

    /** @var BotValidator */
    protected static $instance = null;

    /** @var  LaravelValidator */
    protected $originalValidator;

    /** @var Bot */
    protected $bot;

    /** @var  TemplateRepositoryInterface */
    protected static $templateRepo;

    protected $allowButtonMessages = false;

    /**
     * @param Bot              $bot
     * @param LaravelValidator $validator
     * @param bool             $allowButtonMessages
     * @return BotValidator
     */
    public static function factory(Bot $bot, LaravelValidator $validator, $allowButtonMessages)
    {
        if (! self::$instance) {
            self::$instance = new self(
                $validator->getTranslator(),
                $validator->getData(),
                $validator->getRules(),
                $validator->getCustomMessages(),
                $validator->getCustomAttributes()
            );
            self::$instance->bot = $bot;
            self::$instance->originalValidator = $validator;
            self::$instance->allowButtonMessages = $allowButtonMessages;
        }

        return self::$instance;
    }

    /**
     * @param Bot $bot
     */
    public static function setBot(Bot $bot)
    {
        static::$instance->bot = $bot;
    }

    /**
     * @return TemplateRepositoryInterface
     */
    public function templateRepo()
    {
        if (! static::$templateRepo) {
            static::$templateRepo = app(TemplateRepositoryInterface::class);
        }

        return static::$templateRepo;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateMessage($attribute, $value)
    {
        $typeAttr = "{$attribute}.type";
        $type = array_get($value, 'type');
        if (! $this->v('required', $typeAttr, $type) || ! $this->v('in', $typeAttr, $type, ['text', 'image', 'card_container'])) {
            return false;
        }

        $idAttr = "{$attribute}.id";
        $id = array_get($value, 'id');
        if ($id && ! $this->v('object_id', $idAttr, $id)) {
            return false;
        }

        if ($type == 'text') {
            return $this->validateTextMessage($attribute, $value);
        }

        if ($type == 'image') {
            return $this->validateImageMessage($attribute, $value);
        }

        return $this->validateCardContainer($attribute, $value);
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateTextMessage($attribute, $value)
    {
        $textAttr = "{$attribute}.text";
        $text = array_get($value, 'text');
        if (! $this->v('required', $textAttr, $text) || ! $this->v('max', $textAttr, $text, [320])) {
            return false;
        }

        $buttonsAttr = "{$attribute}.buttons";
        $buttons = array_get($value, 'buttons');
        if (! $buttons) {
            return true;
        }

        return $this->validateButtons($buttonsAttr, $buttons);
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateImageMessage($attribute, $value)
    {
        $imageUrlAttr = "{$attribute}.image_url";
        $imageUrl = array_get($value, 'image_url');
        if ($imageUrl && (! $this->v('required', $imageUrlAttr, $imageUrl) || ! $this->v('string', $imageUrlAttr, $imageUrl) || ! $this->v('image_url', $imageUrlAttr, $imageUrl))) {
            return false;
        }

        $fileAttr = "{$attribute}.file";
        $file = array_get($value, 'file');
        if ($file || ! $imageUrl) {
            if (! $this->v('required', $fileAttr, $file) || ! $this->v('array', $fileAttr, $file)) {
                return false;
            }
            $encodedFileAttr = "{$attribute}.file.encoded";
            $encodedFile = array_get($value, 'file.encoded');
            if (! $this->v('required', $encodedFileAttr, $encodedFile) || ! $this->v('imageable', $encodedFileAttr, $encodedFile)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateCardContainer($attribute, $value)
    {
        $cardsAttr = "{$attribute}.cards";
        $cards = array_get($value, 'cards');
        if (! $this->v('required', $cardsAttr, $cards) || ! $this->v('array', $cardsAttr, $cards) || ! $this->v('between', $cardsAttr, $cards, [1, 10])) {
            return false;
        }

        foreach ($cards as $i => $card) {
            $attr = "{$cardsAttr}.{$i}";
            if (! $this->v('array', $attr, $card) || ! $this->validateCard($attr, $card)) {
                return false;
            }
        }

        return true;
    }

    public function validateImageUrl($attribute, $value)
    {
        if (! $this->validateUrl($attribute, $value)) {
            return false;
        }
        $url = parse_url($value);
        $arr = explode('/', $value);
        $original = parse_url(config('app.url'));

        return ($url['host'] == $original['host'] && array_get($url, 'port') == array_get($original, 'port') && count($arr) >= 2 && is_file(public_path('img/uploads/' . $arr[count($arr) - 1])));
    }


    public function validateImageable($attribute, $value)
    {
        try {
            $image = ImageManagerStatic::make($value);
            $path = storage_path() . '/' . uniqid();
            $image->save($path);
            $isValid = $image->filesize() <= to_bytes('5MB');
            unlink($path);
        } catch (\Exception $e) {
            return false;
        }

        return $isValid;
    }

    private function validateCard($attribute, $value)
    {
        $titleAttr = "{$attribute}.title";
        $title = array_get($value, 'title');
        if (! $this->v('required', $titleAttr, $title) || ! $this->v('string', $titleAttr, $title) || ! $this->v('max', $titleAttr, $title, [80])) {
            return false;
        }

        $subtitleAttr = "{$attribute}.subtitle";
        $subtitle = array_get($value, 'subtitle');
        if ($subtitle && (! $this->v('string', $subtitleAttr, $subtitle) || ! $this->v('max', $subtitleAttr, $subtitle, [80]))) {
            return false;
        }

        $urlAttr = "{$attribute}.url";
        $url = array_get($value, 'url');
        if ($url && (! $this->v('string', $urlAttr, $url) && ! $this->v('url', $urlAttr, $url))) {
            return false;
        }

        $imageUrlAttr = "{$attribute}.image_url";
        $imageUrl = array_get($value, 'image_url');
        if ($imageUrl && (! $this->v('required', $imageUrlAttr, $imageUrl) || ! $this->v('string', $imageUrlAttr, $imageUrl) || ! $this->v('image_url', $imageUrlAttr, $imageUrl))) {
            return false;
        }

        $fileAttr = "{$attribute}.file";
        $file = array_get($value, 'file');
        if ($file) {
            if (! $this->v('required', $fileAttr, $file) || ! $this->v('array', $fileAttr, $file)) {
                return false;
            }
            $encodedFileAttr = "{$attribute}.file.encoded";
            $encodedFile = array_get($value, 'file.encoded');
            if (! $this->v('required', $encodedFileAttr, $encodedFile) || ! $this->v('imageable', $encodedFileAttr, $encodedFile)) {
                return false;
            }
        }

        $buttonsAttr = "{$attribute}.buttons";
        $buttons = array_get($value, 'buttons');
        if ($buttons && ! $this->validateButtons($buttonsAttr, $buttons)) {
            return false;
        }

        if (! $imageUrl && ! $file && ! $subtitle && ! $buttons) {
            $this->addError($attribute, "card_param");

            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateMainMenuButton($attribute, $value)
    {
        $idAttr = "{$attribute}.id";
        $id = array_get($value, 'id');
        if ($id && (! $this->v('object_id', $idAttr, $id) || ! $this->v('main_menu_button_id', $idAttr, $id))) {
            return false;
        }

        $titleAttr = "{$attribute}.title";
        $title = array_get($value, 'title');
        if (! $this->v('required', $titleAttr, $title) || ! $this->v('max', $titleAttr, $title, [30])) {
            return false;
        }

        $mainActionAttr = "{$attribute}.main_action";
        $mainAction = array_get($value, 'main_action');
        if (! $this->v('required', $mainActionAttr, $mainAction) || ! $this->v('string', $mainActionAttr, $mainAction) || ! $this->v('in', $mainActionAttr, $mainAction, ['url', 'template'])) {
            return false;
        }

        if ($mainAction === 'url') {
            $urlAttr = "{$attribute}.url";
            $url = array_get($value, 'url');
            if (! $this->v('required', $urlAttr, $url) || ! $this->v('string', $urlAttr, $url) || ! $this->v('url', $urlAttr, $url)) {
                return false;
            }

            return true;
        }

        $templateAttr = "{$attribute}.template";
        $template = array_get($value, 'template');
        if (! $this->v('required', $templateAttr, $template) || ! $this->v('array', $templateAttr, $template)) {
            return false;
        }


        $templateIdAttr = "{$attribute}.template.id";
        $templateId = array_get($value, 'template.id');
        if (! $this->v('required', $templateIdAttr, $templateId) ||
            ! $this->v('string', $templateIdAttr, $templateId) ||
            ! $this->v('object_id', $templateIdAttr, $templateId) ||
            ! $this->v('template_id', $templateIdAttr, $templateId)
        ) {
            return false;
        }

        if (($addTags = array_get($value, 'add_tags')) && ! $this->validTags("{$attribute}.add_tags", $addTags)) {
            return false;
        }

        if (($removeTags = array_get($value, 'remove_tags')) && ! $this->validTags("{$attribute}.remove_tags", $removeTags)) {
            return false;
        }

        if ($addTags && $removeTags && array_intersect($addTags, $removeTags)) {
            $this->addError("{$attribute}.remove_tags", 'incompatible_tags');

            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateButton($attribute, $value)
    {
        $idAttr = "{$attribute}.id";
        $id = array_get($value, 'id');
        if ($id && ! $this->v('object_id', $idAttr, $id)) {
            return false;
        }

        $titleAttr = "{$attribute}.title";
        $title = array_get($value, 'title');
        if (! $this->v('required', $titleAttr, $title) || ! $this->v('max', $titleAttr, $title, [30])) {
            return false;
        }

        $urlAttr = "{$attribute}.url";
        $url = array_get($value, 'url');
        if ($url && (! $this->v('required', $urlAttr, $url) || ! $this->v('string', $urlAttr, $url) || ! $this->v('url', $urlAttr, $url))) {
            return false;
        }

        $templateIdAttr = "{$attribute}.template.id";
        $templateId = array_get($value, 'template.id');
        if ($templateId &&
            (! $this->v('required', $templateIdAttr, $templateId) ||
                ! $this->v('string', $templateIdAttr, $templateId) ||
                ! $this->v('object_id', $templateIdAttr, $templateId) ||
                ! $this->v('template_id', $templateIdAttr, $templateId)
            )
        ) {
            return false;
        }

        $messages = null;
        if ($this->allowButtonMessages) {
            $messagesAttr = "{$attribute}.messages";
            $messages = array_get($value, 'messages');
            if (
                $messages && (
                    ! $this->v('required', $messagesAttr, $messages) ||
                    ! $this->v('array', $messagesAttr, $messages) ||
                    ! $this->v('between', $messagesAttr, $messages, [1, 10])
                )
            ) {
                foreach ($messages as $i => $message) {
                    $attr = "{$messagesAttr}.{$i}";
                    if (! $this->validateMessage($attr, $message)) {
                        return false;
                    }
                }
            }
        }

        if (! $url && ! $templateId && ! $messages) {
            $this->addError("{$attribute}.template.id", 'has_main_action');
            return false;
        }

        if (($addTags = array_get($value, 'add_tags')) && ! $this->validTags("{$attribute}.add_tags", $addTags)) {
            return false;
        }

        if (($removeTags = array_get($value, 'remove_tags')) && ! $this->validTags("{$attribute}.remove_tags", $removeTags)) {
            return false;
        }

        if ($addTags && $removeTags && array_intersect($addTags, $removeTags)) {
            $this->addError("{$attribute}.remove_tags", 'incompatible_tags');

            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateMainMenuButtonId($attribute, $value)
    {
        foreach ($this->bot->main_menu->buttons as $button) {
            if ($value == (string)$button->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateUniqueTemplateName($attribute, $value, $parameters)
    {
        $exception = array_get($parameters, 0);
        $exception = $exception? new ObjectID($exception) : null;
        if ($this->templateRepo()->nameExists($value, $this->bot, $exception)) {
            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateUrl($attribute, $value)
    {
        return parent::validateUrl($attribute, $value) || parent::validateUrl($attribute, "http://{$value}");
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateObjectId($attribute, $value)
    {
        try {
            new ObjectID($value);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateTemplateId($attribute, $value)
    {
        return $this->templateRepo()->existsByIdForBot(new ObjectID($value), $this->bot);
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function validateBotTag($attribute, $value)
    {
        return in_array($value, $this->bot->tags);
    }


    public function v($rule, $attribute, $value, $parameters = [])
    {
        $rule = studly_case($rule);
        $method = "validate{$rule}";
        if (! $this->{$method}($attribute, $value, $parameters)) {
            $this->addError($attribute, $rule, $parameters);

            return false;
        }

        return true;
    }

    /**
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        $errors = parent::errors();

        //@todo: remove the "validation.main_menu_button"
        return $errors;
    }

    /**
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     */
    public function addError($attribute, $rule, $parameters = [])
    {
        return parent::addError($attribute, $rule, $parameters);
    }

    /**
     * @param string $attr
     * @param array  $addTags
     * @return bool
     */
    protected function validTags($attr, array $addTags)
    {
        if (! $this->v('array', $attr, $addTags)) {
            return false;
        }
        foreach ($addTags as $i => $tag) {
            $tagAttr = "{$attr}.{$i}";
            if (! $this->v('required', $tagAttr, $tag) || ! $this->v('string', $tagAttr, $tag) || ! $this->v('bot_tag', $tagAttr, $tag)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    protected function validateButtons($attribute, $value)
    {
        if (! $this->v('array', $attribute, $value) || ! $this->v('max', $attribute, $value, [3])) {
            return false;
        }

        foreach ($value as $i => $button) {
            $attr = "{$attribute}.{$i}";
            if (! $this->v('array', $attr, $button) || ! $this->validateButton($attr, $button)) {
                return false;
            }
        }

        return true;
    }


}