<?php

namespace AlexDashkin\Adwpfw\Fields;

use AlexDashkin\Adwpfw\App;
use AlexDashkin\Adwpfw\Exceptions\AdwpfwException;

/**
 * Radio Selector.
 */
class Radio extends Field
{
    /**
     * Constructor.
     *
     * @param App $app
     * @param array $data {
     * @type string $id Required.
     * @type string $layout Parent template to extend. Required.
     * @type string $form Form ID (slug). Used to distinguish multiple forms on one page. Required.
     * @type string $tpl Template name. Default 'radio'.
     * @type string $class CSS Class(es) for the control. Default empty.
     * @type string $label Label.
     * @type string $desc Description.
     * @type array $options Options. Required.
     * }
     * @param array $props
     *
     * @throws AdwpfwException
     */
    public function __construct(App $app, array $data, array $props = [])
    {
        $defaults = [
            'options' => [
                'type' => 'array',
                'required' => true,
                'def' => [
                    'value' => '',
                    'label' => 'Option',
                ],
            ],
            'tpl' => [
                'default' => 'radio',
            ],
        ];

        parent::__construct($app, $data, array_merge($defaults, $props));
    }

    public function sanitize($value)
    {
        return sanitize_text_field($value);
    }
}
