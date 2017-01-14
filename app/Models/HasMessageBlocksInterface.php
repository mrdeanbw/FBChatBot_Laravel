<?php namespace App\Models;

/**
 * App\Models\HasMessageBlocksInterface
 *
 * @property-read \App\Models\Page                                                    $page
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $unorderedMessageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[] $message_blocks
 */

interface HasMessageBlocksInterface
{
    public function messageBlocks();
    public function message_blocks();
    public function unorderedMessageBlocks();
    public function page();
    public function deleteMessageBlocks();
}