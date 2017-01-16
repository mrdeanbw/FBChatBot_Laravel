<?php namespace App\Models;

/**
 * App\Models\HasMessageBlocksInterface
 *
 * @property int                                                                      $page_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $message_blocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \App\Models\Page                                                    $page
 */
interface HasMessageBlocksInterface
{

    public function messageBlocks();

    public function message_blocks();

    public function unorderedMessageBlocks();

    public function page();

    public function deleteMessageBlocks();
}