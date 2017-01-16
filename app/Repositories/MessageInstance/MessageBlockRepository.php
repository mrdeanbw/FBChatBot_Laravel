<?php namespace App\Repositories\MessageInstance;

use App\Models\Subscriber;
use App\Models\MessageBlock;
use App\Models\MessageInstance;

interface MessageInstanceRepository
{

    /**
     * Create a new message block, and associate it with a given model.
     * @param array        $data
     * @param MessageBlock $messageBlock
     * @param Subscriber   $subscriber
     * @return MessageInstance
     */
    public function create(array $data, MessageBlock $messageBlock, Subscriber $subscriber);

    /**
     * Update an existing message block.
     * @param MessageInstance $messageInstance
     * @param array           $data
     * @return mixed
     */
    public function update(MessageInstance $messageInstance, array $data);
}
