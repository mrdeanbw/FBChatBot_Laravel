<?php namespace App\Transformers;

use App\Services\LoadsAssociatedModels;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{

    use LoadsAssociatedModels;

    public function includeTemplate($model)
    {
        if (! $model->template_id) {
            return $this->null();
        }

        $this->loadModelsIfNotLoaded($model, ['template']);

        return $this->item($model->template, new TemplateTransformer(), false);
    }

}