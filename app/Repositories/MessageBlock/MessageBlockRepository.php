<?php namespace App\Repositories\MessageBlock;

use App\Models\Button;
use App\Models\MessageBlock;
use App\Models\Template;
use Illuminate\Support\Collection;
use App\Models\HasMessageBlocksInterface;

interface MessageBlockRepository
{

    /**
     * Return all the message blocks associated with a model.
     * @param HasMessageBlocksInterface $model
     * @return Collection
     */
    public function getAllForModel(HasMessageBlocksInterface $model);

    /**
     * Batch delete message blocks
     * @param array $ids
     */
    public function batchDelete(array $ids);

    /**
     * @param int                       $id
     * @param HasMessageBlocksInterface $model
     * @return MessageBlock|null
     */
    public function findForModel($id, HasMessageBlocksInterface $model);


    /**
     * Create a new message block, and associate it with a given model.
     * @param array                     $data
     * @param HasMessageBlocksInterface $model
     * @return MessageBlock
     */
    public function create(array $data, HasMessageBlocksInterface $model);

    /**
     * Update an existing message block.
     * @param MessageBlock $block
     * @param array        $data
     * @return mixed
     */
    public function update(MessageBlock $block, array $data);

    /**
     * Sync the tags for a button.
     * @param Button $button
     * @param array  $tags
     * @param bool   $detaching
     */
    public function syncTags(Button $button, array $tags, $detaching = true);

    /**
     * @param Button   $button
     * @param Template $template
     */
    public function associateTemplateWithButton(Button $button, Template $template);
}
