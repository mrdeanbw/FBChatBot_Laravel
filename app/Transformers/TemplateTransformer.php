<?php namespace App\Transformers;

use App\Models\Template;

class TemplateTransformer extends BaseTransformer
{

    protected $availableIncludes = ['messages'];

    public function transform(Template $template)
    {
        return [
            'id'       => $template->id,
            'name'     => $template->name,
            'explicit' => $template->explicit,
        ];
    }

    protected function includeMessages(Template $template)
    {
        return $this->collection($template->messages, new MessageTransformer(), false);
    }

}