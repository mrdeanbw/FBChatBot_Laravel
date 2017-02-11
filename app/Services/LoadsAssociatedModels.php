<?php namespace App\Services;

use App\Models\BaseModel;
use App\Repositories\DBBaseRepository;

trait LoadsAssociatedModels
{

    /**
     * @param BaseModel $model
     * @param string[]  $modelsToLoad
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
     * @param BaseModel $model
     * @param string    $modelToLoad
     * @return \App\Models\BaseModel|null
     */
    public function loadModel($model, $modelToLoad)
    {
        switch ($modelToLoad) {
            case 'bot':
                return $this->getRepo('bot')->findById($model->bot_id);

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