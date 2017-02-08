<?php namespace App\Models;

class AudienceFilter extends ArrayModel
{

    public $join_type;
    /** @type  AudienceFilterGroup[] */
    public $groups = [];
    public $enabled;

    /**
     * AudienceFilter constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        foreach (array_pull($data, 'groups') as $group) {
            $this->groups[] = new AudienceFilterGroup($group, $strict);
        }
        $data['enabled'] = (bool)$data['enabled'];

        parent::__construct($data, $strict);
    }
}
