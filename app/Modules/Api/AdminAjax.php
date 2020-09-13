<?php

namespace AlexDashkin\Adwpfw\Modules\Api;

/**
 * action*, callback*, fields
 */
class AdminAjax extends Request
{
    /**
     * Init Module
     */
    public function init()
    {
        $actionName = $this->config('prefix') . '_' . $this->getProp('action');

        $this->addHook('wp_ajax_' . $actionName, [$this, 'handle']);
        $this->addHook('wp_ajax_nopriv_' . $actionName, [$this, 'handle']);
    }

    /**
     * Handle the Request
     */
    public function handle()
    {
        check_ajax_referer($this->config('prefix'));

        $this->log('Ajax request: "%s_%s"', [$this->config('prefix'), $this->getProp('action')]);

        $result = $this->execute($_REQUEST['data'] ?? []);

        wp_send_json($result);
    }
}
