<?php

namespace AlexDashkin\Adwpfw\Fields;

use AlexDashkin\Adwpfw\Exceptions\AdwpfwException;
use AlexDashkin\Adwpfw\Traits\ItemTrait;

/**
 * Form Field
 */
abstract class Field
{
    use ItemTrait;

    /**
     * @param array $data Field Data
     * @return Field
     * @throws AdwpfwException
     */
    public static function getField($data)
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($data['type']);

        if (!class_exists($class)) {
            throw new AdwpfwException(sprintf('Field "%s" not found', $data['type']));
        }

        return new $class($data);
    }

    /**
     * Constructor
     *
     * @throws AdwpfwException
     */
    protected function __construct(array $data, array $props = [])
    {
        $defaults = [
            'id' => [
                'required' => true,
            ],
            'label' => [
                'default' => null,
            ],
            'desc' => [
                'default' => null,
            ],
            'class' => [
                'default' => 'adwpfw-form-control',
            ],
            'layout' => [
                'required' => true,
            ],
            'form' => [
                'required' => true,
            ],
            'tpl' => [
                'required' => true,
            ],
        ];

        $this->props = array_merge($defaults, $props);

        $this->data = $this->validateProps($data);
    }

    public function getArgs(array $values)
    {
        $this->data['value'] = isset($values[$this->data['id']]) ? $values[$this->data['id']] : null;

        return $this->data;
    }

    public function sanitize($value)
    {
        return $value;
    }
}