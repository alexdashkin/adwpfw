<?php

namespace AlexDashkin\Adwpfw\Core;

use AlexDashkin\Adwpfw\{Exceptions\AppException, Modules\AcfBlock, Modules\AdminBar, Modules\AdminPage, Modules\AdminPageTab, Modules\Api\AdminAjax, Modules\Api\Rest, Modules\Assets\Css, Modules\Assets\Js, Modules\Block, Modules\Cpt, Modules\CronJob, Modules\Customizer\Panel, Modules\Customizer\Section, Modules\Customizer\Setting, Modules\DbWidget, Modules\Fields\Field, Modules\Hook, Modules\Metabox, Modules\Notice, Modules\PostState, Modules\ProfileSection, Modules\Shortcode, Modules\Sidebar, Modules\Taxonomy, Modules\TermMeta, Modules\Updater\Plugin, Modules\Updater\Theme, Modules\Widget};

/**
 * Main Facade
 */
class Main
{
    /**
     * @var App
     */
    private $app;

    /**
     * @var string
     */
    private $prefix;

    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        $this->prefix = $prefix = $this->app->config('prefix');
    }

    /**
     * Validate and sanitize fields as per provided defs
     *
     * @param array $data
     * @param array $fieldDefs
     * @return array
     * @throws AppException
     */
    public function validateFields(array $data, array $fieldDefs): array
    {
        foreach ($fieldDefs as $name => $settings) {
            if (!isset($data[$name]) && !empty($settings['required'])) {
                throw new AppException('Missing required field: ' . $name);
            }

            if (isset($data[$name])) {
                $value = $data[$name];
                $type = $settings['type'] ?? 'raw';

                if ('email' === $type && !is_email($value)) {
                    throw new AppException('Invalid Email: ' . $value);
                }

                switch ($type) {
                    case 'text':
                        $sanitized = sanitize_text_field($value);
                        break;

                    case 'textarea':
                        $sanitized = sanitize_textarea_field($value);
                        break;

                    case 'email':
                        $sanitized = sanitize_email($value);
                        break;

                    case 'number':
                        $sanitized = (int)$value;
                        break;

                    case 'url':
                        $sanitized = esc_url_raw($value);
                        break;

                    case 'array':
                        $sanitized = is_array($value) ? $value : [];
                        break;

                    case 'form':
                        parse_str($value, $sanitized);
                        $sanitized = array_map('stripslashes_deep', $sanitized);
                        break;

                    case 'raw':
                    default:
                        $sanitized = $value;
                }

                $data[$name] = $sanitized;
            }
        }

        return $data;
    }

    /**
     * Send Email
     *
     * @param array $data
     * @throws AppException
     */
    public function sendMail(array $data)
    {
        // Check required fields
        foreach (['from_name', 'from_email', 'to_email', 'subject', 'body'] as $field) {
            if (empty($data[$field])) {
                throw new AppException(sprintf('Field "%s" is required', $field));
            }
        }

        if (!is_email($data['to_email'])) {
            throw new AppException('Invalid email');
        }

        $fromName = $data['from_name'];
        $fromEmail = $data['from_email'];
        $toEmail = $data['to_email'];
        $subject = $data['subject'];
        $body = $data['body'];
        $headers = sprintf("From: %s <%s>", $fromName, $fromEmail);

        if (!empty($data['format']) && 'html' === $data['format']) {
            $this->addHook(
                'wp_mail_content_type',
                function () {
                    return 'text/html';
                }
            );
        }

        // Catch email errors
        $this->addHook(
            'wp_mail_failed',
            function (\WP_Error $error) {
                throw new AppException($error->get_error_message());
            }
        );

        if (!wp_mail($toEmail, $subject, $body, $headers)) {
            throw new AppException('Unable to send email');
        }
    }

    /**
     * Get Field instance by type
     *
     * @param array $args
     * @return Field
     */
    public function getField(array $args): Field
    {
        switch ($args['type']) {
            case 'checkbox':
                return $this->m('field.checkbox', $args);
            case 'select':
                return $this->m('field.select', $args);
            case 'select2':
                return $this->m('field.select2', $args);
        }

        return $this->m('field', $args);
    }

    /**
     * Perform a Database query
     *
     * @param string $table
     * @return Query
     */
    public function db(string $table = ''): Query
    {
        return new Query($table);
    }

    /**
     * Get Prefixed Table Name
     *
     * @param string $name
     * @return string
     */
    public function getTableName(string $name): string
    {
        return $GLOBALS['wpdb']->prefix . $name;
    }

    /**
     * Render Template (PHP/Twig)
     *
     * @param string $name Template file name without .php/twig
     * @param array $args Args to be passed to the Template. Default [].
     * @return string Rendered Template
     */
    public function render(string $name, array $args = []): string
    {
        $args['prefix'] = $this->prefix;


        // Get tpl files paths
        $paths = [];

        // Single tpl path provided by the client app
        $appPath = $this->app->config('template_path');

        if ($appPath) {
            $paths[] = $appPath;
        }

        // Array of tpl paths provided by the client app
        $appPaths = $this->app->config('template_paths');

        if (!empty($appPaths) && is_array($appPaths)) {
            $paths = array_merge($paths, array_values($appPaths));
        }

        // FW tpls path
        $paths[] = __DIR__ . '/../../tpl';

        foreach ($paths as $path) {
            $filePath = trailingslashit($path) . $name;

            // Searching for PHP template
            if (file_exists($filePath . '.php')) {
                ob_start();
                extract($args);
                include $filePath . '.php';
                return ob_get_clean();
                // Searching for Twig template
            } elseif (file_exists($filePath . '.twig')) {
                return $this->twig($name, $args);
            }
        }

        // Not found
        return sprintf('Template %s not found', $name);
    }

    /**
     * Get Twig Module
     *
     * @return Twig
     */
    public function getTwig(): Twig
    {
        return $this->app->getTwig();
    }

    /**
     * Render Twig Template
     *
     * @param string $name Template file name without .twig.
     * @param array $args Args to be passed to the Template. Default [].
     * @return string Rendered Template
     */
    public function twig(string $name, array $args = []): string
    {
        return $this->app->getTwig()->renderFile($name, $args);
    }

    /**
     * Add Hook (action/filter)
     *
     * @param string $tag
     * @param callable $callback
     * @param int $priority
     * @return Hook
     */
    public function addHook(string $tag, callable $callback, int $priority = 10): Hook
    {
        return $this->m(
            'hook',
            [
                'tag' => $tag,
                'callback' => $callback,
                'priority' => $priority,
            ]
        );
    }

    /**
     * Add Admin Bar
     *
     * @param array $args
     * @return AdminBar
     */
    public function addAdminBar(array $args): AdminBar
    {
        return $this->m('admin_bar', $args);
    }

    /**
     * Add Dashboard Widget
     *
     * @param array $args
     * @return DbWidget
     */
    public function addDbWidget(array $args): DbWidget
    {
        return $this->m('dashboard_widget', $args);
    }

    /**
     * Add Cron Job
     *
     * @param array $args
     * @return CronJob
     */
    public function addCronJob(array $args): CronJob
    {
        return $this->m('cron', $args);
    }

    /**
     * Add CSS asset
     *
     * @param array $args
     * @return Css
     */
    public function addCss(array $args): Css
    {
        return $this->m('asset.css', $args);
    }

    /**
     * Add JS asset
     *
     * @param array $args
     * @return Js
     */
    public function addJs(array $args): Js
    {
        return $this->m('asset.js', $args);
    }

    /**
     * Add AdminAjax action
     *
     * @param array $args
     * @return AdminAjax
     */
    public function addAdminAjax(array $args): AdminAjax
    {
        return $this->m('api.ajax', $args);
    }

    /**
     * Add REST API Endpoint
     *
     * @param array $args
     * @return Rest
     */
    public function addRestEndpoint(array $args): Rest
    {
        return $this->m('api.rest', $args);
    }

    /**
     * Add Plugin Updater
     *
     * @param array $args
     * @return Plugin
     */
    public function updaterPlugin(array $args): Plugin
    {
        return $this->m('updater.plugin', $args);
    }

    /**
     * Add Theme Updater
     *
     * @param array $args
     * @return Theme
     */
    public function updaterTheme(array $args): Theme
    {
        return $this->m('updater.theme', $args);
    }

    /**
     * Add Sidebar
     *
     * @param array $args
     * @return Sidebar
     */
    public function addSidebar(array $args): Sidebar
    {
        return $this->m('sidebar', $args);
    }

    /**
     * Add Widget
     *
     * @param array $args
     * @return Widget
     */
    public function addWidget(array $args): Widget
    {
        $widget = $this->m('widget', $args);

        foreach ($args['fields'] ?? [] as $fieldArgs) {
            $field = $this->getField($fieldArgs);

            $widget->addField($field);
        }

        return $widget;
    }

    /**
     * Add Shortcode
     *
     * @param array $args
     * @return Shortcode
     */
    public function addShortcode(array $args): Shortcode
    {
        return $this->m('shortcode', $args);
    }

    /**
     * Add Admin Notice
     *
     * @param array $args
     * @return Notice
     */
    public function addNotice(array $args): Notice
    {
        return $this->m('notice', $args);
    }

    /**
     * Add Gutenberg Block
     *
     * @param array $args
     * @return Block
     */
    public function addBlock(array $args): Block
    {
        return $this->m('block', $args);
    }

    /**
     * Add ACF Gutenberg Block (requires ACF Pro)
     *
     * @param array $args
     * @return AcfBlock
     * @see acf_register_block_type
     */
    public function addAcfBlock(array $args): AcfBlock
    {
        return $this->m('acf_block', $args);
    }

    /**
     * Add Custom Post Type
     *
     * @param array $args
     * @return Cpt
     */
    public function addCpt(array $args): Cpt
    {
        return $this->m('cpt', $args);
    }

    /**
     * Add Taxonomy
     *
     * @param array $args
     * @return Taxonomy
     */
    public function addTaxonomy(array $args): Taxonomy
    {
        return $this->m('taxonomy', $args);
    }

    /**
     * Add Post State
     *
     * @param int $postId
     * @param string $state
     * @return PostState
     */
    public function addPostState(int $postId, string $state): PostState
    {
        return $this->m(
            'post_state',
            [
                'post_id' => $postId,
                'state' => $state,
            ]
        );
    }

    /**
     * Add Admin Page
     *
     * @param array $args
     * @return AdminPage
     */
    public function addAdminPage(array $args): AdminPage
    {
        /** @var AdminPage $adminPage */
        $adminPage = $this->m('admin_page', $args);

        if (empty($args['tabs'])) {
            return $adminPage;
        }

        foreach ($args['tabs'] as $tabArgs) {
            /** @var AdminPageTab $tab */
            $tab = $this->m('admin_page_tab', $tabArgs);

            foreach ($tabArgs['fields'] as $fieldArgs) {
                $field = $this->getField($fieldArgs);

                $tab->addField($field);
            }

            $adminPage->addTab($tab);
        }

        return $adminPage;
    }

    /**
     * Add Metabox
     *
     * @param array $args
     * @return Metabox
     */
    public function addMetabox(array $args): Metabox
    {
        /** @var Metabox $metabox */
        $metabox = $this->m('metabox', $args);

        foreach ($args['fields'] as $fieldArgs) {
            $field = $this->getField($fieldArgs);

            $metabox->addField($field);
        }

        return $metabox;
    }

    /**
     * Add User Profile Editor Section
     *
     * @param array $args
     * @return ProfileSection
     */
    public function addProfileSection(array $args): ProfileSection
    {
        /** @var ProfileSection $section */
        $section = $this->m('profile_section', $args);

        foreach ($args['fields'] as $fieldArgs) {
            $field = $this->getField($fieldArgs);

            $section->addField($field);
        }

        return $section;
    }

    /**
     * Add Custom Fields to Term editing screen
     *
     * @param array $args
     * @return TermMeta
     */
    public function addTermMeta(array $args): TermMeta
    {
        /** @var TermMeta $section */
        $section = $this->m('term_meta', $args);

        foreach ($args['fields'] as $fieldArgs) {
            $field = $this->getField($fieldArgs);

            $field->setProps(
                [
                    'classes' => 'regular-text',
                ]
            );

            $section->addField($field);
        }

        return $section;
    }

    /**
     * Add Customizer Panel
     *
     * @param array $args
     * @return Panel
     */
    public function addCustomizerPanel(array $args): Panel
    {
        /** @var Panel $panel */
        $panel = $this->m('customizer.panel', $args);

        foreach ($args['sections'] as $sectionArgs) {
            /** @var Section $section */
            $section = $this->m('customizer.section', $sectionArgs);

            $section->setProp('panel', $panel->getProp('id'));

            foreach ($sectionArgs['settings'] as $settingArgs) {
                /** @var Setting $setting */
                $setting = $this->m('customizer.setting', $settingArgs);

                $setting->setProp('section', $section->getProp('id'));

                $section->addSetting($setting);
            }

            $panel->addSection($section);
        }

        return $panel;
    }

    /**
     * Call a method in a loop (e.g. to add multiple items)
     *
     * @param string $method
     * @param array $args
     * @return array
     * @throws AppException
     */
    public function addMany(string $method, array $args): array
    {
        // If singular method exists - call it in a loop
        if (method_exists($this, $method) && is_array($args)) {
            $return = [];

            foreach ($args as $item) {
                $return[] = $this->$method($item);
            }
        } else {
            throw new AppException(sprintf('Method %s not found', $method));
        }

        return $return;
    }

    /**
     * Search in an array.
     *
     * @param array $array Array to parse.
     * @param array $conditions . Array of key-value pairs to compare with.
     * @param bool $single Whether to return a single item.
     * @return mixed
     */
    public function arraySearch(array $array, array $conditions, bool $single = false)
    {
        $found = [];
        $searchValue = end($conditions);
        $searchField = key($conditions);
        array_pop($conditions);

        foreach ($array as $key => $value) {
            if (isset($value[$searchField]) && $value[$searchField] == $searchValue) {
                $found[$key] = $value;
            }
        }

        if (0 === count($found)) {
            return [];
        }

        if (0 !== count($conditions)) {
            $found = $this->arraySearch($found, $conditions);
        }

        return $single ? reset($found) : $found;
    }

    /**
     * Filter an array.
     *
     * @param array $array Array to parse.
     * @param array $conditions Array of key-value pairs to compare with.
     * @param bool $single Whether to return a single item.
     * @return mixed
     */
    public function arrayFilter(array $array, array $conditions, bool $single = false)
    {
        $new = [];
        foreach ($array as $item) {
            foreach ($conditions as $key => $value) {
                if ($item[$key] == $value) {
                    $new[] = $item;
                    if ($single) {
                        return $item;
                    }
                }
            }
        }

        return $new;
    }

    /**
     * Remove duplicates by key.
     *
     * @param array $array Array to parse.
     * @param string $key Key to search duplicates by.
     * @return array Filtered array.
     */
    public function arrayUniqueByKey(array $array, string $key): array
    {
        $existing = [];

        foreach ($array as $arrayKey => $value) {
            if (in_array($value[$key], $existing)) {
                unset($array[$arrayKey]);
            } else {
                $existing[] = $value[$key];
            }
        }

        return $array;
    }

    /**
     * Transform an array.
     *
     * @param array $array Array to parse.
     * @param array $keys Keys to keep.
     * @param string $index Key to be used as index.
     * @param string $sort Key to sort by.
     * @return array
     */
    public function arrayParse(array $array, array $keys = [], string $index = '', string $sort = ''): array
    {
        $new = [];

        foreach ($array as $item) {
            $row = [];

            if ($keys) {
                if (1 === count($keys)) {
                    $row = $item[reset($keys)];
                } else {
                    foreach ($keys as $key) {
                        if (is_array($key)) {
                            $row[current($key)] = $item[key($key)];
                        } else {
                            $row[$key] = $item[$key];
                        }
                    }
                }
            } else {
                $row = $item;
            }

            if ($index) {
                $new[$item[$index]] = $row;
            } else {
                $new[] = $row;
            }
        }

        if ($sort) {
            uasort(
                $new,
                function ($a, $b) use ($sort) {
                    return $a[$sort] > $b[$sort] ? 1 : -1;
                }
            );
        }

        return $new;
    }

    /**
     * Sort an array by key.
     *
     * @param array $array Array to parse.
     * @param string $key Key to sort by.
     * @param bool $keepKeys Keep key=>value assigment when sorting
     * @return array Resulting array.
     */
    public function arraySortByKey(array $array, string $key, bool $keepKeys = false): array
    {
        $func = $keepKeys ? 'uasort' : 'usort';
        $func(
            $array,
            function ($a, $b) use ($key) {
                return $a[$key] > $b[$key] ? 1 : -1;
            }
        );

        return $array;
    }

    /**
     * Arrays deep merge.
     *
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public function arrayMerge(array $arr1, array $arr2): array
    {
        foreach ($arr2 as $key => $value) {
            if (!array_key_exists($key, $arr1)) {
                $arr1[$key] = $value;
                continue;
            }

            if (is_array($arr1[$key]) && is_array($value)) {
                $arr1[$key] = $this->arrayMerge($arr1[$key], $value);
            } else {
                $arr1[$key] = $value;
            }
        }

        return $arr1;
    }

    /**
     * Add an element to an array if not exists.
     *
     * @param array $where Array to add to.
     * @param array $what Array to be added.
     * @return array
     */
    public function arrayAddNonExistent(array $where, array $what): array
    {
        foreach ($what as $name => $value) {
            if (!isset($where[$name])) {
                $where[$name] = $value;
            } elseif (is_array($value)) {
                $where[$name] = $this->arrayAddNonExistent($where[$name], $value);
            }
        }

        return $where;
    }

    /**
     * Recursive implode.
     *
     * @param array $array
     * @param string $glue
     * @return string
     */
    public function deepImplode(array $array, string $glue = ''): string
    {
        $imploded = '';

        foreach ($array as $item) {
            $imploded = is_array($item) ? $imploded . $this->deepImplode($item) : $imploded . $glue . $item;
        }

        return $imploded;
    }

    /**
     * Check functions/classes existence.
     * Used to check if a plugin/theme is active before proceed.
     *
     * @param array $items {
     * @type string $name Plugin or Theme name.
     * @type string $type Type of the dep (class/function).
     * @type string $dep Class or function name.
     * }
     * @return array Not found items.
     */
    public function checkDeps(array $items): array
    {
        $notFound = [];

        foreach ($items as $name => $item) {
            if (('class' === $item['type'] && !class_exists($item['dep']))
                || ('function' === $item['type'] && !function_exists($item['dep']))) {
                $notFound[$name] = $item['name'];
            }
        }

        return $notFound;
    }

    /**
     * Get path to the WP Uploads dir with trailing slash
     *
     * @param string $path Path inside the uploads dir (will be created if not exists).
     * @return string
     */
    public function getUploadsDir(string $path = ''): string
    {
        return $this->getUploads($path);
    }

    /**
     * Get URL of the WP Uploads dir with trailing slash
     *
     * @param string $path Path inside the uploads dir (will be created if not exists).
     * @return string
     */
    public function getUploadsUrl(string $path = ''): string
    {
        return $this->getUploads($path, true);
    }

    /**
     * Get path/url to the WP Uploads dir with trailing slash.
     *
     * @param string $path Path inside the uploads dir (will be created if not exists).
     * @param bool $getUrl Whether to get URL.
     * @return string
     */
    public function getUploads(string $path = '', bool $getUrl = false): string
    {
        $uploadDir = wp_upload_dir();

        $basePath = $uploadDir['basedir'];

        $path = $path ? '/' . trim($path, '/') . '/' : '/';

        $fullPath = $basePath . $path;

        if (!file_exists($fullPath)) {
            wp_mkdir_p($fullPath);
        }

        return $getUrl ? $uploadDir['baseurl'] . $path : $fullPath;
    }

    /**
     * External API request helper.
     *
     * @param array $args {
     * @type string $url . Required.
     * @type string $method Get/Post. Default 'get'.
     * @type array $headers . Default [].
     * @type array $data Data to send. Default [].
     * @type int $timeout . Default 0.
     * }
     *
     * @return mixed Response body or false on failure
     */
    public function apiRequest(array $args)
    {
        $args = array_merge(
            [
                'method' => 'get',
                'headers' => [],
                'data' => [],
                'timeout' => 0,
            ],
            $args
        );

        $url = $args['url'];
        $method = strtoupper($args['method']);
        $data = $args['data'];

        $requestArgs = [
            'method' => $method,
            'headers' => $args['headers'],
            'timeout' => $args['timeout'],
        ];

        if (!empty($data)) {
            if ('GET' === $method) {
                $url .= '?' . http_build_query($data);
            } else {
                $requestArgs['body'] = $data;
            }
        }

        $this->log('Performing api request...');
        $remoteResponse = wp_remote_request($url, $requestArgs);
        $this->log('Response received');

        if (is_wp_error($remoteResponse)) {
            $this->log(implode(' | ', $remoteResponse->get_error_messages()));
            return false;
        }

        if (200 !== ($code = wp_remote_retrieve_response_code($remoteResponse))) {
            $this->log("Response code: $code");
            return false;
        }

        if (empty($remoteResponse['body'])) {
            $this->log('Wrong response format');
            return false;
        }

        return $remoteResponse['body'];
    }

    /**
     * Return Success array.
     *
     * @param string $message Message. Default 'Done'.
     * @param array $data Data to return as JSON. Default [].
     * @param bool $echo Whether to echo Response right away without returning. Default false.
     * @return array
     */
    public function returnSuccess(string $message = 'Success', array $data = [], bool $echo = false): array
    {
        $message = $message ?: 'Success';

        $this->log($message);

        $return = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($echo) {
            wp_send_json($return);
        }

        return $return;
    }

    /**
     * Return Error array.
     *
     * @param string $message Error message. Default 'Unknown Error'.
     * @param bool $echo Whether to echo Response right away without returning. Default false.
     * @return array
     */
    public function returnError(string $message = 'Unknown Error', bool $echo = false)
    {
        $message = $message ?: 'Unknown Error';

        $this->log($message);

        $return = [
            'success' => false,
            'message' => $message,
        ];

        if ($echo) {
            wp_send_json($return);
        }

        return $return;
    }

    /**
     * \WP_Error handler
     *
     * @param mixed $result Result of a function call
     * @return mixed|bool Function return or false on WP_Error
     */
    public function pr($result)
    {
        if ($result instanceof \WP_Error) {
            $this->log($result->get_error_message());

            return false;
        }

        return $result;
    }

    /**
     * Trim vars and arrays.
     *
     * @param array|string $var
     * @return array|string
     */
    public function trim($var)
    {
        if (is_string($var)) {
            return trim($var);
        }

        if (is_array($var)) {
            array_walk_recursive(
                $var,
                function (&$value) {
                    $value = trim($value);
                }
            );
        }

        return $var;
    }

    /**
     * Get output of a function.
     * Used to put output in a variable instead of echo.
     *
     * @param callable $func Callable.
     * @param array $args Function args. Default [].
     * @return string Output
     */
    public function getOutput(callable $func, array $args = []): string
    {
        ob_start();
        call_user_func_array($func, $args);
        return ob_get_clean();
    }

    /**
     * Convert HEX color to RGB.
     *
     * @param string $hex
     * @return string
     */
    public function colorToRgb(string $hex): string
    {
        $pattern = strlen($hex) === 4 ? '#%1x%1x%1x' : '#%2x%2x%2x';
        return sscanf($hex, $pattern);
    }

    /**
     * Remove not empty directory.
     *
     * @param string $path
     */
    public function rmDir(string $path)
    {
        if (!is_dir($path)) {
            return;
        }

        if (substr($path, strlen($path) - 1, 1) != '/') {
            $path .= '/';
        }

        $files = glob($path . '*', GLOB_MARK);

        foreach ($files as $file) {
            is_dir($file) ? $this->rmDir($file) : unlink($file);
        }

        rmdir($path);
    }

    /**
     * Remove WP emojis.
     */
    public function removeEmojis()
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        $this->addHook(
            'tiny_mce_plugins',
            function ($plugins) {
                return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
            }
        );
    }

    /**
     * Add Image Sizes
     *
     * @param array $sizes
     */
    public function addImageSizes(array $sizes)
    {
        $filter = [];
        foreach ($sizes as $size) {
            add_image_size($size['name'], $size['width']);
            $filter[$size['name']] = $size['title'];
        }
        $this->addHook(
            'image_size_names_choose',
            function ($sizes) use ($filter) {
                return array_merge($sizes, $filter);
            }
        );
    }

    /**
     * Add Theme Support
     *
     * @param array $features
     */
    public function addThemeSupport(array $features)
    {
        foreach ($features as $feature => $args) {
            if (is_numeric($feature) && is_string($args)) {
                add_theme_support($args);
            } else {
                add_theme_support($feature, $args);
            }
        }
    }

    /**
     * Get prefixed post meta
     *
     * @param string $name
     * @param int $postId
     * @return mixed
     */
    public function getPostMeta(string $name, int $postId = 0)
    {
        return $this->getMeta($name, 'post', $postId ?: get_queried_object_id());
    }

    /**
     * Get prefixed user meta
     *
     * @param string $name
     * @param int $userId
     * @return mixed
     */
    public function getUserMeta(string $name, int $userId = 0)
    {
        return $this->getMeta($name, 'user', $userId ?: get_current_user_id());
    }

    /**
     * Get prefixed term meta
     *
     * @param string $name
     * @param int $termId
     * @return mixed
     */
    public function getTermMeta(string $name, int $termId = 0)
    {
        return $this->getMeta($name, 'term', $termId ?: get_queried_object_id());
    }

    /**
     * Update prefixed post meta
     *
     * @param string $name
     * @param mixed $value
     * @param int $postId
     * @return int|bool
     */
    public function updatePostMeta(string $name, $value, int $postId = 0)
    {
        return $this->setMeta($name, 'post', $value, $postId);
    }

    /**
     * Update prefixed user meta
     *
     * @param string $name
     * @param mixed $value
     * @param int $userId
     * @return int|bool
     */
    public function updateUserMeta(string $name, $value, int $userId = 0)
    {
        return $this->setMeta($name, 'user', $value, $userId);
    }

    /**
     * Update prefixed term meta
     *
     * @param string $name
     * @param mixed $value
     * @param int $termId
     * @return int|bool
     */
    public function updateTermMeta(string $name, $value, int $termId = 0)
    {
        return $this->setMeta($name, 'term', $value, $termId);
    }

    /**
     * Get prefixed option
     *
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name)
    {
        return get_option($this->prefix . '_' . $name);
    }

    /**
     * Update prefixed option
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function updateOption(string $name, $value): bool
    {
        return update_option($this->prefix . '_' . $name, $value);
    }

    /**
     * Get prefixed customizer value
     *
     * @param string $name
     * @return mixed
     */
    public function getThemeMod(string $name)
    {
        return get_theme_mod($this->prefix . '_' . $name);
    }

    /**
     * Get Settings value
     *
     * @param string $key
     * @param string $optionName
     * @return mixed
     */
    public function setting(string $key, string $optionName = 'settings')
    {
        $settings = $this->getOption($optionName);

        return array_key_exists($key, $settings) ? $settings[$key] : null;
    }

    /**
     * Get prefixed cache entry
     *
     * @param string $name
     * @return mixed
     */
    public function cacheGet(string $name)
    {
        return wp_cache_get($name, $this->prefix);
    }

    /**
     * Update prefixed cache entry
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function cacheSet(string $name, $value)
    {
        return wp_cache_set($name, $value, $this->prefix);
    }

    /**
     * Get prefixed post/user meta
     *
     * @param string $name
     * @param string $type
     * @param int $objectId
     * @return mixed
     */
    private function getMeta(string $name, string $type, int $objectId = 0)
    {
        if (!$objectId) {
            return null;
        }

        $func = 'get_' . $type . '_meta';

        return $func($objectId, '_' . $this->prefix . '_' . $name, true);
    }

    /**
     * Update prefixed post/user meta
     *
     * @param string $name
     * @param string $type
     * @param mixed $value
     * @param int $objectId
     * @return int|bool
     */
    private function setMeta(string $name, string $type, $value, int $objectId = 0)
    {
        $objectId = $objectId ?: get_queried_object_id();

        $func = 'update_' . $type . '_meta';

        return $func($objectId, '_' . $this->prefix . '_' . $name, $value);
    }

    /**
     * Get Prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Add a log entry
     *
     * @param mixed $message Text or any other type including WP_Error.
     * @param array $values If passed, vsprintf() func is applied. Default [].
     * @param int $level 1 = Error, 2 = Warning, 4 = Notice. Default 4.
     */
    public function log($message, array $values = [], int $level = 4)
    {
        $this->app->getLogger()->log($message, $values, $level);
    }

    /**
     * Prefix string
     *
     * @param string $string
     * @param string $glue
     * @return string
     */
    public function prefix(string $string, string $glue = '_'): string
    {
        return sprintf('%s%s%s', $this->prefix, $glue, $string);
    }

    /**
     * Get App Module
     *
     * @param string $alias
     * @param array $args
     */
    private function m(string $alias, array $args = [])
    {
        return $this->app->make($alias, $args);
    }
}
