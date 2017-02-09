<?php namespace App\Transformers;

use App\Models\Template;

class ImplicitTemplateTransformer extends TemplateTransformer
{

    public function transform(Template $template)
    {
        return [
            'id'       => $template->id,
            'name'     => $template->name,
            'explicit' => $template->explicit,
            'messages' => $this->transformInclude($template->messages, new MessageTransformer())
        ];
    }
}