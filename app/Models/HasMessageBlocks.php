<?php namespace App\Models;

trait HasMessageBlocks
{

    public function messageBlocks()
    {
        return $this->morphMany(MessageBlock::class, 'context')->orderBy('order');
    }

    public function message_blocks()
    {
        return $this->messageBlocks();
    }

    public function unorderedMessageBlocks()
    {
        return $this->morphMany(MessageBlock::class, 'context');
    }

    protected static function bootHasMessageBlocks()
    {
        static::deleting(function (HasMessageBlocksInterface $model) {
            $model->deleteMessageBlocks();
        });
    }

    public function deleteMessageBlocks()
    {
        $this->messageBlocks->each(function ($messageBlock) {
            $messageBlock->forceDelete();
        });
    }
}