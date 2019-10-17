<?php

namespace AlexDashkin\Adwpfw\Items\Basic;

use AlexDashkin\Adwpfw\App;

/**
 * Top Admin Bar Item
 */
class AdminBar extends Item
{
    /**
     * Constructor.
     *
     * @param array $data {
     * @type string $id ID w/o prefix. Defaults to sanitized $title.
     * @type string $title Bar Title. Required.
     * @type string $parent Parent node ID. Default null.
     * @type string $capability Minimum capability. Default 'manage_options'.
     * @type string $href URL of the link.
     * @type bool $group Whether or not the node is a group. Default false.
     * @type array $meta Meta data including the following keys: 'html', 'class', 'rel', 'lang', 'dir', 'onclick', 'target', 'title', 'tabindex'. Default empty.
     * }
     * @param App $app
     *
     * @see \WP_Admin_Bar::add_node()
     *
     * @throws \AlexDashkin\Adwpfw\Exceptions\AdwpfwException
     */
    public function __construct(array $data, App $app)
    {
        $props = [
            'id' => [
                'default' => $this->getDefaultId($data['title']),
            ],
            'title' => [
                'required' => true,
            ],
            'parent' => [
                'default' => null,
            ],
            'capability' => [
                'default' => 'manage_options'
            ],
            'href' => [
                'default' => null,
            ],
            'group' => [
                'type' => 'bool',
                'default' => false,
            ],
            'meta' => [
                'type' => 'array',
                'default' => [],
            ],
        ];

        parent::__construct($data, $app, $props);
    }

    /**
     * Register Admin Bars in WP
     * Hooked to "admin_bar_menu" action
     *
     * @param \WP_Admin_Bar $wpAdminBar
     */
    public function register(\WP_Admin_Bar $wpAdminBar)
    {
        $data = $this->data;

        if (!current_user_can($data['capability'])) {
            return;
        }

        $data['id'] = $this->prefix . '-' . $data['id'];

        $wpAdminBar->add_node($data);
    }
}