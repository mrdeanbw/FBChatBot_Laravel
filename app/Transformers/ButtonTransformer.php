<?php namespace App\Transformers;

use App\Models\Button;
use App\Services\LoadsAssociatedModels;

class ButtonTransformer extends BaseTransformer
{

    use LoadsAssociatedModels;

    public function transform(Button $button)
    {
        $item = $this->includeTemplate($button);
        if ($data = $item->getData()) {
            $templateTransformer = $item->getTransformer();
            $template = $templateTransformer->transform($data);
        } else {
            $template = null;
        }

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