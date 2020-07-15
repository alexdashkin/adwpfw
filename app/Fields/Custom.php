<?php

namespace AlexDashkin\Adwpfw\Fields;

/**
 * Custom Field
 */
class Custom extends Field
{
    /**
     * Get Field props
     *
     * @return array
     */
    protected function getInitialPropDefs(): array
    {
        return array_merge(
            parent::getInitialPropDefs(),
            [
                'tpl' => [
                    'default' => 'custom',
                ],
            ]
        );
    }
}
