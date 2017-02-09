<?php namespace App\Transformers;

use App\Models\Button;
use App\Models\Template;
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
            'template' => $this->includeCorrespondingTemplate($button),
        ];
    }

    /**
     * @param Button $button
     * @return Template|array
     */
    private function includeCorrespondingTemplate(Button $button)
    {
        if ($button->template['explicit']) {
            $template = $this->loadModelByID($button->template['id'], 'template');
        } else {
            $template = new Template($button->template);
        }

        if ($template->explicit) {
            return $this->transformInclude($template, new TemplateTransformer());
        }

        return $this->transformInclude($template, new ImplicitTemplateTransformer());
    }
}