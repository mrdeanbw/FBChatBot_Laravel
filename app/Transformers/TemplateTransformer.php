<?php namespace App\Transformers;

use App\Models\Template;

class TemplateTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['messages'];

    public function transform(Template $template)
    {
        return [
            'name'     => $template->name,
            'explicit' => $template->explicit,
        ];
    }

    public function includeMessages(Template $template)
    {
        return $this->collection($template->messages, new MessageTransformer(), false);
    }

}