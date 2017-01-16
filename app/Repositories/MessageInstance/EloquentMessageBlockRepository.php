<?php namespace App\Repositories\MessageInstance;


use App\Models\MessageBlock;
use App\Models\MessageInstance;
use App\Models\Subscriber;

class EloquentMessageInstanceRepository implements MessageInstanceRepository
{

    /**
     * Create a new message block, and associate it with a given model.
     * @param array        $data
     * @param MessageBlock $messageBlock
     * @param Subscriber   $subscriber
     * @return MessageInstance
     */
    public function create(array $data, MessageBlock $messageBlock, Subscriber $subscriber)
    {
        $data['subscriber_id'] = $subscriber->id;
        $data['page_id'] = $messageBlock->page->id;

        return $messageBlock->instances()->create($data);
    }

    /**
     * Update an existing message block.
     * @param MessageInstance $messageInstance
     * @param array           $data
     * @return mixed
     */
    public function update(MessageInstance $messageInstance, array $data)
    {
        $messageInstance->update($data);
    }
}
