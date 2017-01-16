<?php namespace App\Repositories\MessageBlock;

use App\Models\Button;
use App\Models\Template;
use App\Models\MessageBlock;
use Illuminate\Support\Collection;
use App\Models\HasMessageBlocksInterface;

class EloquentMessageBlockRepository implements MessageBlockRepository
{

    /**
     * Return all the message blocks associated with a model.
     * @param HasMessageBlocksInterface $model
     * @return Collection
     */
    public function getAllForModel(HasMessageBlocksInterface $model)
    {
        return $model->message_blocks;
    }

    /**
     * Batch delete message blocks
     * @param array $ids
     */
    public function batchDelete(array $ids)
    {
        MessageBlock::whereIn('id', $ids)->delete();
    }

    /**
     * @param int                       $id
     * @param HasMessageBlocksInterface $model
     * @return MessageBlock|null
     */
    public function findForModel($id, HasMessageBlocksInterface $model)
    {
        return $model->message_blocks()->find($id);
    }

    /**
     * Create a new message block, and associate it with a given model.
     * @param array                     $data
     * @param HasMessageBlocksInterface $model
     * @return MessageBlock
     */
    public function create(array $data, HasMessageBlocksInterface $model)
    {
        $class = "App\\Models\\" . studly_case($data['type']);
        $block = new $class($data);
        $model->messageBlocks()->save($block);

        return $block;
    }

    /**
     * Update an existing message block.
     * @param MessageBlock $block
     * @param array        $data
     * @return mixed
     */
    public function update(MessageBlock $block, array $data)
    {
        $block->update($data);
    }

    /**
     * Sync the tags for a button.
     * @param Button $button
     * @param array  $tags
     * @param bool   $detaching
     */
    public function syncTags(Button $button, array $tags, $detaching = true)
    {
        $button->tags()->sync($tags, $detaching);
    }

    /**
     * @param Button   $button
     * @param Template $template
     */
    public function associateTemplateWithButton(Button $button, Template $template)
    {
        $button->template()->associate($template);
    }
}
