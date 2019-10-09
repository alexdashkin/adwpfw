<?php

namespace AlexDashkin\Adwpfw\Modules;

use AlexDashkin\Adwpfw\App;
use AlexDashkin\Adwpfw\Items\Endpoint;

/**
 * REST API Endpoints
 */
class Rest extends ItemsModule
{
    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * Add an Item
     *
     * @param array $data
     * @param App $app
     */
    public function add(array $data, App $app)
    {
        $this->items[] = new Endpoint($data, $app);
    }

    /**
     * Hooks to register Items in WP
     */
    protected function hooks()
    {
        add_filter('rest_api_init', [$this, 'register']);
    }

    public function register()
    {
        foreach ($this->items as $item) {
            $item->register();
        }
    }
}