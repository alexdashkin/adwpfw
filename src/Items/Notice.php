<?php

namespace AlexDashkin\Adwpfw\Items;

use AlexDashkin\Adwpfw\App;

/**
 * Admin Notice
 */
class Notice extends Item
{
    /**
     * Constructor
     *
     * @param array $data {
     * @type string $slug Defaults to $prefix-notice-uniqid()
     * @type string $message Message to display (tpl will be ignored)
     * @type string $tpl Name of the notice TWIG template
     * @type string $type Notice type (success, error)
     * @type bool $dismissible Whether can be dismissed
     * @type int $days When to show again after dismissed
     * @type array $classes Container classes
     * @type array $args Additional TWIG Args
     * @type callable $callback Must return true for the Notice to show
     * }
     */
    public function __construct(array $data, App $app)
    {
        $this->props = [
            'slug' => [
                'default' => $this->getDefaultSlug('notice'),
            ],
            'message' => [
                'default' => null,
            ],
            'tpl' => [
                'default' => null,
            ],
            'type' => [
                'default' => 'success'
            ],
            'dismissible' => [
                'type' => 'bool',
                'default' => true,
            ],
            'days' => [
                'type' => 'int',
                'default' => 0,
            ],
            'classes' => [
                'type' => 'array',
                'default' => [],
            ],
            'args' => [
                'type' => 'array',
                'default' => [],
            ],
            'callback' => [
                'type' => 'callable',
                'default' => null,
            ],
        ];

        parent::__construct($data, $app);
    }

    /**
     * Show a notice
     */
    public function show()
    {
        $this->updateOption($this->data['slug'], 0);
    }

    /**
     * Stop showing a notice
     */
    public function stop()
    {
        $this->updateOption($this->data['slug'], 2147483647);
    }

    /**
     * Dismiss a notice
     */
    public function dismiss()
    {
        $this->updateOption($this->data['slug'], time());
    }

    /**
     * Process the Notice
     */
    public function process()
    {
        $data = $this->data;

        if (!empty($data['callback']) && !$data['callback']()) {
            return;
        }

        $optionName = $this->config['prefix'] . '_notices';
        $optionValue = get_option($optionName) ?: [];
        $dismissed = !empty($optionValue[$data['slug']]) ? $optionValue[$data['slug']] : 0;

        // If dismissed but days have not yet passed - do not show
        if ($dismissed > time() - $data['days'] * DAY_IN_SECONDS) {
            return;
        }

        echo $this->render();
    }

    /**
     * Render the Notice
     *
     * @return string
     */
    private function render()
    {
        $data = $this->data;

        $slug = $data['slug'];

        $classes = $this->config['prefix'] . '-notice ' . implode(' ', $data['classes']) . ' notice notice-' . $data['type'];

        if ($data['dismissible']) {
            $classes .= ' is-dismissible';
        }

        if ($data['message']) {
            return "<div class='$classes' data-id='$slug'><p>{$data['message']}</p></div>";

        } elseif ($data['tpl']) {
            $data['args']['slug'] = $slug;
            $data['args']['classes'] = $classes;
            return $this->m('Utils')->renderTwig('notices/' . $data['tpl'], $data['args']);
        }

        return '';
    }

    private function updateOption($name, $value)
    {
        $optionName = $this->config['prefix'] . '_notices';

        $optionValue = get_option($optionName) ?: [];

        $optionValue[$name] = $value;

        update_option($optionName, $optionValue);
    }
}