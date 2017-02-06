<?php namespace App\Transformers;

use App\Models\Button;

class ButtonTransformer extends BaseTransformer
{

    public function transform(Button $button)
    {
        return [
            'id'       => $button->id,
            'type'     => $button->type,
            'title'    => $button->title,
            'readonly' => $button->readonly,
            'url'      => $button->url,
            'actions'  => $button->actions,
        ];
    }
}