<?php
namespace App\Transformers;

use App\Models\HasFilterGroupsInterface;
use App\Models\HasMessageBlocksInterface;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{

    public function includeMessageBlocks(HasMessageBlocksInterface $model)
    {
        return $this->collection($model->message_blocks, new MessageBlockTransformer(), false);
    }
    
    public function includeFilterGroups(HasFilterGroupsInterface $model)
    {
        return $this->collection($model->filter_groups, new FilterGroupTransformer(), false);
    }
}