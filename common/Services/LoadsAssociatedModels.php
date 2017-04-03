<?php namespace Common\Services;

use Common\Models\ArrayModel;
use Common\Models\BaseModel;
use Common\Repositories\DBBaseRepository;

trait LoadsAssociatedModels
{

    /**
     * @param array|ArrayModel|BaseModel $model
     * @param string[]                   $modelsToLoad
     */
    public function loadModelsIfNotLoaded(&$model, array $modelsToLoad)
    {
        foreach ($modelsToLoad as $modelToLoad) {
            if (is_array($model)) {
                if (isset($model[$modelToLoad])) {
                    continue;
                }
                $model[$modelToLoad] = $this->loadModel($model, $modelToLoad);
                continue;
            }

            if (isset($model->{$modelToLoad})) {
                continue;
            }

            $model->{$modelToLoad} = $this->loadModel($model, $modelToLoad);
        }
    }

    /**
     * @param BaseModel $model
     * @param string    $modelToLoad
     * @return \Common\Models\BaseModel|null
     */
    public function loadModel($model, $modelToLoad)
    {
        switch ($modelToLoad) {
            case 'bot':
                return $this->getRepo('bot')->findById(is_array($model)? $model['bot_id'] : $model->bot_id);

            case 'template':
                return $this->getRepo('template')->findById(is_array($model)? $model['template_id'] : $model->template_id);

            case 'sequences':
                $filter = [['operator' => 'in', 'key' => '_id', 'value' => is_array($model)? $model['sequences'] : $model->sequences]];
                return $this->getRepo('sequence')->getAll($filter);

            default:
                return null;
        }
    }

    /**
     * @param string $model
     * @return DBBaseRepository
     */
    public function getRepo($model)
    {
        $model = studly_case($model);
        $classPath = "Common\\Repositories\\{$model}\\{$model}RepositoryInterface";

        return app($classPath);
    }

}
