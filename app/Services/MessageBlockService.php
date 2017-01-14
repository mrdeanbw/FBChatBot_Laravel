<?php

namespace App\Services;

use App\Models\HasMessageBlocksInterface;
use App\Models\MessageBlock;
use App\Models\Page;
use App\Models\Template;

class MessageBlockService
{

    private $tags;
    /**
     * @type ImageFileService
     */
    private $imageFiles;

    /**
     * MessageBlockService constructor.
     *
     * @param TagService       $tags
     * @param ImageFileService $imageFiles
     */
    public function __construct(TagService $tags, ImageFileService $imageFiles)
    {
        $this->tags = $tags;
        $this->imageFiles = $imageFiles;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    private function mayHaveChildren($type)
    {
        return in_array($type, ['text', 'card_container', 'card']);
    }

    /**
     * @param HasMessageBlocksInterface $model
     * @param                           $blockData
     * @param                           $order
     * @param Page                      $page
     *
     * @return MessageBlock
     */
    private function getOrCreateBlock(HasMessageBlocksInterface $model, $blockData, $order, Page $page)
    {

        $class = "App\\Models\\" . studly_case($blockData['type']);

        /** @type MessageBlock $block */
        if ($id = array_get($blockData, 'id')) {
            $block = $model->messageBlocks()->findOrFail($id);
        } else {
            $block = new $class;
        }

        $block->order = $order;

        if (! $block->is_disabled) {

            $block->text = array_get($blockData, 'text');
            $block->title = array_get($blockData, 'title');
            $block->subtitle = array_get($blockData, 'subtitle');
            $block->url = array_get($blockData, 'url');
            $block->image_url = array_get($blockData, 'image_url');

            if ($block->image_url && ! $this->imageFiles->isUrl($block->image_url)) {
                $directory = public_path("img/uploads/{$page->facebook_id}");
                $block->image_url = url("img/uploads/{$page->facebook_id}/{$this->imageFiles->store($directory, $block->image_url)}");
            }

            if ($block->url && preg_match("#https?://#", $block->url) === 0) {
                $block->url = "http://{$block->url}";
            }


            //        if ($template = array_get($options, 'actions.send')) {
            //            $options['actions']['send'] = ['id' => $template['id']];
            //        }
            //
            //        if ($subscribe = array_get($options, 'actions.subscribe')) {
            //            $options['actions']['subscribe'] = extract_attribute($subscribe);
            //        }
            //
            //        if ($unsubscribe = array_get($options, 'actions.unsubscribe')) {
            //            $options['actions']['unsubscribe'] =  extract_attribute($unsubscribe);
            //        }
            //        $block->options = $options;

        }

        return $block;
    }


    /**
     * @param HasMessageBlocksInterface $model
     * @param                           $blocks
     * @param Page|null                 $page
     * @param bool                      $allowImplicitTemplates
     */
    public function persist(HasMessageBlocksInterface $model, $blocks, $page = null, $allowImplicitTemplates = true)
    {
        $order = 1;

        stable_usort($blocks, function ($a, $b) {
            $aIsDisabled = (bool)array_get($a, 'is_disabled');
            $bIsDisabled = (bool)array_get($b, 'is_disabled');

            return $aIsDisabled < $bIsDisabled ? -1 : ($aIsDisabled > $bIsDisabled ? 1 : 0);
        });

        $page = $page ?: $model->page;



        $existingMessageBlocks = $model->message_blocks->pluck('id')->toArray();
        $keepMessageBlocks = array_values(array_filter(array_pluck($blocks, 'id')));
        $deleteMessageBlocks = array_diff($existingMessageBlocks, $keepMessageBlocks);
        MessageBlock::whereIn('id', $deleteMessageBlocks)->delete();

        foreach ($blocks as $blockData) {

            $block = $this->getOrCreateBlock($model, $blockData, $order, $page);

            $model->messageBlocks()->save($block);

            if ($blockData['type'] == 'button' && $tags = array_get($blockData, 'tag')) {
                $block->tags()->attach($this->tags->createTags($tags, $page), ['add' => 1]);
            }

            if ($blockData['type'] == 'button' && $tags = array_get($blockData, 'untag')) {
                $block->tags()->attach($this->tags->createTags($tags, $page), ['add' => 0]);
            }

            if ($blockData['type'] == 'button' && $allowImplicitTemplates && $templateData = array_get($blockData, 'template')) {
                if ($templateId = array_get($templateData, 'id')) {
                    $template = $page->templates()->findOrFail($templateId);
                } else {
                    $template = new Template();
                    $template->is_explicit = 0;
                    $template->name = "Subtree For Button #{$block->id}";
                    $template->page_id = $page->id;
                    $template->save();
                }
                $block->template()->associate($template);
                $block->save();
                if (! $template->is_explicit) {
                    $childBlocks = array_filter(array_get($templateData, 'message_blocks', []), function ($child) use ($block) {
                        return in_array($child['type'], ['text', 'card_container', 'image']);
                    });
                    $this->persist($template, $childBlocks, $page, $allowImplicitTemplates);
                }
            }

            if ($this->mayHaveChildren($block->type)) {
                $childBlocks = array_filter(array_get($blockData, 'message_blocks', []), function ($child) use ($block) {
                    return $block->type != $child['type'];
                });

                $this->persist($block, $childBlocks, $page);
            }

            $order++;
        }
    }

    /**
     * @param HasMessageBlocksInterface $model
     */
    public function disableLastMessageBlock(HasMessageBlocksInterface $model)
    {
        $lastMessageBlock = $model->unorderedMessageBlocks()->latest('order')->first();
        $lastMessageBlock->is_disabled = true;
        $lastMessageBlock->save();

    }
}