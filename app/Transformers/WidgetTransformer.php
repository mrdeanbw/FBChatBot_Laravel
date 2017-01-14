<?php
namespace App\Transformers;

use App\Models\Widget;

class WidgetTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks'];

    public function transform(Widget $widget)
    {
        return [
            'id'             => (int)$widget->id,
            'name'           => $widget->name,
            'type'           => $widget->type,
            'widget_options' => $widget->options,
            'sequence'       => $widget->sequence,
        ];
    }
}