<?php
namespace App\Transformers;

use App\Models\Template;

class TemplateTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks'];

    public function transform(Template $template)
    {
        return [
            'id'          => (int)$template->id,
            'name'        => $template->name,
            'is_explicit' => $template->is_explicit
        ];
    }
}