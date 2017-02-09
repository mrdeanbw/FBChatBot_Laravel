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

        return $this->item($model->template, new ImplicitTemplateTransformer, false);
    }

    public function transformInclude($data, $transformer)
    {

        if (is_array($data)) {
            $collection = $this->collection($data, $transformer, false);
            $transformer = $collection->getTransformer();

            return array_map(function ($item) use ($transformer) {
                return $transformer->transform($item);
            }, $collection->getData());
        }

        $item = $this->item($data, $transformer, false);
        $transformer = $item->getTransformer();

        return $transformer->transform($item->getData());
    }

}