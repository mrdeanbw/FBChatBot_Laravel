<?php
namespace App\Transformers;

use App\Models\MessageBlock;
use App\Models\Button;

class MessageBlockTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks', 'template'];

    public function transform(MessageBlock $messageBlock)
    {
        return [
            'id'          => (int)$messageBlock->id,
            'type'        => $messageBlock->type,
            'is_disabled' => (bool)$messageBlock->is_disabled,
            'order'       => (int)$messageBlock->order,
            'text'        => $messageBlock->text,
            'title'       => $messageBlock->title,
            'subtitle'    => $messageBlock->subtitle,
            'image_url'   => $messageBlock->image_url,
            'url'         => $messageBlock->url,
            'tag'         => $messageBlock->type == 'button'? $messageBlock->addTags->pluck('tag')->toArray() : null,
            'untag'       => $messageBlock->type == 'button'? $messageBlock->removeTags->pluck('tag')->toArray() : null,
            'stats'       => [
                'sent'   => [
                    'total'  => $messageBlock->instances()->count(),
                    'unique' => $messageBlock->instances()->groupBy('subscriber_id')->count()
                ],
                'read'   => [
                    'total'  => $messageBlock->instances()->whereNotNull('read_at')->count(),
                    'unique' => $messageBlock->instances()->whereNotNull('read_at')->groupBy('subscriber_id')->count()
                ],
                'clicks' => [
                    'total'  => $messageBlock->instances()->sum('clicks'),
                    'unique' => $messageBlock->instances()->where('clicks', '!=', 0)->groupBy('subscriber_id')->count(),
                ]
            ],
        ];
    }

    public function includeTemplate(MessageBlock $model)
    {
        if (! $model->template) {
            return $this->null();
        }

        return $this->item($model->template, new TemplateTransformer(), false);
    }

}