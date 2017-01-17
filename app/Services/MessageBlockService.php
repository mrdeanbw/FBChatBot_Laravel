<?php namespace App\Services;

use App\Models\MessageInstance;
use App\Models\Page;
use App\Models\Button;
use App\Models\Template;
use App\Models\MessageBlock;
use App\Models\HasMessageBlocksInterface;
use App\Repositories\Template\TemplateRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\MessageBlock\MessageBlockRepository;
use Illuminate\Support\Collection;

class MessageBlockService
{

    /**
     * @type TagService
     */
    private $tags;

    /**
     * @type ImageFileService
     */
    private $imageFiles;
    /**
     * @type MessageBlockRepository
     */
    private $messageBlockRepo;
    /**
     * @type TemplateRepository
     */
    private $templateRepo;

    /**
     * MessageBlockService constructor.
     *
     * @param MessageBlockRepository $messageBlockRepo
     * @param TemplateRepository     $templateRepo
     * @param TagService             $tags
     * @param ImageFileService       $imageFiles
     */
    public function __construct(
        MessageBlockRepository $messageBlockRepo,
        TemplateRepository $templateRepo,
        TagService $tags,
        ImageFileService $imageFiles
    ) {
        $this->tags = $tags;
        $this->imageFiles = $imageFiles;
        $this->messageBlockRepo = $messageBlockRepo;
        $this->templateRepo = $templateRepo;
    }

    /**
     * @param HasMessageBlocksInterface $model
     * @param                           $blocks
     * @param bool                      $allowImplicitTemplates
     * @return Collection
     */
    public function persist(HasMessageBlocksInterface $model, array $blocks, $allowImplicitTemplates = true)
    {
        // Disabled message blocks (with "powered by" copyright messages) should always be at the end.
        $this->moveDisabledBlocksToTheEnd($blocks);

        // If some of the message blocks associated with the model are not present in the input,
        // it means they have been deleted at the frontend. Thus, we delete them as well.
        $this->deleteAbsentMessageBlocks($model, $blocks);

        $page = $model->page;

        $order = 1;

        $ret = [];

        foreach ($blocks as $blockData) {

            $messageBlock = $this->createOrUpdateMessageBlock($model, $blockData, $order, $page);

            // If the message block that has just been created is a button,
            // then sync the tags, and persist the template which may be associated with it.
            if ($blockData['type'] == 'button') {
                $this->syncAddAndRemoveTagsForButton($messageBlock, $blockData, $page);
                if ($allowImplicitTemplates && $templateData = array_get($blockData, 'template')) {
                    $this->processButtonTemplate($templateData, $page, $messageBlock);
                }
            }

            // If the message blocks has children, persist them as well.
            if ($this->mayHaveChildBlocks($messageBlock)) {
                $this->persistChildBlocks($blockData, $messageBlock);
            }

            $order++;

            $ret[] = $messageBlock;
        }

        return new Collection($ret);
    }

    /**
     * Moves the disabled message blocks to the end of the array, while maintaining the input order.
     * @param $blocks
     */
    private function moveDisabledBlocksToTheEnd(array &$blocks)
    {
        stable_usort($blocks, function ($a, $b) {
            $aIsDisabled = (bool)array_get($a, 'is_disabled');
            $bIsDisabled = (bool)array_get($b, 'is_disabled');

            return $aIsDisabled < $bIsDisabled? -1 : ($aIsDisabled > $bIsDisabled? 1 : 0);
        });
    }

    /**
     * Delete message blocks associated with a model that don't exist in the input.
     * @param HasMessageBlocksInterface $model
     * @param array                     $blocks
     */
    private function deleteAbsentMessageBlocks(HasMessageBlocksInterface $model, array $blocks)
    {
        $existingMessageBlocks = $this->getMessageBlocks($model)->pluck('id')->toArray();
        $keepMessageBlocks = array_values(array_filter(array_pluck($blocks, 'id')));
        $deleteMessageBlocks = array_diff($existingMessageBlocks, $keepMessageBlocks);
        $this->messageBlockRepo->batchDelete($deleteMessageBlocks);
    }

    /**
     * Create a message block if it doesn't exist, otherwise update and return it.
     * @param HasMessageBlocksInterface $model
     * @param                           $blockData
     * @param                           $order
     * @param Page                      $page
     * @return MessageBlock
     */
    public function createOrUpdateMessageBlock(HasMessageBlocksInterface $model, $blockData, $order, Page $page)
    {

        if ($id = array_get($blockData, 'id')) {

            $block = $this->messageBlockRepo->findForModel($id, $model);

            if (! $block) {
                throw new ModelNotFoundException;
            }

            $data = [];

            // If a message block is disabled (copyrights), then don't allow updating it.
            if (! $block->is_disabled) {
                $data = $this->parseBlockData($blockData, $page->facebook_id);
            }

            // We always want to update the order.
            $data['order'] = $order;

            $this->update($block, $data);

            return $block;
        }

        // Message block doesn't exist, create a new one and return it.
        $data = $this->parseBlockData($blockData, $page->facebook_id);
        $data['order'] = $order;

        return $this->messageBlockRepo->create($data, $model);
    }

    /**
     * Sync tag actions with button, there are two types "add" tags and "remove" tags.
     * We create all the tags in our system, and then we sync them with the button along with the mode (add/remove).
     * @param Button $button
     * @param array  $data
     * @param Page   $page
     */
    private function syncAddAndRemoveTagsForButton(Button $button, array $data, Page $page)
    {
        $syncData = [];

        // Add Tags
        if ($tags = array_get($data, 'tag')) {
            foreach ($this->tags->getOrCreateTags($tags, $page) as $tagId) {
                $syncData[$tagId] = ['add' => true];
            }
        }

        // Remove Tags
        if ($tags = array_get($data, 'untag')) {
            foreach ($this->tags->getOrCreateTags($tags, $page) as $tagId) {
                $syncData[$tagId] = ['add' => false];
            }
        }

        // Syncing add & remove tags
        $this->messageBlockRepo->syncTags($button, $syncData);
    }

    /**
     * Determine whether or not a message block may have nested message blocks.
     * @param MessageBlock $block
     * @return bool
     */
    private function mayHaveChildBlocks(MessageBlock $block)
    {
        return in_array($block->type, ['text', 'card_container', 'card']);
    }

    /**
     * Get a template if it exists, otherwise create it.
     * @param $templateData
     * @param $page
     * @param $messageBlock
     * @return Template
     */
    private function getOrCreateTemplate(array $templateData, MessageBlock $messageBlock, Page $page)
    {
        if ($templateId = array_get($templateData, 'id')) {
            $template = $this->templateRepo->findByIdForPage($templateId, $page);

            if (! $template) {
                throw new ModelNotFoundException;
            }

            return $template;
        }

        $data = [
            'is_explicit' => 1,
            'name'        => "Subtree For Button #{$messageBlock->id}",
        ];

        return $this->templateRepo->create($data, $page);
    }

    /**
     * @param Button   $button
     * @param Template $template
     */
    private function associateTemplateWithButton(Button $button, Template $template)
    {
        $this->messageBlockRepo->associateTemplateWithButton($button, $template);
    }

    /**
     * @param $blockData
     * @param $messageBlock
     */
    private function persistChildBlocks($blockData, $messageBlock)
    {
        $childBlocks = array_filter(array_get($blockData, 'message_blocks', []), function ($child) use ($messageBlock) {
            return $messageBlock->type != $child['type'];
        });

        $this->persist($messageBlock, $childBlocks);
    }

    /**
     * Gets/Creates the template to be used with the button, associates them and persist the template's child message blocks.
     * @param $templateData
     * @param $page
     * @param $messageBlock
     */
    private function processButtonTemplate($templateData, $page, $messageBlock)
    {
        $template = $this->getOrCreateTemplate($templateData, $messageBlock, $page);

        $this->associateTemplateWithButton($messageBlock, $template);

        if (! $template->is_explicit) {
            // If the template is create implicitly, to handle this button action exclusively,
            // then we need to persist the template child message blocks.
            $blocks = array_get($templateData, 'message_blocks', []);
            $this->persist($template, $blocks, true);
        }
    }

    /**
     * Parse the input for clean block data, stores images on the drive, and save their URL.
     * @param array $blockData
     * @param       $pageFacebookId
     * @return array
     */
    private function parseBlockData(array $blockData, $pageFacebookId)
    {
        $data = [
            'type'      => array_get($blockData, 'type'),
            'text'      => array_get($blockData, 'text'),
            'title'     => array_get($blockData, 'title'),
            'subtitle'  => array_get($blockData, 'subtitle'),
            'url'       => array_get($blockData, 'url'),
            'image_url' => array_get($blockData, 'image_url'),
        ];

        // Store image data into a file, and create a URL to it.
        // If a URL is passed instead of the image data, it means that
        // we have already stored this image before, and generated a URL for it.
        if ($data['image_url'] && ! $this->imageFiles->isUrl($data['image_url'])) {
            $directory = public_path("img/uploads/{$pageFacebookId}");
            $fileName = $this->imageFiles->store($directory, $data['image_url']);
            $data['image_url'] = url("img/uploads/{$pageFacebookId}/{$fileName}");
        }

        // Add "http" prefix, if it is not included.
        if ($data['url'] && preg_match("#https?://#", $data['url']) === 0) {
            $data['url'] = "http://{$data['url']}";
        }

        return $data;
    }

    /**
     * @param HasMessageBlocksInterface $model
     * @return \Illuminate\Support\Collection
     */
    public function getMessageBlocks(HasMessageBlocksInterface $model)
    {
        return $this->messageBlockRepo->getAllForModel($model);
    }

    /**
     * Update a message block.
     * @param MessageBlock $block
     * @param array        $data
     */
    public function update(MessageBlock $block, array $data)
    {
        $this->messageBlockRepo->update($block, $data);
    }
    
    /**
     * Find a message block by ID.
     * @param      $id
     * @return MessageBlock|null
     */
    public function findMessageBlock($id)
    {
        return $this->messageBlockRepo->findById($id);
    }

    /**
     * Find a message block that belongs to a certain page.
     * @param      $id
     * @param Page $page
     * @return MessageBlock|null
     */
    public function findMessageBlockForPage($id, Page $page)
    {
        $block = $this->findMessageBlock($id);

        if (! $block) {
            return null;
        }
        
        if ($block->page->id != $page->id) {
            return null;
        }

        return;
    }

    /**
     * Return the root model, to which this message block belongs.
     * @param MessageBlock $messageBlock
     * @return HasMessageBlocksInterface
     */
    public function getRootContext(MessageBlock $messageBlock)
    {
        return $this->messageBlockRepo->rootContext($messageBlock);
    }
}