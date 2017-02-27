<?php namespace App\Transformers;

use App\Services\LoadsAssociatedModels;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{

    use LoadsAssociatedModels;

    /**
     * @param $model
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeTemplate($model)
    {
        if (! $model->template_id) {
            return $this->null();
        }

        $this->loadModelsIfNotLoaded($model, ['template']);

        if ($model->template->explicit) {
            return $this->item($model->template, new TemplateTransformer(), false);
        }

        return $this->item($model->template, new ImplicitTemplateTransformer, false);
    }

    /**
     * @param $data
     * @param $transformer
     * @return array|mixed
     */
    public function transformInclude($data, $transformer)
    {
        if (is_array($data)) {
            return $this->transformIncludeCollection($data, $transformer);
        }

        return $this->transformIncludeItem($data, $transformer);
    }

    /**
     * @param $data
     * @param $transformer
     * @return array
     */
    private function transformIncludeCollection($data, $transformer)
    {
        $collection = $this->collection($data, $transformer, false);
        $transformer = $collection->getTransformer();

        return array_map(function ($item) use ($transformer) {
            return $transformer->transform($item);
        }, $collection->getData());
    }

    /**
     * @param $data
     * @param $transformer
     * @return mixed
     */
    private function transformIncludeItem($data, $transformer)
    {
        $item = $this->item($data, $transformer, false);
        $transformer = $item->getTransformer();

        return $transformer->transform($item->getData());
    }

}