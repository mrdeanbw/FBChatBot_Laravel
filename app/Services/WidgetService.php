<?php namespace App\Services;

use App\Models\Page;
use App\Models\Widget;
use App\Services\MessageBlockService;

class WidgetService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;

    /**
     * WidgetService constructor.
     * @param MessageBlockService $messageBlocks
     */
    public function __construct(MessageBlockService $messageBlocks)
    {
        $this->messageBlocks = $messageBlocks;
    }

    /**
     * @param Page $page
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(Page $page)
    {
        return $page->widgets;
    }

    /**
     * @param             $id
     * @param Page        $page
     * @return Widget
     */
    public function find($id, Page $page)
    {
        return $page->widgets()->findOrFail($id);
    }

    /**
     * @param      $id
     * @param      $input
     * @param Page $page
     */
    public function update($id, $input, Page $page)
    {
        \DB::beginTransaction();

        $widget = $this->find($id, $page);
        $widget->name = $input['name'];
        $widget->sequence_id = $input['sequence']['id'];
        $widget->type = $input['type'];
        $widget->options = $this->cleanOptions($widget->type, $input['widget_options']);
        $widget->save();

        $this->messageBlocks->persist($widget, $input['message_blocks'], $page);
        
        \DB::commit();
    }

    /**
     * @param      $input
     * @param Page $page
     * @return Widget
     */
    public function create($input, Page $page)
    {
        \DB::beginTransaction();

        $widget = new Widget();
        $widget->name = $input['name'];
        $widget->sequence_id = $input['sequence']['id'];
        $widget->type = $input['type'];
        $widget->options = $this->cleanOptions($widget->type, $input['widget_options']);
        $page->widgets()->save($widget);

        $this->messageBlocks->persist($widget, $input['message_blocks'], $page);

        \DB::commit();

        return $widget;
    }


    /**
     * @param      $id
     * @param Page $page
     */
    public function delete($id, $page)
    {
        $widget = $this->find($id, $page);
        \DB::beginTransaction();
        $widget->delete();
        \DB::commit();
    }


    /**
     * @param $type
     * @param $options
     * @return array
     */
    private function cleanOptions($type, $options)
    {
        $allowed = [
            'button' => ['color', 'size'],
        ];

        $clean = [];
        foreach ($allowed[$type] as $optionKey) {
            $clean[$optionKey] = $options[$optionKey];
        }

        return $clean;
    }

}