<?php

return [
    'facade' => [
        'class' => AlexDashkin\Adwpfw\Modules\Facade::class,
        'single' => true,
    ],
    'logger' => [
        'class' => AlexDashkin\Adwpfw\Modules\Logger::class,
        'single' => true,
    ],
    'twig' => [
        'class' => AlexDashkin\Adwpfw\Modules\Twig::class,
        'single' => true,
    ],
    'helpers' => [
        'class' => AlexDashkin\Adwpfw\Modules\Helpers::class,
        'single' => true,
    ],
    'db' => [
        'class' => AlexDashkin\Adwpfw\Modules\Db::class,
        'single' => true,
    ],
    'query' => [
        'class' => AlexDashkin\Adwpfw\Modules\Query::class,
    ],
    'hook' => [
        'class' => AlexDashkin\Adwpfw\Modules\Hook::class,
    ],
    'asset.css' => [
        'class' => AlexDashkin\Adwpfw\Modules\Assets\Css::class,
    ],
    'asset.js' => [
        'class' => AlexDashkin\Adwpfw\Modules\Assets\Js::class,
    ],
    'admin_ajax' => [
        'class' => AlexDashkin\Adwpfw\Modules\Api\AdminAjax::class,
    ],
    'rest' => [
        'class' => AlexDashkin\Adwpfw\Modules\Api\Rest::class,
    ],
    'admin_page' => [
        'class' => AlexDashkin\Adwpfw\Modules\AdminPage::class,
    ],
    'admin_page_tab' => [
        'class' => AlexDashkin\Adwpfw\Modules\AdminPageTab::class,
    ],
    'admin_bar' => [
        'class' => AlexDashkin\Adwpfw\Modules\AdminBar::class,
    ],
    'dashboard_widget' => [
        'class' => AlexDashkin\Adwpfw\Modules\DbWidget::class,
    ],
    'cron' => [
        'class' => AlexDashkin\Adwpfw\Modules\CronJob::class,
    ],
    'metabox' => [
        'class' => AlexDashkin\Adwpfw\Modules\Metabox::class,
    ],
    'notice' => [
        'class' => AlexDashkin\Adwpfw\Modules\Notice::class,
    ],
    'post_state' => [
        'class' => AlexDashkin\Adwpfw\Modules\PostState::class,
    ],
    'post_type' => [
        'class' => AlexDashkin\Adwpfw\Modules\PostType::class,
    ],
    'profile_section' => [
        'class' => AlexDashkin\Adwpfw\Modules\ProfileSection::class,
    ],
    'term_meta' => [
        'class' => AlexDashkin\Adwpfw\Modules\TermMeta::class,
    ],
    'shortcode' => [
        'class' => AlexDashkin\Adwpfw\Modules\Shortcode::class,
    ],
    'updater.plugin' => [
        'class' => AlexDashkin\Adwpfw\Modules\Updater\Plugin::class,
    ],
    'updater.theme' => [
        'class' => AlexDashkin\Adwpfw\Modules\Updater\Theme::class,
    ],
    'widget' => [
        'class' => AlexDashkin\Adwpfw\Modules\Widget::class,
    ],
    'customizer.panel' => [
        'class' => AlexDashkin\Adwpfw\Modules\Customizer\Panel::class,
    ],
    'customizer.section' => [
        'class' => AlexDashkin\Adwpfw\Modules\Customizer\Section::class,
    ],
    'customizer.setting' => [
        'class' => AlexDashkin\Adwpfw\Modules\Customizer\Setting::class,
    ],
    'field.actions' => [
        'class' => AlexDashkin\Adwpfw\Fields\Actions::class,
    ],
    'field.button' => [
        'class' => AlexDashkin\Adwpfw\Fields\Button::class,
    ],
    'field.checkbox' => [
        'class' => AlexDashkin\Adwpfw\Fields\Checkbox::class,
    ],
    'field.custom' => [
        'class' => AlexDashkin\Adwpfw\Fields\Custom::class,
    ],
    'field.heading' => [
        'class' => AlexDashkin\Adwpfw\Fields\Heading::class,
    ],
    'field.hidden' => [
        'class' => AlexDashkin\Adwpfw\Fields\Hidden::class,
    ],
    'field.number' => [
        'class' => AlexDashkin\Adwpfw\Fields\Number::class,
    ],
    'field.password' => [
        'class' => AlexDashkin\Adwpfw\Fields\Password::class,
    ],
    'field.radio' => [
        'class' => AlexDashkin\Adwpfw\Fields\Radio::class,
    ],
    'field.select' => [
        'class' => AlexDashkin\Adwpfw\Fields\Select::class,
    ],
    'field.select2' => [
        'class' => AlexDashkin\Adwpfw\Fields\Select2::class,
    ],
    'field.text' => [
        'class' => AlexDashkin\Adwpfw\Fields\Text::class,
    ],
    'field.textarea' => [
        'class' => AlexDashkin\Adwpfw\Fields\Textarea::class,
    ],
];