<?php namespace App\Transformers;

use Common\Models\Button;
use Common\Models\MessageRevision;
use App\Services\LoadsAssociatedModels;

class ButtonTransformer extends BaseTransformer
{

    use LoadsAssociatedModels;

    /**
     * @param Button|MessageRevision $button
     * @return array
     */
    public function transform(Button $button)
    {
        return [
            'title'    => $button->title,
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