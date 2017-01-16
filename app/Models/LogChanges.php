<?php namespace App\Models;

trait LogChanges
{

    protected static function bootRecordsActivity()
    {
        static::updating(function (BaseModel $model) {
            $changed = $model->getDirty();
            $log = new ChangeLog();
            $log->before = array_intersect_key($model->fresh()->toArray(), $changed);
            $log->after = $changed;
            $model->changeLogs()->save($log);
        });
    }

    public function changeLogs()
    {
        return $this->morphMany(ChangeLog::class, 'context');
    }
}