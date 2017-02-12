<?php namespace App\Models;

/**
 * @property Template $template
 */
class SequenceMessage extends ArrayModel
{

    /** @type \MongoDB\BSON\ObjectID */
    public $id;

    public $name;

    /** @type  array */
    public $conditions;

    public $template_id;

    public $live;

    /** @type \Carbon\Carbon */
    public $deleted_at;

    public $queued = 0;

    /**
     * SequenceMessage constructor.
     *
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        parent::__construct($data, $strict);
        $this->normalizeConditions();
    }

    public function normalizeConditions(array $conditions = [])
    {
        if ($conditions) {
            $this->conditions = $conditions;
        }

        $this->conditions = [
            'wait_for' => [
                'days'    => $this->conditions['wait_for']['days'],
                'hours'   => $this->conditions['wait_for']['hours'],
                'minutes' => $this->conditions['wait_for']['minutes'],
            ]
        ];
    }
}
