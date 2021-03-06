<?php

namespace AlexDashkin\Adwpfw\Modules\Assets;

use AlexDashkin\Adwpfw\Exceptions\AppException;
use AlexDashkin\Adwpfw\Modules\Module;

abstract class Asset extends Module
{
    /**
     * Init Module
     *
     * @throws AppException
     */
    public function init()
    {
        // If URL is not provided
        if (!$this->getProp('url')) {
            // Check if file is provided
            if ($this->getProp('file')) {
                // Get path
                $file = $this->getProp('file') . $this->getProp('min') . '.' . $this->getFileExt();
                $path = $this->getProp('base_dir') . '/' . $file;

                // If file does not exist - try without .min
                if (!file_exists($path)) {
                    $file = $this->getProp('file') . '.' . $this->getFileExt();
                    $noMinPath = $this->getProp('base_dir') . '/' . $file;

                    // If exists - use it, otherwise - do not register
                    if (file_exists($noMinPath)) {
                        $path = $this->getProp('base_dir') . '/' . $file;
                    } else {
                        throw new AppException(sprintf('Asset not found in "%s" and "%s"', $path, $noMinPath));
                    }
                }

                // Set URL
                $this->setProp('url', $this->getProp('base_url') . '/' . $file);

                // Set Version
                if (!$this->getProp('ver')) {
                    $this->setProp('ver', filemtime($path));
                }
            } else {
                throw new AppException(sprintf('No file or URL specified for asset "%s"', $this->getProp('id')));
            }
        }

        // Action name depends on assets type
        switch ($this->getProp('type')) {
            case 'admin':
                $action = 'admin_enqueue_scripts';
                break;

            case 'block':
                $action = 'enqueue_block_editor_assets';
                break;

            default:
                $action = 'wp_enqueue_scripts';
        }

        // Cannot register on hook as GB Blocks renders before and assets do not get included
//        $this->addHook($action, [$this, 'register'], 0);
        $this->register();

        // If added too late - enqueue immediately, otherwise - use the respective hook
        if (did_action($action)) {
            $this->enqueue();
        } else {
            $this->addHook($action, [$this, 'enqueue'], 99);
        }
    }

    /**
     * Enqueue style
     */
    public function enqueue()
    {
        // Do not enqueue if not required
        if (!$this->getProp('enqueue')) {
            return;
        }

        // Do not enqueue if callback returns false
        $callback = $this->getProp('callback');

        if ($callback && is_callable($callback) && !$callback()) {
            return;
        }

        // Enqueue asset
        $func = $this->getEnqueueFuncName();

        $func($this->getProp('handle'));
    }

    /**
     * Get Default prop values
     *
     * @return array
     */
    protected function defaults(): array
    {
        $baseFile = $this->config('base_file');

        return [
            'id' => function () {
                return sanitize_key(str_replace(' ', '_', $this->getProp('type')));
            },
            'handle' => function () {
                return $this->prefix . '-' . sanitize_title($this->getProp('id'));
            },
            'base_dir' => dirname($baseFile),
            'base_url' => 'theme' === $this->config('type') ? get_stylesheet_directory_uri() : plugin_dir_url($baseFile),
            'min' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min',
            'enqueue' => true,
        ];
    }

    /**
     * Get file extension
     *
     * @return string
     */
    abstract protected function getFileExt(): string;

    /**
     * Get enqueue func name
     *
     * @return string
     */
    abstract protected function getEnqueueFuncName(): string;

    /**
     * Register asset
     */
    abstract protected function register();
}
