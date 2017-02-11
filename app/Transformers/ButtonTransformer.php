<?php namespace App\Transformers;

use App\Models\Button;
use App\Services\LoadsAssociatedModels;

class ButtonTransformer extends BaseTransformer
{

    use LoadsAssociatedModels;

    public function transform(Button $button)
    {
        return [
            'id'       => $button->id->__toString(),
            'type'     => $button->type,
            'title'    => $button->title,
            'readonly' => $button->readonly,
            'url'      => $button->url,
            'actions'  => $button->actions,
            'template' => $this->getTransformedTemplate($button),
            'messages' => $this->transformInclude($button->messages, new MessageTransformer())
        ];
    }

    /**
     * @param Button $button
     * @return null
     */
    private function getTransformedTemplate(Button $button)
    {
        $item = $this->includeTemplate($button);
        
        if ($data = $item->getData()) {
            $templateTransformer = $item->getTransformer();
            $template = $templateTransformer->transform($data);

            return $template;
        }

        return null;
    }
}