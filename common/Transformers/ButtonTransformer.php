<?php namespace Common\Transformers;

use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\MessageRevision;
use Common\Services\LoadsAssociatedModels;

class ButtonTransformer extends BaseTransformer
{

    use LoadsAssociatedModels;

    /**
     * @param Button|MessageRevision $button
     * @return array
     */
    public function transform($button)
    {
        $ret = [
            'title'    => $button->title,
            'url'      => $button->url,
            'template' => $this->getTransformedTemplate($button),
            'messages' => (array)$this->transformInclude($button->messages, new MessageTransformer())
        ];

        foreach (['add_tags', 'remove_tags', 'add_sequences', 'remove_sequences'] as $key) {
            if ($value = $button->{$key}) {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * @param Button|MessageRevision $button
     * @return null
     */
    private function getTransformedTemplate($button)
    {
        $item = $this->includeTemplate($button);

        if ($data = $item->getData()) {
            $templateTransformer = $item->getTransformer();
            $template = $templateTransformer->transform($data);

            return $template;
        }

        return null;
    }

    private function transformActions(array $actions)
    {
        $actions['add_sequences'] = array_map(function (ObjectId $sequenceId) {
            return (string)$sequenceId;
        }, $actions['add_sequences']);

        $actions['remove_sequences'] = array_map(function (ObjectId $sequenceId) {
            return (string)$sequenceId;
        }, $actions['remove_sequences']);

        return $actions;
    }
}