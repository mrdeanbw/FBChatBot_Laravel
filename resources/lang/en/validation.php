<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'          => 'The :attribute must be accepted.',
    'active_url'        => 'The :attribute is not a valid URL.',
    'after'             => 'The :attribute must be a date after :date.',
    'alpha'             => 'The :attribute may only contain letters.',
    'alpha_dash'        => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num'         => 'The :attribute may only contain letters and numbers.',
    'array'             => 'The :attribute must be an array.',
    'before'            => 'The :attribute must be a date before :date.',
    'between'           => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file'    => 'The :attribute must be between :min and :max kilobytes.',
        'string'  => 'The :attribute must be between :min and :max characters.',
        'array'   => 'The :attribute must have between :min and :max items.',
    ],
    'boolean'           => 'The :attribute field must be true or false.',
    'confirmed'         => 'The :attribute confirmation does not match.',
    'date'              => 'The :attribute is not a valid date.',
    'date_format'       => 'The :attribute does not match the format :format.',
    'different'         => 'The :attribute and :other must be different.',
    'digits'            => 'The :attribute must be :digits digits.',
    'digits_between'    => 'The :attribute must be between :min and :max digits.',
    'dimensions'        => 'The :attribute has invalid image dimensions.',
    'distinct'          => 'The :attribute field has a duplicate value.',
    'email'             => 'The :attribute must be a valid email address.',
    'exists'            => 'The selected :attribute is invalid.',
    'file'              => 'The :attribute must be a file.',
    'filled'            => 'The :attribute field is required.',
    'image'             => 'The :attribute must be an image.',
    'in'                => 'The selected :attribute is invalid.',
    'in_array'          => 'The :attribute field does not exist in :other.',
    'integer'           => 'The :attribute must be an integer.',
    'ip'                => 'The :attribute must be a valid IP address.',
    'json'              => 'The :attribute must be a valid JSON string.',
    'max'               => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file'    => 'The :attribute may not be greater than :max kilobytes.',
        'string'  => 'The :attribute may not be greater than :max characters.',
        'array'   => 'The :attribute may not have more than :max items.',
    ],
    'mimes'             => 'The :attribute must be a file of type: :values.',
    'mimetypes'         => 'The :attribute must be a file of type: :values.',
    'min'               => [
        'numeric' => 'The :attribute must be at least :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'The :attribute must be at least :min characters.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
    'not_in'            => 'The selected :attribute is invalid.',
    'numeric'           => 'The :attribute must be a number.',
    'present'           => 'The :attribute field must be present.',
    'regex'             => 'The :attribute format is invalid.',
    'required'          => 'The :attribute field is required.',
    //    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_unless'   => 'The :attribute field is required unless :other is in :values.',
    'required_with'     => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values is present.',
    'required_without'  => 'The :attribute field is required when :values is not present.',
    //    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'              => 'The :attribute and :other must match.',
    'size'              => [
        'numeric' => 'The :attribute must be :size.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'The :attribute must be :size characters.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'            => 'The :attribute must be a string.',
    'timezone'          => 'The :attribute must be a valid zone.',
    'unique'            => 'The :attribute has already been taken.',
    'uploaded'          => 'The :attribute failed to upload.',
    'url'               => 'The :attribute format is invalid.',


    'message_block'           => 'You have an invalid :attribute.',
    'imageable'               => 'Only images of size less than 5MB are allowed.',
    'max_if'                  => 'The :attribute has too many items.',
    'min_if'                  => 'The :attribute has too few items.',
    'required_if_without_all' => 'The :attribute field is required.',
    'required_if'             => 'The :attribute field is required.',
    'required_without_all'    => 'The :attribute field is required.',


    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'message_blocks.*.url' => [
            'required_without_all' => 'Every button must have either "Go To URL" or "Send Other Messages" action..'
        ],

        'message_blocks.*.message_blocks.*.url' => [
            'required_if_without_all' => 'Every button must have either "Go To URL" or "Send Other Messages" action..'
        ],

        'message_blocks.*.message_blocks.*.message_blocks.*.url' => [
            'required_without_all' => 'Every button must have either "Go To URL" or "Send Other Messages" action..'
        ],

//        'keyword.unique' => "The Mode/Keyword combination should be unique.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'name'                                    => "Name",

        // Main Menu Buttons
        'message_blocks.*.title'                  => 'Button Title',
        'message_blocks.*.actions'                => 'Button Actions',
        'message_blocks.*.url'                    => 'Button Redirect URL',
        'message_blocks.*.send'                   => 'Button Send Message',
        'message_blocks.*.send.in'                => 'Button Send Message Template',
        'message_blocks.*.subscribe'              => 'Button Subscribe Sequence List',
        'message_blocks.*.unsubscribe'            => 'Button Unsubscribe Sequence List',
        'message_blocks.*.tag'                    => 'Button Tag List',
        'message_blocks.*.tag.*'                  => 'Tag in Button Tag List',
        'message_blocks.*.untag'                  => 'Button Untag List',
        'message_blocks.*.untag.*'                => 'Tag in Button Untag List',

        // Message Blocks Attributes
        'message_blocks'                          => 'Message Blocks',
        'message_blocks.*'                        => "Message Block",
        'message_blocks.*.type'                   => "Message Block Type",
        'message_blocks.*.text'                   => 'Text Message',
        'message_blocks.*.image_url'              => 'Image Message',
        'message_blocks.*.message_blocks'         => 'Associated Message Block (Buttons/Cards)', // limit to 10 cards, and 3 buttons
        'message_blocks.*.message_blocks.*.type'  => 'Associated Message Block Type',

        // Common Button & Card title validation
        'message_blocks.*.message_blocks.*.title' => '(Button/Card) Block Title', // limit to 80 if card, 30 otherwise (button)

        // Child Card Validation

        'message_blocks.*.message_blocks.*.subtitle'       => 'Card Subtitle', // card subtitle
        'message_blocks.*.message_blocks.*.image_url'      => 'Card Image',
        'message_blocks.*.message_blocks.*.message_blocks' => 'Card Buttons', // card buttons

        'message_blocks.*.message_blocks.*.url'                          => 'Button/Card URL',

        // Child Button Validation
        'message_blocks.*.message_blocks.*.actions'                      => 'Button Actions',
        'message_blocks.*.message_blocks.*.send'                         => 'Button Send Message',
        'message_blocks.*.message_blocks.*.send.in'                      => 'Button Send Message Template',
        'message_blocks.*.message_blocks.*.subscribe'                    => 'Button Subscribe Sequence List',
        'message_blocks.*.message_blocks.*.unsubscribe'                  => 'Button Unsubscribe Sequence List',
        'message_blocks.*.message_blocks.*.tag'                          => 'Button Tag List',
        'message_blocks.*.message_blocks.*.tag.*'                        => 'Tag in Button Tag List',
        'message_blocks.*.message_blocks.*.untag'                        => 'Button Untag List',
        'message_blocks.*.message_blocks.*.untag.*'                      => 'Tag in Button Untag List',

        // Grand Child Button Validation
        'message_blocks.*.message_blocks.*.message_blocks.*.title'       => 'Card Button Title', // button text
        'message_blocks.*.message_blocks.*.message_blocks.*.actions'     => 'Card Button Actions',
        'message_blocks.*.message_blocks.*.message_blocks.*.url'         => 'Card Button Redirect URL',
        'message_blocks.*.message_blocks.*.message_blocks.*.send'        => 'Card Button Send Message',
        'message_blocks.*.message_blocks.*.message_blocks.*.send.in'     => 'Card Button Send Message Template',
        'message_blocks.*.message_blocks.*.message_blocks.*.subscribe'   => 'Card Button Subscribe Sequence List',
        'message_blocks.*.message_blocks.*.message_blocks.*.unsubscribe' => 'Card Button Unsubscribe Sequence List',
        'message_blocks.*.message_blocks.*.message_blocks.*.tag'         => 'Card Button Tag List',
        'message_blocks.*.message_blocks.*.message_blocks.*.tag.*'       => 'Tag in Card Button Tag List',
        'message_blocks.*.message_blocks.*.message_blocks.*.untag'       => 'Card Button Untag List',
        'message_blocks.*.message_blocks.*.message_blocks.*.untag.*'     => 'Tag in Card Button Untag List',
    ],

];
