<?php namespace App\Services;

use App\Repositories\BaseDBRepository;

trait LoadsAssociatedModels
{

    /**
     * @param       $model
     * @param array $modelsToLoad
     */
    public function loadModelsIfNotLoaded(&$model, array $modelsToLoad)
    {
        foreach ($modelsToLoad as $modelToLoad) {
            if (isset($model->{$modelToLoad})) {
                continue;
            }

            $model->{$modelToLoad} = $this->loadModel($model, $modelToLoad);
        }
    }

    /**
     * @param $model
     * @param $modelToLoad
     * @return \App\Models\BaseModel|null
     */
    public function loadModel($model, $modelToLoad)
    {
        switch ($modelToLoad) {
            case 'template':
                return $this->getRepo('template')->findById($model->template_id);

            default:
                return null;
        }
    }

    /**
     * @param string $model
     * @return BaseDBRepository
     */
    public function getRepo($model)
    {
        $model = studly_case($model);
        $classPath = "App\\Repositories\\{$model}\\{$model}RepositoryInterface";

        return app($classPath);
    }

}