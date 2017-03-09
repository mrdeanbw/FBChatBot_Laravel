<?php namespace Common\Models;

class AudienceFilterGroup extends ArrayModel
{

    public $join_type;
    /** @type  AudienceFilterRule[] */
    public $rules;

    /**
     * AudienceFilterGroup constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        foreach (array_pull($data, 'rules', []) as $rule) {
            $this->rules[] = new AudienceFilterRule($rule, $strict);
        }

        parent::__construct($data, $strict);
    }

}
