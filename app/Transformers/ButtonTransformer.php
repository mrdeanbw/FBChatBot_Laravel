<?php namespace App\Transformers;

use App\Models\Button;
use App\Services\LoadsAssociatedModels;

class ButtonTransformer extends BaseTransformer
{

    use LoadsAssociatedModels;

    public function transform(Button $button)
    {
        $item = $this->includeTemplate($button);
        $templateTransformer = $item->getTransformer();
        $template = $templateTransformer->transform($item->getData());

        return [
            'id'       => $button->id->__toString(),
            'type'     => $button->type,
            'title'    => $button->title,
            'readonly' => $button->readonly,
            'url'      => $button->url,
            'actions'  => $button->actions,
            'template' => $template,
            'messages' => $this->transformInclude($button->messages, new MessageTransformer())
        ];
    }
}