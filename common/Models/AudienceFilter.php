<?php namespace Common\Models;

class AudienceFilter extends ArrayModel
{

    /** @type  AudienceFilterGroup[] */
    public $groups = [];
    public $enabled;
    public $join_type = 'and';

    /**
     * AudienceFilter constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        foreach (array_pull($data, 'groups', []) as $group) {
            $this->groups[] = new AudienceFilterGroup($group, $strict);
        }
        
        $data['enabled'] = (bool)$data['enabled'];

        parent::__construct($data, $strict);
    }
}
